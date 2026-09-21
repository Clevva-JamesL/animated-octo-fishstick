export type HelixCategory = {
  id: string
  name: string
  box_art_url: string
}

function helixHeaders(auth: TwitchExtAuthorized): HeadersInit {
  return {
    'Client-Id': auth.clientId,
    Authorization: `Extension ${auth.helixToken ?? ''}`,
  }
}

export function sizedBoxArt(url: string, width = 28, height = 40): string {
  return url.replaceAll('{width}', String(width)).replaceAll('{height}', String(height))
}

export function twitchBoxArtUrl(twitchId: string): string {
  return `https://static-cdn.jtvnw.net/ttv-boxart/${encodeURIComponent(twitchId)}-{width}x{height}.jpg`
}

export async function searchCategories(
  auth: TwitchExtAuthorized,
  query: string,
): Promise<HelixCategory[]> {
  if (!auth.helixToken || query.trim() === '') {
    return []
  }

  const url = new URL('https://api.twitch.tv/helix/search/categories')
  url.searchParams.set('query', query.trim())
  url.searchParams.set('first', '8')

  const response = await fetch(url, { headers: helixHeaders(auth) })
  if (!response.ok) {
    return []
  }

  const body = (await response.json()) as { data?: HelixCategory[] }
  return body.data ?? []
}

export async function fetchChannelCategory(auth: TwitchExtAuthorized): Promise<HelixCategory | null> {
  if (!auth.helixToken) {
    return null
  }

  const url = new URL('https://api.twitch.tv/helix/channels')
  url.searchParams.set('broadcaster_id', auth.channelId)

  const response = await fetch(url, { headers: helixHeaders(auth) })
  if (!response.ok) {
    return null
  }

  const body = (await response.json()) as {
    data?: Array<{ game_id?: string; game_name?: string }>
  }
  const channel = body.data?.[0]
  const gameId = channel?.game_id?.trim()
  const gameName = channel?.game_name?.trim()

  if (!gameId || gameId === '0' || !gameName) {
    return null
  }

  return {
    id: gameId,
    name: gameName,
    box_art_url: twitchBoxArtUrl(gameId),
  }
}
