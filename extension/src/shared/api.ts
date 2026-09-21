const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000').replace(/\/$/, '')

export type Counts = {
  stream: number
  game: number
  run: number
}

export type CatalogGame = {
  id: number
  twitch_id: string | null
  name: string
  box_art_url: string | null
}

export type StreamSession = {
  id: number
  game_id: number | null
  twitch_game_id: string | null
  game: string | null
  box_art_url: string | null
  run: string | null
  started_at: string | null
  ended_at: string | null
  active: boolean
}

export type Death = {
  id: number
  game: string | null
  run: string | null
  note: string | null
  died_at: string | null
  clip_url: string | null
  clip_id: string | null
  category_type: string | null
  category_value: string | null
}

export type DeathCategoryGroup = {
  type: 'boss' | 'character' | string
  value: string
  count: number
  deaths: Death[]
}

export type ExtState = {
  ok: boolean
  channel: {
    id: number
    twitch_user_id: string
    allow_viewer_clips: boolean
  }
  role: string | null
  user_id: string | null
  session: StreamSession | null
  counts: Counts
  recent_games: CatalogGame[]
  recent_deaths: Death[]
  deaths: {
    stream: Death[]
    game: Death[]
    run: Death[]
  }
  categories: DeathCategoryGroup[]
}

type ApiOptions = {
  method?: string
  token: string
  body?: unknown
  channelId?: string
  role?: string
  userId?: string
}

function authHeaders(options: ApiOptions): Record<string, string> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    Authorization: `Bearer ${options.token}`,
  }

  if (options.token === 'dev') {
    headers['X-Twitch-Dev-Channel'] = options.channelId ?? 'dev-channel'
    headers['X-Twitch-Dev-Role'] = options.role ?? 'broadcaster'
    headers['X-Twitch-Dev-User'] = options.userId ?? 'dev-user'
  }

  if (options.body !== undefined) {
    headers['Content-Type'] = 'application/json'
  }

  return headers
}

async function apiFetch<T>(path: string, options: ApiOptions): Promise<T> {
  const response = await fetch(`${apiBaseUrl}${path}`, {
    method: options.method ?? 'GET',
    headers: authHeaders(options),
    body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
  })

  if (!response.ok) {
    let detail = `Request failed (${response.status})`
    try {
      const payload = (await response.json()) as {
        message?: string
        errors?: Record<string, string[]>
      }
      const firstError = payload.errors
        ? Object.values(payload.errors).flat()[0]
        : undefined
      if (typeof firstError === 'string' && firstError !== '') {
        detail = firstError
      } else if (payload.message) {
        detail = payload.message
      }
    } catch {
      // ignore JSON parse errors
    }
    throw new Error(detail)
  }

  if (response.status === 204) {
    return undefined as T
  }

  return response.json() as Promise<T>
}

export type AuthContext = {
  token: string
  channelId?: string
  role?: string
  userId?: string
}

export async function fetchExtState(auth: AuthContext): Promise<ExtState> {
  return apiFetch<ExtState>('/api/ext/state', auth)
}

export type SessionPayload = {
  game?: string | null
  run?: string | null
  twitch_game_id?: string | null
  box_art_url?: string | null
}

export async function startSession(
  auth: AuthContext,
  payload: SessionPayload,
): Promise<{ session: StreamSession; counts: Counts }> {
  return apiFetch('/api/ext/sessions', { ...auth, method: 'POST', body: payload })
}

export async function updateSession(
  auth: AuthContext,
  payload: SessionPayload,
): Promise<{ session: StreamSession; counts: Counts }> {
  return apiFetch('/api/ext/sessions/current', { ...auth, method: 'PATCH', body: payload })
}

export async function endSession(
  auth: AuthContext,
): Promise<{ session: StreamSession; counts: Counts }> {
  return apiFetch('/api/ext/sessions/current/end', { ...auth, method: 'POST' })
}

export type DeathPayload = {
  note?: string | null
  category_type?: 'boss' | 'character' | null
  category_value?: string | null
}

export async function createDeath(
  auth: AuthContext,
  payload: DeathPayload = {},
): Promise<{ death: Death; counts: Counts }> {
  return apiFetch('/api/ext/deaths', { ...auth, method: 'POST', body: payload })
}

export async function updateDeath(
  auth: AuthContext,
  deathId: number,
  payload: DeathPayload,
): Promise<{ death: Death; counts: Counts }> {
  return apiFetch(`/api/ext/deaths/${deathId}`, { ...auth, method: 'PATCH', body: payload })
}

export async function deleteDeath(
  auth: AuthContext,
  deathId: number,
): Promise<{ counts: Counts }> {
  return apiFetch(`/api/ext/deaths/${deathId}`, { ...auth, method: 'DELETE' })
}

export async function attachClip(
  auth: AuthContext,
  deathId: number,
  clipUrl: string,
): Promise<{ death: Death; counts: Counts }> {
  return apiFetch(`/api/ext/deaths/${deathId}/clip`, {
    ...auth,
    method: 'POST',
    body: { clip_url: clipUrl },
  })
}

export async function detachClip(
  auth: AuthContext,
  deathId: number,
): Promise<{ death: Death; counts: Counts }> {
  return apiFetch(`/api/ext/deaths/${deathId}/clip`, { ...auth, method: 'DELETE' })
}

export function getApiBaseUrl(): string {
  return apiBaseUrl
}
