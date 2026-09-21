import '../shared/styles.css'
import {
  endSession,
  fetchExtState,
  startSession,
  updateSession,
  type AuthContext,
  type ExtState,
  type SessionPayload,
} from '../shared/api'
import { fetchChannelCategory } from '../shared/helix'
import { listenBroadcast, resolveAuthToken, setStatus } from '../shared/twitch'
import { sessionLabel, setButtonBusy } from '../shared/ui'
import { mountGamePicker } from './gamePicker'

function requireEl<T extends HTMLElement>(selector: string): T {
  const el = document.querySelector<T>(selector)
  if (!el) {
    throw new Error(`Missing required DOM node: ${selector}`)
  }
  return el
}

const statusEl = requireEl<HTMLElement>('#status')
const formEl = requireEl<HTMLFormElement>('#session-form')
const gameInput = requireEl<HTMLInputElement>('#game')
const gameList = requireEl<HTMLElement>('#game-list')
const gameSelected = requireEl<HTMLElement>('#game-selected')
const runInput = requireEl<HTMLInputElement>('#run')
const startBtn = requireEl<HTMLButtonElement>('#start-btn')
const startLabel = requireEl<HTMLElement>('#start-btn [data-label]')
const saveBtn = requireEl<HTMLButtonElement>('#save-btn')
const endBtn = requireEl<HTMLButtonElement>('#end-btn')
const summaryEl = requireEl<HTMLElement>('#session-summary')

let auth: AuthContext = { token: '' }
let twitchAuth: TwitchExtAuthorized | null = null
let sessionActive = false
let ready = false
let busy: 'start' | 'save' | 'end' | null = null

const picker = mountGamePicker({
  input: gameInput,
  list: gameList,
  selectedEl: gameSelected,
  getAuth: () => twitchAuth,
})

function sessionGamePayload(): SessionPayload {
  const selected = picker.getSelected()
  if (!selected) {
    return { game: null, twitch_game_id: null, box_art_url: null }
  }

  return {
    game: selected.name,
    twitch_game_id: selected.twitch_id,
    box_art_url: selected.box_art_url,
  }
}

function syncActions(): void {
  setButtonBusy(startBtn, busy === 'start')
  setButtonBusy(saveBtn, busy === 'save')
  setButtonBusy(endBtn, busy === 'end')

  if (!ready || busy !== null) {
    if (busy !== 'start') startBtn.disabled = true
    if (busy !== 'save') saveBtn.disabled = true
    if (busy !== 'end') endBtn.disabled = true
    return
  }

  startBtn.disabled = false
  saveBtn.disabled = !sessionActive
  endBtn.disabled = !sessionActive
}

function paint(state: ExtState): void {
  sessionActive = Boolean(state.session)
  startLabel.textContent = sessionActive ? 'Restart session' : 'Start session'
  syncActions()

  picker.setRecents(state.recent_games ?? [])

  if (state.session) {
    runInput.value = state.session.run ?? ''
    summaryEl.textContent = `Active: ${sessionLabel(state.session)}`
    if (state.session.game) {
      picker.setSelected({
        twitch_id: state.session.twitch_game_id,
        name: state.session.game,
        box_art_url: state.session.box_art_url,
      })
    } else {
      picker.setSelected(null)
    }
  } else {
    summaryEl.textContent = 'No active session'
  }
}

async function refresh(): Promise<void> {
  const state = await fetchExtState(auth)
  ready = true
  paint(state)
  if (busy === null) {
    setStatus(statusEl, state.session ? 'Session active' : 'Ready to start a session')
  }
}

async function resolveStreamCategory(): Promise<void> {
  if (!twitchAuth) {
    return
  }

  const fromHelix = await fetchChannelCategory(twitchAuth)
  if (fromHelix) {
    const picked = {
      twitch_id: fromHelix.id,
      name: fromHelix.name,
      box_art_url: fromHelix.box_art_url,
    }
    picker.setStreamCategory(picked)
    if (!picker.getSelected()) {
      picker.setSelected(picked)
    }
    return
  }

  const fromContext = await waitForContextGame()
  if (fromContext) {
    const picked = {
      twitch_id: null,
      name: fromContext,
      box_art_url: null,
    }
    picker.setStreamCategory(picked)
    if (!picker.getSelected()) {
      picker.setSelected(picked)
    }
  }
}

function waitForContextGame(): Promise<string | null> {
  return new Promise((resolve) => {
    const twitch = window.Twitch?.ext
    if (!twitch?.onContext) {
      resolve(null)
      return
    }

    const timer = window.setTimeout(() => resolve(null), 1500)
    twitch.onContext((context) => {
      const name = context.game?.trim()
      if (name) {
        window.clearTimeout(timer)
        resolve(name)
      }
    })
  })
}

async function boot(): Promise<void> {
  try {
    twitchAuth = await resolveAuthToken()
    auth = {
      token: twitchAuth.token,
      channelId: twitchAuth.channelId,
      userId: twitchAuth.userId,
      role: 'broadcaster',
    }

    setStatus(statusEl, 'Loading…', 'loading')
    await refresh()
    void resolveStreamCategory()

    listenBroadcast(() => {
      void refresh()
    })
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Unknown error'
    setStatus(statusEl, message, true)
  }
}

formEl.addEventListener('submit', (event) => {
  event.preventDefault()
  void runAction('start', async () => {
    await startSession(auth, {
      ...sessionGamePayload(),
      run: runInput.value.trim() || undefined,
    })
  }, 'Session started')
})

saveBtn.addEventListener('click', () => {
  void runAction('save', async () => {
    await updateSession(auth, {
      ...sessionGamePayload(),
      run: runInput.value.trim() || null,
    })
  }, 'Session updated')
})

endBtn.addEventListener('click', () => {
  void runAction('end', async () => {
    await endSession(auth)
  }, 'Session ended')
})

async function runAction(
  key: 'start' | 'save' | 'end',
  work: () => Promise<void>,
  okMessage: string,
): Promise<void> {
  if (!ready || busy !== null) {
    return
  }

  busy = key
  syncActions()
  try {
    await work()
    await refresh()
    setStatus(statusEl, okMessage, 'ok')
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Unknown error'
    setStatus(statusEl, message, true)
  } finally {
    busy = null
    syncActions()
  }
}

void boot()
