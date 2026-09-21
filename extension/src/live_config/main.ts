import '../shared/styles.css'
import {
  attachClip,
  createDeath,
  deleteDeath,
  detachClip,
  fetchExtState,
  updateDeath,
  type AuthContext,
  type Death,
  type DeathPayload,
  type ExtState,
} from '../shared/api'
import { listenBroadcast, resolveAuthToken, setStatus } from '../shared/twitch'
import { applyStateCounts, deathsFor, sessionLabel, setButtonBusy } from '../shared/ui'

function requireEl<T extends HTMLElement>(selector: string): T {
  const el = document.querySelector<T>(selector)
  if (!el) {
    throw new Error(`Missing required DOM node: ${selector}`)
  }
  return el
}

const statusEl = requireEl<HTMLElement>('#status')
const noteInput = requireEl<HTMLInputElement>('#note')
const tagType = requireEl<HTMLSelectElement>('#tag-type')
const tagValue = requireEl<HTMLInputElement>('#tag-value')
const plusOneBtn = requireEl<HTMLButtonElement>('#plus-one')
const lastCard = requireEl<HTMLElement>('#last-death-card')
const lastWhen = requireEl<HTMLElement>('#last-death-when')
const lastNote = requireEl<HTMLInputElement>('#last-note')
const lastTagType = requireEl<HTMLSelectElement>('#last-tag-type')
const lastTagValue = requireEl<HTMLInputElement>('#last-tag-value')
const lastClip = requireEl<HTMLInputElement>('#last-clip')
const saveLastBtn = requireEl<HTMLButtonElement>('#save-last')
const undoLastBtn = requireEl<HTMLButtonElement>('#undo-last')
const lastEmpty = requireEl<HTMLElement>('#last-death-empty')

let auth: AuthContext = { token: '' }
let sessionActive = false
let latest: Death | null = null
let lastPaintedId: number | null = null
let dirty = false
let mutating = false

function readTag(typeEl: HTMLSelectElement, valueEl: HTMLInputElement): DeathPayload {
  const type = typeEl.value === 'boss' || typeEl.value === 'character' ? typeEl.value : null
  const value = valueEl.value.trim() || null

  return {
    category_type: type,
    category_value: value,
  }
}

function setTagEnabled(typeEl: HTMLSelectElement, valueEl: HTMLInputElement, clearWhenOff: boolean): void {
  const enabled = typeEl.value === 'boss' || typeEl.value === 'character'
  valueEl.disabled = !enabled
  if (!enabled && clearWhenOff) {
    valueEl.value = ''
  }
}

function paintLastDeath(state: ExtState): void {
  const next = deathsFor(state, 'stream')[0] ?? null
  latest = next

  if (!next) {
    lastCard.hidden = true
    lastEmpty.hidden = false
    lastEmpty.textContent = state.session
      ? `Active: ${sessionLabel(state.session)} · No deaths yet`
      : 'Start a session in Config first'
    lastPaintedId = null
    dirty = false
    return
  }

  lastCard.hidden = false
  lastEmpty.hidden = true
  lastWhen.textContent = next.died_at
    ? new Date(next.died_at).toLocaleTimeString()
    : '—'

  if ((mutating || dirty) && next.id === lastPaintedId) {
    return
  }

  lastNote.value = next.note ?? ''
  lastTagType.value =
    next.category_type === 'boss' || next.category_type === 'character' ? next.category_type : ''
  lastTagValue.value = next.category_value ?? ''
  setTagEnabled(lastTagType, lastTagValue, false)
  lastClip.value = next.clip_url ?? ''
  lastPaintedId = next.id
  dirty = false
}

function paint(state: ExtState): void {
  sessionActive = Boolean(state.session)
  applyStateCounts(state)
  paintLastDeath(state)
  if (!mutating) {
    plusOneBtn.disabled = !sessionActive
  }
}

async function refresh(): Promise<void> {
  const state = await fetchExtState(auth)
  paint(state)
  if (!mutating) {
    setStatus(statusEl, state.session ? 'Ready' : 'No active session')
  }
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

lastNote.addEventListener('input', () => {
  dirty = true
})
lastTagType.addEventListener('change', () => {
  dirty = true
  setTagEnabled(lastTagType, lastTagValue, true)
})
lastTagValue.addEventListener('input', () => {
  dirty = true
})
lastClip.addEventListener('input', () => {
  dirty = true
})

tagType.addEventListener('change', () => {
  setTagEnabled(tagType, tagValue, true)
})

plusOneBtn.addEventListener('click', () => {
  if (mutating || !sessionActive) {
    return
  }
  void (async () => {
    mutating = true
    setButtonBusy(plusOneBtn, true)
    saveLastBtn.disabled = true
    undoLastBtn.disabled = true
    try {
      const note = noteInput.value.trim()
      await createDeath(auth, {
        ...(note ? { note } : {}),
        ...readTag(tagType, tagValue),
      })
      noteInput.value = ''
      dirty = false
      await refresh()
      setStatus(statusEl, 'Death recorded', 'ok')
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Unknown error'
      setStatus(statusEl, message, true)
    } finally {
      mutating = false
      setButtonBusy(plusOneBtn, false)
      plusOneBtn.disabled = !sessionActive
      saveLastBtn.disabled = !latest
      undoLastBtn.disabled = !latest
    }
  })()
})

saveLastBtn.addEventListener('click', () => {
  if (!latest || mutating) {
    return
  }
  void (async () => {
    if (!latest) {
      return
    }
    const deathId = latest.id
    mutating = true
    setButtonBusy(saveLastBtn, true)
    undoLastBtn.disabled = true
    plusOneBtn.disabled = true
    try {
      const note = lastNote.value.trim() || null
      const clip = lastClip.value.trim()
      await updateDeath(auth, deathId, {
        note,
        ...readTag(lastTagType, lastTagValue),
      })
      if (clip === '') {
        if (latest.clip_url) {
          await detachClip(auth, deathId)
        }
      } else if (clip !== (latest.clip_url ?? '')) {
        await attachClip(auth, deathId, clip)
      }
      dirty = false
      await refresh()
      setStatus(statusEl, 'Death updated', 'ok')
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Unknown error'
      setStatus(statusEl, message, true)
    } finally {
      mutating = false
      setButtonBusy(saveLastBtn, false)
      undoLastBtn.disabled = !latest
      plusOneBtn.disabled = !sessionActive
    }
  })()
})

undoLastBtn.addEventListener('click', () => {
  if (!latest || mutating) {
    return
  }
  void (async () => {
    if (!latest) {
      return
    }
    mutating = true
    setButtonBusy(undoLastBtn, true)
    saveLastBtn.disabled = true
    plusOneBtn.disabled = true
    try {
      await deleteDeath(auth, latest.id)
      dirty = false
      await refresh()
      setStatus(statusEl, 'Death undone', 'ok')
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Unknown error'
      setStatus(statusEl, message, true)
    } finally {
      mutating = false
      setButtonBusy(undoLastBtn, false)
      saveLastBtn.disabled = !latest
      plusOneBtn.disabled = !sessionActive
    }
  })()
})

void boot()
