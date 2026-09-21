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
| Base palette (shared by every view) | `extension/src/shared/tokens.css` |
| Overlay HUD | `extension/src/overlay/overlay.css` |
| Panel / Config / Live Config | `extension/src/shared/styles.css` |

Both stylesheets `@import` the token file, so a palette change is made once.

---

## Surfaces

| Surface | Context | Background |
|---------|---------|------------|
| **Overlay** | On top of the Twitch player | Fully transparent; only HUD chips are visible |
| **Panel / Config / Live Config** | Opaque Twitch iframe | Solid `--bg` (outer), cards and controls sit on `--surface` (inner) |

Overlay is non-interactive (`pointer-events: none`). Config and live config are fully interactive.

---

## Colour

The scheme is derived from the lavender panel in the channel’s “About me” banner. The bird illustration is deliberately excluded: its olive, tan, and red hues are character artwork, not interface colours. The panel’s deep violet, lavender gradient, and white lettering define the UI; values are adjusted only where small-text contrast requires it.

### Changing the palette

Each base colour is declared once in `tokens.css`, as a space-separated channel triple rather than a hex value so derived tokens can add alpha with `rgb(var(--x-rgb) / a)`.

| Role | Variable | Current |
|------|----------|---------|
| Outer background | `--bg-rgb` | `155 149 184` (`#9b95b8`) |
| Inner background | `--surface-rgb` | `202 196 222` (`#cac4de`) |
| Text | `--fg-rgb` | `48 42 66` (`#302a42`) |
| Text highlight | `--highlight-rgb` | `255 255 255` (`#ffffff`) |
| Error | `--error-rgb` | `66 9 14` (`#42090e`) |

Edit one triple and every surface follows: borders, muted text, input tints, hover states, overlay pills, and the count-chip halo. Re-check contrast afterwards (see below) and update this table.

### Tokens

| Token | Value | Use |
|-------|-------|-----|
| `--bg` | `#9b95b8` | Outer background: mid-lavender extrapolated from the panel gradient |
| `--surface` | `#cac4de` | Inner background: the panel’s light lavender |
| `--fg` | `#302a42` | Primary text, darkened from the illustrated lettering for accessibility |
| `--fg-highlight` | `#ffffff` | White lettering and text on saturated fills |
| `--muted` | text at 85% | Secondary text **on `--surface` only** |
| `--accent` | `#9146ff` | Twitch purple; primary button fills |
| `--accent-text` | `#5c16c5` | Darker Twitch purple for text (clip links) |
| `--error` | `#42090e` | Error status |
| `--ok` | `#082014` | Success status |
| `--danger-bg` | error at 16% | Danger button fill |
| `--danger-fg` | `--error` | Danger button text |

`--accent` and `--accent-text` are Twitch brand purple (fill vs text). `--ok` remains a semantic status hue. Surfaces stay on the banner palette.

Text is dark on light, so `--fg` (not `--fg-highlight`) is the default. Reach for `--fg-highlight` only where the fill is saturated enough that dark text fails.

### Contrast rules

The backgrounds are mid-tone, so text colour is constrained. Every value above clears WCAG AA (4.5:1) for small text against both `--bg` and `--surface`, with two deliberate exceptions:

- `--fg` clears AA on `--bg` (4.8:1) and `--surface` (8.1:1).
- `--muted` only clears AA on `--surface` (5.8:1); on `--bg` it is 3.8:1. Use it inside cards, chips, and dropdowns. Small secondary text sitting directly on the page (`p`, `label`, `#status`, `.meta`) uses `--fg` and takes its hierarchy from size and weight instead.
- `--accent` is a fill, never small text. White on `--accent` is 4.6:1. For purple text use `--accent-text`, which reaches 5.2:1 on `--surface`.

Before adding a colour, check it against both backgrounds rather than eyeballing it.

### Surfaces & borders (opaque views)

Two derived alpha tokens carry the borders and light control fills.

| Token | Value | Use |
|-------|-------|-----|
| `--line` | text at 22% | Chip / card / list borders |
| `--line-strong` | text at 35% | Input, dropdown, overlay chip borders |
| `--tint` | highlight at 55% | Input and default button fill |
| `--tint-hover` | highlight at 35% | Hover / selected row and summary fill |

