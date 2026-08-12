const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000').replace(/\/$/, '')

export type ExtState = {
  ok: boolean
  channel_id: string | null
  role: string | null
  user_id: string | null
  message: string
}

export async function fetchExtState(token: string): Promise<ExtState> {
  const response = await fetch(`${apiBaseUrl}/api/ext/state`, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  })

  if (!response.ok) {
    throw new Error(`State request failed (${response.status})`)
  }

  return response.json() as Promise<ExtState>
}

export function getApiBaseUrl(): string {
  return apiBaseUrl
}
