import '../shared/styles.css'
import { fetchExtState, getApiBaseUrl } from '../shared/api'
import { setStatus, waitForTwitchAuth } from '../shared/twitch'

function requireEl(selector: string): HTMLElement {
  const el = document.querySelector<HTMLElement>(selector)
  if (!el) {
    throw new Error(`Missing required DOM node: ${selector}`)
  }
  return el
}

const statusEl = requireEl('#status')
const metaEl = requireEl('#meta')

async function boot(): Promise<void> {
  try {
    const auth = await waitForTwitchAuth()
    setStatus(statusEl, 'Authorized. Fetching extension state…')

    const state = await fetchExtState(auth.token)
    setStatus(statusEl, `Config ready — ${state.message}`)
    metaEl.textContent = [
      `API: ${getApiBaseUrl()}`,
      `channel: ${state.channel_id ?? auth.channelId}`,
      `role: ${state.role ?? 'unknown'}`,
    ].join('\n')
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Unknown error'
    setStatus(statusEl, message, true)
  }
}

void boot()
