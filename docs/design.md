# Design guidelines

Source of truth for **all visual UI** in this project: overlay, panel, config, and live config. If a colour, type size, radius, or motion is not listed here, add it before using it in CSS.

Laravel backend views are out of scope.

## How to use this doc

1. Decide the visual change here first (token, component, or motion).
2. Implement it in CSS using the token names below.
3. Do not introduce one-off hex values, font stacks, or animation timings in a single view.

CSS files that implement this spec:

| Surface | File |
|---------|------|
| Overlay HUD | `extension/src/overlay/overlay.css` |
| Panel / Config / Live Config | `extension/src/shared/styles.css` |

---

## Surfaces

| Surface | Context | Background |
|---------|---------|------------|
| **Overlay** | On top of the Twitch player | Fully transparent; only HUD chips are visible |
| **Panel / Config / Live Config** | Opaque Twitch iframe | Solid `--bg` |

Overlay is non-interactive (`pointer-events: none`). Config and live config are fully interactive.

---

## Colour

Use these tokens. Overlay may use translucent blacks for contrast on video; opaque views use `--bg`.

| Token | Value | Use |
|-------|-------|-----|
| `--bg` | `#201c2b` | Opaque page background |
| `--fg` | `#f4f2f7` | Primary text, count values |
| `--muted` | `#a89bb8` (opaque) / `rgba(244, 242, 247, 0.75)` (overlay) | Labels, secondary text, session name |
| `--accent` | `#9146ff` | Twitch purple; primary actions |
| `--error` | `#ff6b6b` | Error status |
| `--ok` | `#6bcb77` | Success status |
| `--danger-bg` | `rgba(255, 107, 107, 0.2)` | Danger button fill |
| `--danger-fg` | `#ffb4b4` | Danger button text |

### Surfaces & borders (opaque views)

| Role | Value |
|------|-------|
| Chip / card fill | `rgba(255, 255, 255, 0.03)` |
| Chip / list border | `rgba(255, 255, 255, 0.08)` |
| Input fill | `rgba(0, 0, 0, 0.25)` |
| Input / overlay chip border | `rgba(255, 255, 255, 0.12)` |
| Default button fill | `rgba(255, 255, 255, 0.1)` |
| Combobox dropdown fill | `#201c2b` (`--bg`) with `rgba(255, 255, 255, 0.12)` border |
| Combobox row hover / selected | `rgba(255, 255, 255, 0.06)` |
| Disabled | `opacity: 0.45` |

### Overlay-only fills

| Role | Value |
|------|-------|
| Session label / status pill | `rgba(0, 0, 0, 0.55)` |
| Count chip | `rgba(0, 0, 0, 0.6)` plus `backdrop-filter: blur(4px)` |
| Text on video | `text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8)` (values: `0 1px 3px rgba(0, 0, 0, 0.9)`) |

Do not use white page backgrounds. Stay dark so HUD and iframe UIs match Twitch’s dark player chrome.

---

## Typography

| Token | Value |
|-------|-------|
| Font | `"Segoe UI", Tahoma, Geneva, Verdana, sans-serif` |
| Page title (`h1`) | `1.1rem` |
| Section title (`h2`) | `0.95rem` |
| Body | `0.9rem` / line-height `1.4` |
| Status | `0.85rem` |
| Meta / timestamps | `0.75rem` |
| Count value | `1.6rem` opaque / `1.5rem` overlay, weight `700`, line-height `1.1` |
| Count label | `0.7rem` opaque / `0.6rem` overlay, uppercase, letter-spacing `0.04em`–`0.05em` |
| Overlay session label | `0.7rem` / line-height `1.3` |
| Overlay status | `0.65rem` |
| Input / button | inherit the page font; buttons `font-weight: 600` |
| Primary button | `1.1rem` |

---

## Spacing & radius

| Token | Value | Use |
|-------|-------|-----|
| Page padding (`--pad`) | `12px` | Opaque views |
| Overlay inset | `16px` from top and right | HUD position |
| Stack / form gap | `10px` |
| Chip / action gap | `8px` |
| Spinner | `12×12px`, `2px` ring | Button, status, combobox, overlay loading |
| Title → body | `8px` |
| Count chip padding | `10px 6px` opaque / `8px 10px` overlay |
| Button padding | `10px 12px` (primary: `14px 12px`) |
| Input padding | `8px 10px` |
| Chip radius | `8px` |
| Button / input / overlay pill radius | `6px` |
| Overlay HUD max width | `min(320px, 40vw)` |
| Combobox dropdown max height | `220px` |
| Combobox box-art | `28×40px`, radius `4px` |
| Combobox row padding | `8px 10px` |
| Combobox section label | `0.65rem`, uppercase, letter-spacing `0.04em` |
| Last-death card padding | `10px` |
| Disclosure summary padding | `8px 10px` |
| Death-group gap | `8px` |

---

## Motion

Count updates snap to the new number. Interactive controls may use `control-hover`.

When adding animation, define it here first, then implement. Prefer:

- Short, ease-out transitions on hover/focus for interactive controls (config / live config only).
- A brief scale or colour flash when a death count increments (overlay).
- Respect `prefers-reduced-motion: reduce` — no animation, or duration `0`.