| Role | Value |
|------|-------|
| Chip / card fill | `--surface` |
| Chip / list border | `--line` |
| Input fill | `--tint` with `--line-strong` border |
| Default button fill | `--tint` |
| Combobox dropdown fill | `--surface` with `--line-strong` border |
| Combobox row hover / selected | `--tint-hover` |
| Combobox box-art placeholder | `--bg` |
| Disabled | `opacity: 0.45` |

### Overlay-only fills

| Token | Value | Use |
|-------|-------|-----|
| `--pill` | inner background at 90% | Header and status pill fill |
| `--chip` | inner background at 95% | Count chip fill, plus `backdrop-filter: blur(4px)` and a `--line-strong` border |
| `--halo` | `0 1px 2px` highlight at 70% | `text-shadow` for labels and header text on video |
| `--halo-strong` | `0 1px 3px` highlight at 80% | `text-shadow` for count values |

Overlay fills stay near-opaque so dark HUD text keeps its contrast over bright and dark footage alike. `--muted` is safe here because it always sits on a pill, never on raw video.

The HUD reads as dark text on a light lilac pill, so the halo behind text is light, not black.

Keep page backgrounds on `--bg`; do not paint plain white or revert individual views to the old dark chrome.

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
| Overlay header session | `0.7rem` / line-height `1.3` |
| Overlay header last death / status | `0.65rem` / line-height `1.3` |
| Input / button / select | inherit the page font; buttons `font-weight: 600` |
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
| Overlay HUD max width | `min(400px, 50vw)` |
| Overlay header padding | `4px 10px` |
| Combobox dropdown max height | `220px` |
| Combobox box-art | `28×40px`, radius `4px` |
| Combobox row padding | `8px 10px` |
| Combobox section label | `0.65rem`, uppercase, letter-spacing `0.04em` |
| Last-death card padding | `10px` |
| Disclosure summary padding | `8px 10px` |
| Death-group gap | `8px` |
| Tag row | two columns: type `110px`, name `1fr`, gap `8px` |
| Death-row tag | `0.75rem`, `--muted` |

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
- Overlay chips sit in a horizontal row, top-right, right-aligned under the header.

### Overlay header

One pill above the count chips. Combines session identity and the most recent stream death. Not a third count chip.

- Fill, padding, and radius match the overlay status pill (`--pill`, `4px 10px`, `6px`). Right-aligned text. Max width is the HUD max.
- Line 1: `Game · Run` (or just the game). `0.7rem`, `--muted`. Ellipsis if it overflows.
- Line 2 (optional): last death note (`0.65rem`, `--fg`, weight `600`) then ` · ` then `Boss · Name` or `Character · Name` (`0.65rem`, `--muted`). Fallback note is `Death`. Hide the separator and category when untagged. Hide the whole line when there is no stream death. One line with ellipsis.
- No time, no `LAST` heading, no clip URL.
- Hidden entirely when there is no session or the overlay is still loading (`hidden` on the header). Status “Waiting for session” instead.

Panel session copy stays a sentence under the title (not this pill).

### Buttons

| Class | Look |
|-------|------|
| default | Neutral fill, `--fg` text |
| `.primary` | `--accent` fill, `--fg-highlight` text, larger padding |
| `.danger` | `--danger-bg` / `--danger-fg` |
| `.is-loading` | Inline-flex, 8px gap; 12×12 spinner to the left of the label |

While an action is in progress, the pressed button uses `.is-loading` and `aria-busy="true"`. The spinner is a 2px `currentColor` ring with one transparent segment, rotating with `btn-spin`. Loading buttons stay at full opacity (do not use the disabled fade) and use `cursor: wait`. Sibling actions disable (faded) until the request finishes.

Every write control uses this pattern: **Start session** / **Restart session**, **Update current**, **End session**, **+1 Death**, **Save**, **Undo**. Until the first `/state` response, those buttons stay disabled (no spinner) and the status line shows loading.

Respect `prefers-reduced-motion: reduce` — spinner stays visible as a static full ring, no rotation.

### Inputs

Full width, muted label above the field, dark fill, light border. Native `<select>` uses the same fill, border, radius, and padding as text inputs (opaque views only).

### Combobox (config Game picker)

Search-select over an input. Opaque views only (not overlay).

