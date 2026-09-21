import type { Counts, Death, ExtState, StreamSession } from './api'

export function renderCounts(counts: Counts): void {
  const stream = document.querySelector('#count-stream')
  const game = document.querySelector('#count-game')
  const run = document.querySelector('#count-run')

  if (stream) stream.textContent = String(counts.stream)
  if (game) game.textContent = String(counts.game)
  if (run) run.textContent = String(counts.run)
}

export function sessionLabel(session: StreamSession | null): string {
  if (!session) {
    return 'No active session'
  }

  const game = session.game?.trim() || 'Unset game'
  const run = session.run?.trim()

  return run ? `${game} · ${run}` : game
}

export function deathsFor(
  state: ExtState,
  bucket: 'stream' | 'game' | 'run',
): Death[] {
  return state.deaths?.[bucket] ?? (bucket === 'stream' ? state.recent_deaths : [])
}

export function renderDeathList(listEl: HTMLElement, deaths: Death[]): void {
  listEl.replaceChildren()

  if (deaths.length === 0) {
    const empty = document.createElement('li')
    empty.textContent = 'No deaths recorded yet.'
    listEl.append(empty)
    return
  }

  for (const death of deaths) {
    const item = document.createElement('li')
    const note = document.createElement('div')
    note.textContent = death.note?.trim() || 'Death'

    const when = document.createElement('div')
    when.className = 'when'
    when.textContent = death.died_at ? new Date(death.died_at).toLocaleTimeString() : '—'

    item.append(note, when)

    if (death.clip_url) {
      const clip = document.createElement('a')
      clip.className = 'death-clip'
      clip.href = death.clip_url
      clip.target = '_blank'
      clip.rel = 'noopener noreferrer'
      clip.textContent = 'Clip'
      item.append(clip)
    }

    listEl.append(item)
  }
}

export function applyStateCounts(state: Pick<ExtState, 'counts' | 'session'>): void {
  renderCounts(state.counts)
}

export function setButtonBusy(button: HTMLButtonElement, busy: boolean): void {
  button.classList.toggle('is-loading', busy)
  button.setAttribute('aria-busy', busy ? 'true' : 'false')
  button.disabled = busy
}
