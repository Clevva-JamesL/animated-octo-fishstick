import '../shared/styles.css'
import {
  endSession,
  fetchExtState,
  startSession,
  updateSession,
  type AuthContext,
  type ExtState,
} from '../shared/api'
import { listenBroadcast, resolveAuthToken, setStatus } from '../shared/twitch'
import { sessionLabel } from '../shared/ui'

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
const runInput = requireEl<HTMLInputElement>('#run')
const startBtn = requireEl<HTMLButtonElement>('#start-btn')
const saveBtn = requireEl<HTMLButtonElement>('#save-btn')
const endBtn = requireEl<HTMLButtonElement>('#end-btn')
const summaryEl = requireEl<HTMLElement>('#session-summary')

let auth: AuthContext = { token: '' }

function paint(state: ExtState): void {
  const active = Boolean(state.session)
  saveBtn.disabled = !active
  endBtn.disabled = !active
  startBtn.textContent = active ? 'Restart session' : 'Start session'

  if (state.session) {
    gameInput.value = state.session.game ?? ''
    runInput.value = state.session.run ?? ''
    summaryEl.textContent = `Active: ${sessionLabel(state.session)}`
  } else {
    summaryEl.textContent = 'No active session'
  }
}

async function refresh(): Promise<void> {
  const state = await fetchExtState(auth)
  paint(state)
  setStatus(statusEl, state.session ? 'Session active' : 'Ready to start a session')
}

async function boot(): Promise<void> {
  try {
    const twitchAuth = await resolveAuthToken()
    auth = {
      token: twitchAuth.token,
      channelId: twitchAuth.channelId,
      userId: twitchAuth.userId,
      role: 'broadcaster',
    }

    setStatus(statusEl, 'Loading…')
    await refresh()

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
  void (async () => {
    try {
      startBtn.disabled = true
      await startSession(auth, {
        game: gameInput.value.trim() || undefined,
        run: runInput.value.trim() || undefined,
      })
      await refresh()
      setStatus(statusEl, 'Session started')
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Unknown error'
      setStatus(statusEl, message, true)
    } finally {
      startBtn.disabled = false
    }
  })()
})

saveBtn.addEventListener('click', () => {
  void (async () => {
    try {
      saveBtn.disabled = true
      await updateSession(auth, {
        game: gameInput.value.trim() || null,
        run: runInput.value.trim() || null,
      })
      await refresh()
      setStatus(statusEl, 'Session updated')
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Unknown error'
      setStatus(statusEl, message, true)
    }
  })()
})

endBtn.addEventListener('click', () => {
  void (async () => {
    try {
      endBtn.disabled = true
      await endSession(auth)
      await refresh()
      setStatus(statusEl, 'Session ended')
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Unknown error'
      setStatus(statusEl, message, true)
    }
  })()
})

void boot()
