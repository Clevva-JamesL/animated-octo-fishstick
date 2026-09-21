# Video Overlay

The death counter can be shown **on top of the Twitch video player** as a Video Overlay extension view. Viewers see counts in the corner of the stream without opening the panel below the player.

This is different from:

- **Panel** (`viewer.html`) — docked below the video; full UI with recent deaths.
- **OBS browser source** — baked into the broadcast; not covered here.

## What was added

| File | Purpose |
|------|---------|
| `extension/overlay.html` | Overlay entry page (Twitch Video Overlay path) |
| `extension/src/overlay/main.ts` | Fetches state, listens to PubSub, updates counts |
| `extension/src/overlay/overlay.css` | Transparent HUD styling (top-right corner) |

The overlay reuses existing shared modules:

- `src/shared/api.ts` — `fetchExtState`
- `src/shared/twitch.ts` — `resolveAuthToken`, `listenBroadcast`
- `src/shared/ui.ts` — `applyStateCounts`, `sessionLabel`

No backend changes are required. The overlay is read-only and uses the same `/api/ext/state` endpoint and PubSub broadcasts as the panel.

## Twitch Developer Console changes

Enable **Video Overlay** on the extension (you can disable Panel if you no longer want it).

| Field | Value |
|-------|--------|
| Video Overlay Path | `overlay.html` |
| Config Path | `config.html` |
| Live Config Path | `live_config.html` |
| Base URI | Vite dev URL or `dist/` HTTPS URL (trailing `/`) |

If switching away from Panel entirely, you can leave Panel Viewer Path blank or remove the Panel capability.

## Build

```bash
cd extension
npm run build
```

`dist/overlay.html` is included in the build output alongside the other pages.

## Testing

### 1. Local UI (no Twitch)

Useful for layout and API wiring. Does **not** validate transparency on Twitch’s player.

```bash
# Terminal 1 — backend
cd backend && php artisan serve

# Terminal 2 — extension
cd extension && npm run dev
```

Open:

```
http://127.0.0.1:5173/overlay.html?dev=1&channel=12345
```

Start a session and add deaths via Live Config (`live_config.html?dev=1&channel=12345`) or API:

```bash
curl -s -X POST http://127.0.0.1:8000/api/ext/sessions \
  -H 'Authorization: Bearer dev' \
  -H 'X-Twitch-Dev-Channel: 12345' \
  -H 'X-Twitch-Dev-Role: broadcaster' \
  -H 'Content-Type: application/json' \
  -d '{"game":"Test Game","run":"Run 1"}'

curl -s -X POST http://127.0.0.1:8000/api/ext/deaths \
  -H 'Authorization: Bearer dev' \
  -H 'X-Twitch-Dev-Channel: 12345' \
  -H 'X-Twitch-Dev-Role: broadcaster' \
  -H 'Content-Type: application/json' \
  -d '{"note":"Test death"}'
```

Reload the overlay page to confirm counts update. PubSub does not run in `?dev=1` mode; use Live Config or manual refresh to verify count changes locally.

### 2. Twitch Extension Local Test

Requires a public HTTPS URL for the EBS and extension assets.

1. **Backend** — run Laravel and expose via ngrok:
   ```bash
   cd backend && php artisan serve
   ngrok http 8000
   ```
   Set `APP_URL` in `backend/.env` to the ngrok HTTPS URL.

2. **Extension** — point API at ngrok and expose Vite:
   ```bash
   cd extension
   # extension/.env
   # VITE_API_BASE_URL=https://<your-ngrok>.ngrok-free.app
   npm run dev
   ngrok http 5173
   ```

3. **Twitch Developer Console**
   - Base URI: `https://<vite-ngrok>/` (trailing slash)
   - Video Overlay Path: `overlay.html`
   - Extension Backend Service (EBS): your Laravel ngrok URL
   - Enable Config + Live Config for session/death controls

4. **Extension Manager** on your channel — activate the extension and enable the overlay view.

5. **Watch your channel** as a viewer (or use the Developer Rig). The counter should appear top-right on the video player when a session is active.

6. **Live updates** — use Live Config to +1 death; overlay should update via PubSub without a page reload (requires `TWITCH_EXTENSION_*` credentials on the backend).

### 3. Hosted / production test

1. `npm run build` in `extension/`
2. Zip `extension/dist/` and upload to Twitch (or use Hosted Test)
3. Set Base URI to the hosted asset URL
4. Confirm EBS URL and Twitch credentials in production `backend/.env`

## Overlay behavior notes

- **Transparent background** — only the HUD chips are visible; the rest of the iframe is clear.
- **Non-interactive** — `pointer-events: none` so clicks pass through to the player.
- **Minimal UI** — stream / run counts plus optional session label; no death list. Game count is still tracked in the backend and shown on the panel. Visual tokens live in [design.md](design.md).
- **Not in VOD/OBS output** — the overlay is a Twitch client layer for viewers on twitch.tv, not encoded into the stream.

## Optional follow-ups

- Config toggle for overlay position (top-left vs top-right)
- Show only one count (e.g. run) via extension configuration
- Separate OBS browser source page for stream-embedded counters
