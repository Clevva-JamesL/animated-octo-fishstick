import '../shared/styles.css'
import { createDeath, fetchExtState, type AuthContext, type ExtState } from '../shared/api'
import { listenBroadcast, resolveAuthToken, setStatus } from '../shared/twitch'
import { applyStateCounts, sessionLabel } from '../shared/ui'

function requireEl<T extends HTMLElement>(selector: string): T {
  const el = document.querySelector<T>(selector)
  if (!el) {
    throw new Error(`Missing required DOM node: ${selector}`)
  }
  return el
}

const statusEl = requireEl<HTMLElement>('#status')
const noteInput = requireEl<HTMLInputElement>('#note')
const plusOneBtn = requireEl<HTMLButtonElement>('#plus-one')
const lastDeathEl = requireEl<HTMLElement>('#last-death')

let auth: AuthContext = { token: '' }

function paint(state: ExtState): void {
  applyStateCounts(state)
  plusOneBtn.disabled = !state.session

  const latest = state.recent_deaths[0]
  if (latest) {
    const when = latest.died_at ? new Date(latest.died_at).toLocaleTimeString() : '—'
    lastDeathEl.textContent = `Last: ${latest.note?.trim() || 'Death'} · ${when}`
  } else {
    lastDeathEl.textContent = state.session
      ? `Active: ${sessionLabel(state.session)} · No deaths yet`
      : 'Start a session in Config first'
  }
}

async function refresh(): Promise<void> {
  const state = await fetchExtState(auth)
  paint(state)
  setStatus(statusEl, state.session ? 'Ready' : 'No active session')
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

plusOneBtn.addEventListener('click', () => {
  void (async () => {
    try {
      plusOneBtn.disabled = true
      const note = noteInput.value.trim()
      await createDeath(auth, note ? { note } : {})
      noteInput.value = ''
      await refresh()
      setStatus(statusEl, 'Death recorded')
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Unknown error'
      setStatus(statusEl, message, true)
      plusOneBtn.disabled = false
    }
  })()
})

void boot()