- Dropdown is a bordered list under the field, filled with `--surface` so it lifts off the page, scrollable, `z-index: 2`.
- Rows are a horizontal cluster: optional box-art, then name. Hover/keyboard highlight uses combobox row hover fill.
- Section labels (“On this stream”, “Recent”, “Search”) sit above their rows; muted, uppercase, not clickable.
- A selected game is a compact chip above the input (art + name + clear). Clear is a small default button, `aria-label="Clear game"`.
- Custom fallback row: `Use “typed name”` when Helix returns nothing.
- Searching: while a Helix query is in flight, a non-clickable row under the Search section shows a 12×12 spinner and `Searching…` (muted). The custom fallback stays available. `aria-busy="true"` on the input.
- Respect `prefers-reduced-motion: reduce` — `control-hover` duration becomes `0`.

### Last-death card (live config)

Opaque views only. Used to edit the most recent death without leaving Live Controls.

- Card fill and border match chip / list (`--surface` / `--line`), radius `8px`, padding `10px`.
- Title is an `h2`. Timestamp under it uses meta type.
- Note, optional tag (type + name), and Clip URL. Clip placeholder is a Twitch clip URL. Tag type is `None` / `Boss` / `Character`; name is a text field, disabled when type is `None`.
- Actions: **Save** (default button) then **Undo** (`.danger`) in `.actions`.
- Hidden entirely when there is no last death (`hidden` on the section).

### Disclosure groups (panel)

Native `<details>` / `<summary>` for Stream, Game, Run, and optional tag groups. Opaque views only (not overlay).

- Each group is a chip-styled card (same fill, border, radius as count chips).
- Groups stack with `8px` gap.
- Summary is a horizontal row: muted triangle, group name (body, weight `600`, left), muted count on the right. Padding `8px 10px`. Cursor pointer.
- Summary hover / open uses combobox row hover fill and `control-hover`.
- Stream starts open; Game, Run, and tag groups start closed.
- Tag groups follow Run. One group per distinct boss/character name in the current game (session fallback if the game is unset). Label is `Boss · Name` or `Character · Name`. Hide the tag block when there are no tagged deaths.
- Summary uses a muted CSS triangle (closed: pointing right; open: pointing down). Native markers are hidden so alignment matches across browsers.
- List rows inside reuse death-list type. Optional tag line under the note is muted `0.75rem`. Optional **Clip** link is `--accent-text`, `0.75rem`, no underline until hover.
- Respect `prefers-reduced-motion: reduce` — summary hover duration `0`.

### Tag row (live config)

Opaque views only. Used on **+1 Death** and **Last death**.

- Horizontal pair: native select (type) + text input (name).
- Type options: `None`, `Boss`, `Character`. Name maxlength `120`.
- Name is disabled when type is `None`.
- After +1, keep the tag so the next death can reuse the same boss/character; still clear the note.

### Status

Muted by default. `data-state="error"` → `--error`. `data-state="ok"` → `--ok`. `data-state="loading"` stays muted and shows a 12×12 spinner 8px to the left of the copy (same ring as the button spinner, `btn-spin`). Set `aria-busy="true"` while loading.

Opaque views boot with `Waiting for Twitch authorization…` then `Loading…`, both as loading. Overlay errors and loading stay in the small status pill; do not use toast/modals.

### Overlay loading

On first load, hide the header and count chips and show the status pill `Loading` with spinner. After `/state` arrives: header (session, plus last death if any) + count chips, or `Waiting for session` with no spinner. Do not animate the HUD into view.

---

## Overlay rules

- Transparent `html`/`body`; never paint a page background.
- HUD only — Stream / Run counts plus a session/last-death header. No death list, no forms, no Twitch-branded chrome beyond the purple accent if needed.
- Clicks must pass through to the player.
- Keep copy short; this sits on live video.

---

## Do / don’t

**Do**

- Reuse the tokens and component patterns above.
- Keep overlay contrast high enough to read on bright and dark game footage (near-opaque `--surface` pill + light text shadow).
- Match Twitch purple for the main call to action.
- Derive new shades from the four base colours with alpha rather than inventing another hue.

**Don’t**

- Add a third count chip to the overlay without updating this doc.
- Introduce a second font family, or a dark variant of any extension view.
- Use CSS animation that isn’t named in **Motion**.
- Block the centre of the player with HUD (stay top-right unless position is added as a config option here).
