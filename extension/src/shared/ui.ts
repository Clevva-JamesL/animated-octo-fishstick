import type { Counts, Death, DeathCategoryGroup, ExtState, StreamSession } from './api'

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

export function categoryLabel(type: string | null | undefined, value: string | null | undefined): string | null {
  const name = value?.trim()
  if (!type || !name) {
    return null
  }

  if (type === 'character') {
    return `Character · ${name}`
  }

  if (type === 'boss') {
    return `Boss · ${name}`
  }

  return `${type} · ${name}`
}

export function deathsFor(
  state: ExtState,
  bucket: 'stream' | 'game' | 'run',
): Death[] {
  return state.deaths?.[bucket] ?? (bucket === 'stream' ? state.recent_deaths : [])
}

export function latestStreamDeath(state: ExtState): Death | null {
  return deathsFor(state, 'stream')[0] ?? null
}

export function renderDeathList(
  listEl: HTMLElement,
  deaths: Death[],
  options: { showTag?: boolean } = {},
): void {
  const showTag = options.showTag !== false
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

    item.append(note)

    if (showTag) {
      const tag = categoryLabel(death.category_type, death.category_value)
      if (tag) {
        const tagEl = document.createElement('div')
        tagEl.className = 'death-tag'
        tagEl.textContent = tag
        item.append(tagEl)
      }
    }

    const when = document.createElement('div')
    when.className = 'when'
    when.textContent = death.died_at ? new Date(death.died_at).toLocaleTimeString() : '—'
    item.append(when)

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

export function renderCategoryGroups(container: HTMLElement, groups: DeathCategoryGroup[]): void {
  container.replaceChildren()
  container.hidden = groups.length === 0

  for (const group of groups) {
    const details = document.createElement('details')
    details.className = 'death-group'

    const summary = document.createElement('summary')
    const name = document.createElement('span')
    name.className = 'death-group-name'
    name.textContent = categoryLabel(group.type, group.value) ?? group.value
    const count = document.createElement('span')
    count.className = 'death-group-count'
    count.textContent = String(group.count)
    summary.append(name, count)

    const list = document.createElement('ul')
    renderDeathList(list, group.deaths, { showTag: false })

    details.append(summary, list)
    container.append(details)
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
