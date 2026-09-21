import './overlay.css'
import { fetchExtState, type AuthContext, type ExtState } from '../shared/api'
import { listenBroadcast, resolveAuthToken } from '../shared/twitch'
import {
  applyStateCounts,
  categoryLabel,
  latestStreamDeath,
  sessionLabel,
} from '../shared/ui'

function requireEl(selector: string): HTMLElement {
  const el = document.querySelector<HTMLElement>(selector)
  if (!el) {
    throw new Error(`Missing required DOM node: ${selector}`)
  }
  return el
}

const overlayEl = requireEl('#overlay')
const headlineEl = requireEl('#headline')
const sessionLabelEl = requireEl('#session-label')
const lastDeathEl = requireEl('#last-death')
const lastDeathNoteEl = requireEl('#last-death-note')
const lastDeathSepEl = requireEl('#last-death-sep')
const lastDeathTagEl = requireEl('#last-death-tag')
const statusEl = requireEl('#status')

let auth: AuthContext = { token: '' }

function setStatus(message: string, state: 'ok' | 'error' | 'loading' | 'muted' = 'muted'): void {
  statusEl.textContent = message
  if (state === 'muted') {
    statusEl.removeAttribute('data-state')
  } else {
    statusEl.dataset.state = state
  }
  statusEl.hidden = false
  statusEl.setAttribute('aria-busy', state === 'loading' ? 'true' : 'false')
}

function clearStatus(): void {
  statusEl.hidden = true
  statusEl.textContent = ''
  statusEl.removeAttribute('data-state')
  statusEl.setAttribute('aria-busy', 'false')
}

function setOverlayLoading(loading: boolean): void {
  overlayEl.dataset.loading = loading ? 'true' : 'false'
  overlayEl.setAttribute('aria-busy', loading ? 'true' : 'false')
}

function paintHeadline(state: ExtState): void {
  if (!state.session) {
    headlineEl.hidden = true
    sessionLabelEl.textContent = ''
    lastDeathEl.hidden = true
    lastDeathNoteEl.textContent = 'Death'
    lastDeathSepEl.hidden = true
    lastDeathTagEl.hidden = true
    lastDeathTagEl.textContent = ''
    return
  }

  headlineEl.hidden = false
  sessionLabelEl.textContent = sessionLabel(state.session)

  const last = latestStreamDeath(state)
  if (!last) {
    lastDeathEl.hidden = true
    lastDeathNoteEl.textContent = 'Death'
    lastDeathSepEl.hidden = true
    lastDeathTagEl.hidden = true
    lastDeathTagEl.textContent = ''
    return
  }

  const tag = categoryLabel(last.category_type, last.category_value)
  lastDeathEl.hidden = false
  lastDeathNoteEl.textContent = last.note?.trim() || 'Death'
  lastDeathTagEl.textContent = tag ?? ''
  lastDeathTagEl.hidden = !tag
  lastDeathSepEl.hidden = !tag
}

function paint(state: ExtState): void {
  applyStateCounts(state)
  paintHeadline(state)
  setOverlayLoading(false)

  if (state.session) {
    clearStatus()
  } else {
    setStatus('Waiting for session')
  }
}

async function refresh(): Promise<void> {
  const state = await fetchExtState(auth)
  paint(state)
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

    setStatus('Loading', 'loading')
    await refresh()

    listenBroadcast(() => {
      void refresh()
    })
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Unknown error'
    setStatus(message, 'error')
    overlayEl.setAttribute('data-state', 'error')
  }
}

void boot()
