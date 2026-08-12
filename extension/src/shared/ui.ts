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
    const when = death.died_at ? new Date(death.died_at).toLocaleTimeString() : '—'
    const note = death.note?.trim() || 'Death'
    item.innerHTML = `<div>${escapeHtml(note)}</div><div class="when">${escapeHtml(when)}</div>`
    listEl.append(item)
  }
}

export function applyStateCounts(state: Pick<ExtState, 'counts' | 'session'>): void {
  renderCounts(state.counts)
}

function escapeHtml(value: string): string {
  return value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
}