| Name | Duration | Easing | Use |
|------|----------|--------|-----|
| `control-hover` | `120ms` | `ease-out` | Combobox row / clear-button hover (config); disclosure summary hover (panel) |
| `btn-spin` | `600ms` | `linear` infinite | Loading spinner (buttons, status, combobox, overlay) |

Do not animate the overlay into view on load (it should already be in place). Do not use looping or attention-grabbing motion on the HUD.

---

## Components

### Count chips

- Stream and Run on the **overlay**. Game is still tracked in the backend and shown on panel / live config.
- Label is uppercase muted text under a large number.
- Overlay chips sit in a horizontal row, top-right, right-aligned with the session label.

### Session label

- Overlay: compact pill above the chips (`Game · Run`). Hidden when no session; status “Waiting for session” instead.
- Panel: always visible sentence under the title.

### Buttons

| Class | Look |
|-------|------|
| default | Neutral fill, `--fg` text |
| `.primary` | `--accent` fill, white text, larger padding |
| `.danger` | `--danger-bg` / `--danger-fg` |
| `.is-loading` | Inline-flex, 8px gap; 12×12 spinner to the left of the label |

While an action is in progress, the pressed button uses `.is-loading` and `aria-busy="true"`. The spinner is a 2px `currentColor` ring with one transparent segment, rotating with `btn-spin`. Loading buttons stay at full opacity (do not use the disabled fade) and use `cursor: wait`. Sibling actions disable (faded) until the request finishes.

Every write control uses this pattern: **Start session** / **Restart session**, **Update current**, **End session**, **+1 Death**, **Save**, **Undo**. Until the first `/state` response, those buttons stay disabled (no spinner) and the status line shows loading.

Respect `prefers-reduced-motion: reduce` — spinner stays visible as a static full ring, no rotation.

### Inputs

Full width, muted label above the field, dark fill, light border.

### Combobox (config Game picker)

Search-select over an input. Opaque views only (not overlay).

- Dropdown is a bordered list under the field, same `--bg` as the page, scrollable, `z-index: 2`.
- Rows are a horizontal cluster: optional box-art, then name. Hover/keyboard highlight uses combobox row hover fill.
- Section labels (“On this stream”, “Recent”, “Search”) sit above their rows; muted, uppercase, not clickable.
- A selected game is a compact chip above the input (art + name + clear). Clear is a small default button, `aria-label="Clear game"`.
- Custom fallback row: `Use “typed name”` when Helix returns nothing.
- Searching: while a Helix query is in flight, a non-clickable row under the Search section shows a 12×12 spinner and `Searching…` (muted). The custom fallback stays available. `aria-busy="true"` on the input.
- Respect `prefers-reduced-motion: reduce` — `control-hover` duration becomes `0`.

### Last-death card (live config)

Opaque views only. Used to edit the most recent death without leaving Live Controls.

- Card fill and border match chip / list (`rgba(255, 255, 255, 0.03)` / `0.08`), radius `8px`, padding `10px`.
- Title is an `h2`. Timestamp under it uses meta type.
- Note and Clip URL are standard inputs. Clip placeholder is a Twitch clip URL.
- Actions: **Save** (default button) then **Undo** (`.danger`) in `.actions`.
- Hidden entirely when there is no last death (`hidden` on the section).

### Disclosure groups (panel)

Native `<details>` / `<summary>` for Stream, Game, and Run death lists. Opaque views only (not overlay).

- Each group is a chip-styled card (same fill, border, radius as count chips).
- Groups stack with `8px` gap.
- Summary is a horizontal row: muted triangle, group name (body, weight `600`, left), muted count on the right. Padding `8px 10px`. Cursor pointer.
- Summary hover / open uses combobox row hover fill and `control-hover`.
- Stream starts open; Game and Run start closed.
- Summary uses a muted CSS triangle (closed: pointing right; open: pointing down). Native markers are hidden so alignment matches across browsers.
- List rows inside reuse death-list type. Optional **Clip** link is `--accent`, `0.75rem`, no underline until hover.
- Respect `prefers-reduced-motion: reduce` — summary hover duration `0`.

### Status

Muted by default. `data-state="error"` → `--error`. `data-state="ok"` → `--ok`. `data-state="loading"` stays muted and shows a 12×12 spinner 8px to the left of the copy (same ring as the button spinner, `btn-spin`). Set `aria-busy="true"` while loading.

Opaque views boot with `Waiting for Twitch authorization…` then `Loading…`, both as loading. Overlay errors and loading stay in the small status pill; do not use toast/modals.

### Overlay loading

On first load, hide the count chips and show the status pill `Loading` with spinner. After `/state` arrives: session label + chips, or `Waiting for session` with no spinner. Do not animate the HUD into view.

---

## Overlay rules

- Transparent `html`/`body`; never paint a page background.
- HUD only — no death list, no forms, no Twitch-branded chrome beyond the purple accent if needed.
- Clicks must pass through to the player.
- Keep copy short; this sits on live video.

---

## Do / don’t

**Do**

- Reuse the tokens and component patterns above.
- Keep overlay contrast high enough to read on bright and dark game footage (translucent black + text shadow).
- Match Twitch purple for the main call to action.

**Don’t**

- Add a third count chip to the overlay without updating this doc.
- Introduce a second font family or a light theme for extension views.
- Use CSS animation that isn’t named in **Motion**.
- Block the centre of the player with HUD (stay top-right unless position is added as a config option here).
