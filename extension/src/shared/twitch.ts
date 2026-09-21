export function waitForTwitchAuth(): Promise<TwitchExtAuthorized> {
  return new Promise((resolve, reject) => {
    const twitch = window.Twitch?.ext

    if (!twitch) {
      reject(new Error('Twitch Extension Helper is not available'))
      return
    }

    twitch.onAuthorized((auth) => {
      resolve(auth)
    })
  })
}

export type StatusState = 'muted' | 'ok' | 'error' | 'loading'

export function setStatus(
  element: HTMLElement,
  message: string,
  state: boolean | StatusState = 'muted',
): void {
  element.textContent = message

  const resolved: StatusState =
    state === true ? 'error' : state === false ? 'muted' : state

  if (resolved === 'muted') {
    element.removeAttribute('data-state')
  } else {
    element.dataset.state = resolved
  }

  element.setAttribute('aria-busy', resolved === 'loading' ? 'true' : 'false')
}

export function listenBroadcast(onMessage: (payload: unknown) => void): () => void {
  const twitch = window.Twitch?.ext

  if (!twitch) {
    return () => undefined
  }

  const handler = (_target: string, _contentType: string, message: string) => {
    try {
      onMessage(JSON.parse(message) as unknown)
    } catch {
      // ignore malformed pubsub payloads
    }
  }

  twitch.listen('broadcast', handler)

  return () => {
    twitch.unlisten('broadcast', handler)
  }
}

/**
 * Prefer ?dev=1 for local browser testing — the Helper script is always loaded
 * on our pages, but outside a Twitch iframe it does not provide a usable JWT.
 */
export function resolveAuthToken(): Promise<TwitchExtAuthorized> {
  const params = new URLSearchParams(window.location.search)
  if (params.get('dev') === '1') {
    return Promise.resolve({
      token: 'dev',
      userId: params.get('user') ?? 'dev-user',
      channelId: params.get('channel') ?? 'dev-channel',
      clientId: 'dev-client',
    })
  }

  if (window.Twitch?.ext) {
    return waitForTwitchAuth()
  }

  return Promise.reject(new Error('Twitch Extension Helper is not available'))
}
