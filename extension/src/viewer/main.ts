import '../shared/styles.css'
import { fetchExtState, type AuthContext, type ExtState } from '../shared/api'
import { listenBroadcast, resolveAuthToken, setStatus } from '../shared/twitch'
import { applyStateCounts, renderDeathList, sessionLabel } from '../shared/ui'

function requireEl(selector: string): HTMLElement {
  const el = document.querySelector<HTMLElement>(selector)
  if (!el) {
    throw new Error(`Missing required DOM node: ${selector}`)
  }
  return el
}

const statusEl = requireEl('#status')
const sessionLabelEl = requireEl('#session-label')
const deathListEl = requireEl('#death-list')

let auth: AuthContext = { token: '' }

function paint(state: ExtState): void {
  applyStateCounts(state)
  sessionLabelEl.textContent = sessionLabel(state.session)
  renderDeathList(deathListEl, state.recent_deaths)
}

async function refresh(): Promise<void> {
  const state = await fetchExtState(auth)
  paint(state)
  setStatus(statusEl, state.session ? 'Live' : 'Waiting for streamer to start a session')
}

async function boot(): Promise<void> {
  try {
    const twitchAuth = await resolveAuthToken()
    auth = {
      token: twitchAuth.token,
      channelId: twitchAuth.channelId,
      userId: twitchAuth.userId,
      role: 'viewer',
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

void boot()
