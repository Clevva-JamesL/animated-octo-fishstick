import '../shared/styles.css'
import { fetchExtState, type AuthContext, type ExtState } from '../shared/api'
import { listenBroadcast, resolveAuthToken, setStatus } from '../shared/twitch'
import { applyStateCounts, deathsFor, renderCategoryGroups, renderDeathList, sessionLabel } from '../shared/ui'

function requireEl(selector: string): HTMLElement {
  const el = document.querySelector<HTMLElement>(selector)
  if (!el) {
    throw new Error(`Missing required DOM node: ${selector}`)
  }
  return el
}

const statusEl = requireEl('#status')
const sessionLabelEl = requireEl('#session-label')
const streamList = requireEl('#death-list-stream')
const gameList = requireEl('#death-list-game')
const runList = requireEl('#death-list-run')
const streamCount = requireEl('#count-list-stream')
const gameCount = requireEl('#count-list-game')
const runCount = requireEl('#count-list-run')
const tagGroups = requireEl('#death-groups-tags')

let auth: AuthContext = { token: '' }

function paintGroup(
  listEl: HTMLElement,
  countEl: HTMLElement,
  deaths: ReturnType<typeof deathsFor>,
  total: number,
): void {
  countEl.textContent = String(total)
  renderDeathList(listEl, deaths)
}

function paint(state: ExtState): void {
  applyStateCounts(state)
  sessionLabelEl.textContent = sessionLabel(state.session)
  paintGroup(streamList, streamCount, deathsFor(state, 'stream'), state.counts.stream)
  paintGroup(gameList, gameCount, deathsFor(state, 'game'), state.counts.game)
  paintGroup(runList, runCount, deathsFor(state, 'run'), state.counts.run)
  renderCategoryGroups(tagGroups, state.categories ?? [])
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

    setStatus(statusEl, 'Loading…', 'loading')
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
