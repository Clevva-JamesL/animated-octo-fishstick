/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_API_BASE_URL: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

interface TwitchExtAuthorized {
  token: string
  userId: string
  channelId: string
  clientId: string
  helixToken?: string
}

interface TwitchExt {
  onAuthorized: (callback: (auth: TwitchExtAuthorized) => void) => void
  listen: (
    target: string,
    callback: (target: string, contentType: string, message: string) => void,
  ) => void
  unlisten: (
    target: string,
    callback: (target: string, contentType: string, message: string) => void,
  ) => void
}

interface Window {
  Twitch?: {
    ext: TwitchExt
  }
}
