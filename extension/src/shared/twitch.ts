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

export function setStatus(element: HTMLElement, message: string, isError = false): void {
  element.textContent = message
  element.dataset.state = isError ? 'error' : 'ok'
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

/** Local / Developer Rig fallback when Twitch Helper is absent. */
export function resolveAuthToken(): Promise<TwitchExtAuthorized> {
  if (window.Twitch?.ext) {
    return waitForTwitchAuth()
  }

  const params = new URLSearchParams(window.location.search)
  if (params.get('dev') === '1') {
    return Promise.resolve({
      token: 'dev',
      userId: params.get('user') ?? 'dev-user',
      channelId: params.get('channel') ?? 'dev-channel',
      clientId: 'dev-client',
    })
  }

  return Promise.reject(new Error('Twitch Extension Helper is not available'))
}
