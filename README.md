# Death Counter (Twitch Extension)

Twitch Extension death counter — Laravel EBS + Vite extension frontend + Supabase Postgres.

## Layout

- `backend/` — Laravel API (Extension Backend Service)
- `extension/` — Panel / Config / Live Config (Vite multi-page)

## Prerequisites

- PHP 8.4+, Composer, Node/npm
- Supabase project (Postgres connection details)
- Twitch Extension Client ID + Secret (when testing against Twitch)
- ngrok (for a public HTTPS EBS URL during local MVP)

## Backend setup

```bash
cd backend
cp .env.example .env   # if needed; key may already exist
# Fill DB_* from Supabase → Project Settings → Database
# Fill TWITCH_EXTENSION_* from Twitch Developer Console
php artisan serve --host=127.0.0.1 --port=8000
```

Health check: `GET /up`  
Extension state (dev auth): 

```bash
curl -s http://127.0.0.1:8000/api/ext/state \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer dev' \
  -H 'X-Twitch-Dev-Channel: 12345' \
  -H 'X-Twitch-Dev-Role: broadcaster'
```

`TWITCH_ALLOW_DEV_AUTH=true` only works when `APP_ENV=local`. Prefer real Extension JWTs once the Twitch Helper is wired.

### ngrok

With Laravel on port 8000:

```bash
ngrok http 8000
```

Set `APP_URL` to the ngrok HTTPS URL, and set `extension/.env` `VITE_API_BASE_URL` to the same host. Rebuild or restart Vite after changing it.

Laravel trusts proxies (`TrustProxies`) so HTTPS behind ngrok is handled correctly. CORS patterns allow Twitch extension origins, localhost, and ngrok hosts.

## Extension setup

```bash
cd extension
cp .env.example .env
# VITE_API_BASE_URL=http://localhost:8000  (or your ngrok URL)
npm install
npm run dev      # local Vite
npm run build    # outputs extension/dist for Twitch upload / Local Test Base URI
```

### Twitch Developer Console (Local Test)

| Field | Value |
|-------|--------|
| Panel Viewer Path | `viewer.html` |
| Config Path | `config.html` |
| Live Config Path | `live_config.html` |
| Base URI | Vite or static `dist` HTTPS URL (trailing `/`) |
| Type | Panel (enable Config / Live Config as needed) |

Asset paths use `base: './'` so builds work on Twitch’s CDN path layout.

## Current iteration

**Iteration 1 (core counters):** Channel / StreamSession / Death models on Supabase; start/update/end session; +1 death; panel + config + live config UIs; PubSub broadcast on writes (when Twitch credentials are set).

### Quick API test (local dev auth)

```bash
cd backend && php artisan serve
# another terminal:
curl -s http://127.0.0.1:8000/api/ext/state \
  -H 'Authorization: Bearer dev' \
  -H 'X-Twitch-Dev-Channel: 12345' \
  -H 'X-Twitch-Dev-Role: broadcaster'
```

Browser UI without Twitch Helper: open Vite pages with `?dev=1&channel=12345`.

Next: clip linking + expandable category lists.
