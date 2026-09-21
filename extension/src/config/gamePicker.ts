import type { CatalogGame } from '../shared/api'
import { searchCategories, sizedBoxArt, type HelixCategory } from '../shared/helix'

export type PickedGame = {
  twitch_id: string | null
  name: string
  box_art_url: string | null
}

type ListOption = {
  id: string
  label: string
  game: PickedGame
}

function toPicked(game: CatalogGame): PickedGame {
  return {
    twitch_id: game.twitch_id,
    name: game.name,
    box_art_url: game.box_art_url,
  }
}

function fromHelix(category: HelixCategory): PickedGame {
  return {
    twitch_id: category.id,
    name: category.name,
    box_art_url: category.box_art_url,
  }
}

function sameGame(a: PickedGame, b: PickedGame): boolean {
  if (a.twitch_id && b.twitch_id) {
    return a.twitch_id === b.twitch_id
  }
  return a.twitch_id === b.twitch_id && a.name === b.name
}

export function mountGamePicker(options: {
  input: HTMLInputElement
  list: HTMLElement
  selectedEl: HTMLElement
  getAuth: () => TwitchExtAuthorized | null
}): {
  getSelected: () => PickedGame | null
  setSelected: (game: PickedGame | null) => void
  setRecents: (games: CatalogGame[]) => void
  setStreamCategory: (game: PickedGame | null) => void
} {
  let selected: PickedGame | null = null
  let recents: CatalogGame[] = []
  let streamCategory: PickedGame | null = null
  let searchHits: HelixCategory[] = []
  let debounce: number | undefined
  let requestId = 0
  let activeIndex = 0
  let open = false
  let searching = false

  function getSelected(): PickedGame | null {
    return selected
  }

  function setSelected(game: PickedGame | null): void {
    selected = game
    paintSelected()
  }

  function setRecents(games: CatalogGame[]): void {
    recents = games
    if (open) {
      paintList()
    }
  }

  function setStreamCategory(game: PickedGame | null): void {
    streamCategory = game
    if (open) {
      paintList()
    }
  }

  function paintSelected(): void {
    const nameEl = options.selectedEl.querySelector('.combobox-name')
    const artEl = options.selectedEl.querySelector<HTMLImageElement>('.combobox-art')
    if (!nameEl || !artEl) {
      return
    }

    if (!selected) {
      options.selectedEl.hidden = true
      nameEl.textContent = ''
      artEl.hidden = true
      artEl.removeAttribute('src')
      return
    }

    options.selectedEl.hidden = false
    nameEl.textContent = selected.name
    if (selected.box_art_url) {
      artEl.hidden = false
      artEl.src = sizedBoxArt(selected.box_art_url)
      artEl.alt = ''
    } else {
      artEl.hidden = true
      artEl.removeAttribute('src')
    }
  }

  function visibleOptions(): ListOption[] {
    const query = options.input.value.trim()
    const rows: ListOption[] = []

    if (query === '') {
      if (streamCategory) {
        rows.push({ id: 'stream', label: streamCategory.name, game: streamCategory })
      }
      for (const recent of recents) {
        const game = toPicked(recent)
        if (streamCategory && sameGame(game, streamCategory)) {
          continue
        }
        rows.push({ id: `recent-${recent.id}`, label: recent.name, game })
      }
      return rows
    }

    for (const hit of searchHits) {
      rows.push({ id: `search-${hit.id}`, label: hit.name, game: fromHelix(hit) })
    }

    const already = rows.some((row) => row.label.toLowerCase() === query.toLowerCase())
    if (!already) {
      rows.push({
        id: 'custom',
        label: `Use “${query}”`,
        game: { twitch_id: null, name: query, box_art_url: null },
      })
    }

    return rows
  }

  function paintList(): void {
    const query = options.input.value.trim()
    const rows = visibleOptions()
    options.list.replaceChildren()

    const showSearching = open && searching && query !== ''
    if (!open || (rows.length === 0 && !showSearching)) {
      options.list.hidden = true
      options.input.setAttribute('aria-expanded', 'false')
      return
    }

    if (activeIndex >= rows.length) {
      activeIndex = Math.max(0, rows.length - 1)
    }

    if (showSearching) {
      appendSection('Search')
      appendSearching()
    }

    rows.forEach((row, index) => {
      const previous = rows[index - 1]
      if (query === '' && row.id === 'stream') {
        appendSection('On this stream')
      } else if (query === '' && row.id.startsWith('recent-') && !previous?.id.startsWith('recent-')) {
        appendSection('Recent')
      } else if (query !== '' && row.id.startsWith('search-') && !previous?.id.startsWith('search-') && !searching) {
        appendSection('Search')
      }

      const item = document.createElement('li')
      item.id = `game-option-${row.id}`
      item.className = 'combobox-option'
      item.setAttribute('role', 'option')
      item.setAttribute('aria-selected', index === activeIndex ? 'true' : 'false')

      if (row.game.box_art_url) {
        const img = document.createElement('img')
        img.className = 'combobox-art'
        img.src = sizedBoxArt(row.game.box_art_url)
        img.alt = ''
        item.append(img)
      }

      const label = document.createElement('span')
      label.textContent = row.label
      item.append(label)

      item.addEventListener('mousedown', (event) => {
        event.preventDefault()
        choose(row.game)
      })

      options.list.append(item)
    })

    options.list.hidden = false
    options.input.setAttribute('aria-expanded', 'true')
    const active = rows[activeIndex]
    options.input.setAttribute('aria-activedescendant', active ? `game-option-${active.id}` : '')
  }

  function appendSection(label: string): void {
    const section = document.createElement('li')
    section.className = 'combobox-section'
    section.textContent = label
    options.list.append(section)
  }

  function appendSearching(): void {
    const item = document.createElement('li')
    item.className = 'combobox-searching'
    item.setAttribute('role', 'status')
    const spin = document.createElement('span')
    spin.className = 'spinner'
    spin.setAttribute('aria-hidden', 'true')
    const label = document.createElement('span')
    label.textContent = 'Searching…'
    item.append(spin, label)
    options.list.append(item)
  }

  function choose(game: PickedGame): void {
    selected = game
    options.input.value = ''
    open = false
    paintSelected()
    paintList()
    options.input.blur()
  }

  function scheduleSearch(): void {
    window.clearTimeout(debounce)
    const query = options.input.value.trim()
    const auth = options.getAuth()
    const id = ++requestId

    if (!auth?.helixToken || query === '') {
      searching = false
      options.input.setAttribute('aria-busy', 'false')
      searchHits = []
      paintList()
      return
    }

    searching = true
    options.input.setAttribute('aria-busy', 'true')
    searchHits = []
    paintList()

    debounce = window.setTimeout(() => {
      void (async () => {
        try {
          const hits = await searchCategories(auth, query)
          if (id !== requestId) {
            return
          }
          searchHits = hits
        } catch {
          if (id !== requestId) {
            return
          }
          searchHits = []
        } finally {
          if (id === requestId) {
            searching = false
            options.input.setAttribute('aria-busy', 'false')
            paintList()
          }
        }
      })()
    }, 200)
  }

  options.input.addEventListener('focus', () => {
    open = true
    paintList()
  })

  options.input.addEventListener('click', () => {
    open = true
    paintList()
  })

  options.input.addEventListener('input', () => {
    open = true
    scheduleSearch()
  })

  options.input.addEventListener('keydown', (event) => {
    const rows = visibleOptions()
    if (event.key === 'ArrowDown') {
      event.preventDefault()
      open = true
      activeIndex = Math.min(rows.length - 1, activeIndex + 1)
      paintList()
    } else if (event.key === 'ArrowUp') {
      event.preventDefault()
      open = true
      activeIndex = Math.max(0, activeIndex - 1)
      paintList()
    } else if (event.key === 'Enter' && open && rows[activeIndex]) {
      event.preventDefault()
      choose(rows[activeIndex].game)
    } else if (event.key === 'Escape') {
      open = false
      paintList()
    }
  })

  options.input.addEventListener('blur', () => {
    window.setTimeout(() => {
      open = false
      paintList()
    }, 120)
  })

  options.selectedEl.querySelector('.combobox-clear')?.addEventListener('click', () => {
    selected = null
    options.input.value = ''
    paintSelected()
  })

  paintSelected()

  return { getSelected, setSelected, setRecents, setStreamCategory }
}
