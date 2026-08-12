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
