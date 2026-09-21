# Death Counter — streamer guide

This is the usage guide for **you**, the Twitch streamer (or a mod helping on stream). It covers activating the extension, starting a session, counting deaths, and what viewers see.

It does **not** cover installing the backend, ngrok, or building the extension. Those steps live in the [project README](../README.md).

---

## What it does

Death Counter tracks how many times you die during a stream and shows the numbers to people watching on twitch.tv.

You (or a mod) press **+1 Death** when you die. Viewers see the counts update on the video player. You do not need OBS, a browser source, or a chat command.

**You control:**

| Place | Where you open it | What it’s for |
|-------|-------------------|---------------|
| **Config** (“Death Counter Setup”) | Extension Manager → Configure | Start, rename, or end a **session** |
| **Live Controls** | Stream Manager while you are live | Press **+1 Death** and see the three counts |

**Viewers see:**

| Place | What they see |
|-------|----------------|
| **Video overlay** | Small HUD in the **top-right** of the player: session + last death, **Stream** count, **Run** count |
| **Panel** (only if you activate a panel slot) | Stream, Game, and Run counts plus a **Recent deaths** list |

The overlay sits on top of Twitch’s player for people watching in the browser or app. It is **not** burned into your stream, **not** in VODs, and **not** visible in OBS.

---

## First-time setup

Do this once per channel, before or at the start of a stream.

### 1. Add the extension

1. Open [Creator Dashboard](https://dashboard.twitch.tv) → **Extensions**.
2. Find **Death Counter** (or your test build under your developer extensions).
3. Add it to your channel.

### 2. Activate it as a video overlay

1. Open **Extension Manager** for your channel  
   (`Creator Dashboard` → your channel → **Extensions** / **Extension Manager**).
2. Activate Death Counter in an **Overlay** slot (not Component).
3. Save.

Viewers will not see counts until you do this. The HUD only appears on the video player when the overlay is active.

If you also want the full list of recent deaths under the player, activate a **Panel** slot as well. The overlay is enough for most streams.

### 3. Open Config and start a session

1. On the activated extension, click **Configure**. You should see **Death Counter Setup**.
2. Choose **Game**:
   - If Twitch already knows what you are streaming, it is listed under **On this stream** (and may already be selected). Click it if it isn’t.
   - Or click **Recent** for a game you have counted before.
   - Or type to **search Twitch’s game list** and pick the official name. `Elden Ring`, `elden ring`, and `ELDEN RING!` are the same game once you pick it from the list.
   - Only use **Use “typed name”** when the title is not in Twitch’s catalog (unlisted / unreleased).
3. Optionally set **Run** — e.g. `RL1`, `NG+`, `Attempt 3` (120 characters max).
4. Click **Start session**.

Status should change to **Session started** / **Session active**. The summary line should read something like `Active: Elden Ring · RL1`.

You **must** start a session before anyone can record a death. Until then:

- Live Controls shows **No active session** and **+1 Death** is disabled.
- The overlay shows **Waiting for session**.

---

## During the stream

### Open Live Controls

While live, open **Stream Manager** in Creator Dashboard and use the extension’s live config view (**Live Controls**).

You should see three numbers — **Stream**, **Game**, **Run** — a **Note** field, optional **Tag** (None / Boss / Character + name), a large **+1 Death** button, and a **Last death** card after you record one.

### Record a death

1. (Optional) Type a short note, e.g. `phase 2`, `fall`, `gank`. 500 characters max.
2. (Optional) Set **Tag** to **Boss** or **Character** and type a name, e.g. `Malenia` or `Samurai`. 120 characters max. Leave it at **None** to skip.
3. Click **+1 Death**.

Status should say **Death recorded**. The numbers tick up. The note field clears. The tag stays so you can +1 the same boss or character again. **Last death** shows the note, tag, time, and a clip field.

Change the tag when you move to a different fight or character. Set it back to **None** for untagged deaths.

To fix a mis-click, open **Last death** and click **Undo**. That deletes the most recent death and drops the counts.

To change the note, tag, or attach a clip:

1. Edit **Note** and/or **Tag** on the Last death card, and/or paste a Twitch clip URL (`clips.twitch.tv/…` or `twitch.tv/…/clip/…`).
2. Click **Save**.

Clear the clip field and **Save** to remove a clip. Set Tag to **None** and **Save** to remove a tag. Viewers see tags and clip links on the **panel**. The overlay shows the last death’s tag (if any), but not clip links.

Viewers on twitch.tv should see overlay counts update within a second or two. They do not need to refresh.

If **+1 Death** is greyed out, there is no active session. Go back to Config and start one.

---

## What the three numbers mean

Every death is stored with the **game** (Twitch catalog pick or custom name) and **run** that were on the session **at the moment you pressed +1**.

| Count | What it includes |
|-------|------------------|
| **Stream** | Deaths in the **current session only**. Resets when you start or restart a session. |
| **Game** | All deaths on your channel for the **same catalog game** as the current session (across sessions). Picking the official Twitch title keeps the total together even if you typed it differently last time. |
| **Run** | All deaths on your channel for that catalog game **and** the same run name (across sessions). |

**Examples**

- Session `Elden Ring` / `RL1`, you die 4 times this stream, and you already had 20 RL1 deaths from last week: **Stream 4**, **Game** (all Elden Ring deaths), **Run 24**.
- You leave Game and Run blank: **Game** and **Run** show the same number as **Stream**.
- Overlay shows **Stream** and **Run** only. Session name and last death share one header above them. **Game** is still tracked; viewers see it on the panel if you activated one.

For **Run**, use the same spelling across streams (`RL1` and `rl1` are different). Game totals no longer depend on how you typed the title, as long as you pick it from the list.

---

## Change game or run without resetting Stream

Use this when you switch characters, attempts, or titles **without** starting a new broadcast session.

1. Open **Config**.
2. Pick a different **Game** from the list (or **Clear** then search), and/or edit **Run**.
3. Click **Update current** (enabled only while a session is active).

The session label on the overlay changes. **Stream** stays the same.

New deaths use the new game and run. Older deaths keep the game they had when you recorded them, so **Game** and **Run** totals follow whatever is on the session *now*. If you switch from Elden Ring to a different title, Game shows that title’s history, not the deaths you just recorded. If you rename `RL1` to `Attempt 2`, Run will show deaths already tagged `Attempt 2`.

---

## End or restart a session

| Action | Where | What happens |
|--------|--------|----------------|
| **End session** | Config | Session stops. Overlay shows **Waiting for session**. Stream/Game/Run on Live Controls go to `0`. **+1 Death** disables. Past deaths stay in the database. |
| **Restart session** | Config → **Start session** while one is already active (the button label becomes **Restart session**) | The old session is ended and a new one starts immediately. **Stream** goes back to `0`. Game/Run totals continue if you picked the same catalog game (and the same run spelling). |

Typical pattern:

1. Before going live: **Start session** with today’s game and run.
2. During the stream: **+1 Death** from Live Controls.
3. When you go offline (or switch to a totally different game): **End session**.

You can leave a session running between streams if you want **Stream** to keep climbing. End it when you want a fresh Stream count.

---

## Who can press the buttons

| Role | Config (start / update / end) | Live Controls (+1 / Save / Undo) |
|------|-------------------------------|----------------------------------|
| Broadcaster (you) | Yes | Yes |
| Moderator | Yes (if they can open those views) | Yes |
| Viewers | No | No |

Viewers only see counts and clip links. They cannot add, edit, or undo deaths from the overlay or panel.

---

## What viewers see

**Overlay (top-right of the player)**

- A compact header: `Game · Run` (or just the game if Run is empty). When there is a death this stream, a second line in the same pill: note (or “Death”) and optional boss/character category.
- Two chips: **Stream** and **Run**.
- Clicks pass through to the player; viewers cannot interact with the HUD.
- If there is no session: **Waiting for session**.

**Panel (optional)**

- Stream, Game, and Run counts.
- Expandable lists for **Stream**, **Game**, and **Run** (up to 25 deaths each). Stream starts open.
- Extra groups for each **Boss** / **Character** tag used on the current game, closed by default, labeled like `Boss · Malenia`.
- Tag names on death rows in Stream / Game / Run.
- A **Clip** link on any death that has one. Opens the Twitch clip.

**Not visible**

- Your Config and Live Controls screens.
- The overlay inside OBS, clips you download, or VODs. Those are encoded from your broadcast, not from Twitch’s viewer UI.

If you want the numbers **in the video itself** (recordings, YouTube, in-person overlays), that is a separate OBS browser source — this extension does not provide that yet.

---

## Typical stream checklist

1. Activate the overlay (once).
2. Open **Config** → pick Game from **On this stream** / Recent / search → set Run → **Start session**.
3. Go live.
4. Keep **Live Controls** handy (second monitor, Stream Manager, or a mod).
5. On each death: optional note + optional boss/character tag → **+1 Death**. Mis-click: **Undo** on Last death. Clip or tag fix: edit Last death → **Save**.
6. Switching attempt names: **Update current**, don’t restart unless you want Stream back at 0.
7. End of stream: **End session** if you want a clean Stream count next time.

---

## What this version does not do

These are not available in the current extension UI:

- Chat commands (`!death`)
- Automatic death detection from the game
- Viewer-submitted clips
- Moving the overlay (it is top-right only)
- Showing the Game count on the overlay
- Embedding the counter in OBS / the encoded stream
- Fight timers

---

## Troubleshooting

| What you see | What to try |
|--------------|-------------|
| Overlay says **Waiting for session** | Start a session in Config. |
| **+1 Death** is disabled / Live Controls says **No active session** | Same — Config → **Start session**. |
| Overlay never appears on the player | Confirm the extension is **Activated** in an **Overlay** slot, then refresh the channel page as a viewer. |
| Counts don’t move for viewers after +1 | Wait a moment; they update over Twitch’s broadcast channel. If you just started the session, make sure you used **+1 Death**, not only **Update current**. |
| Game or Run looks “wrong” after a change | Game totals follow the **selected catalog game**, Run totals follow the **run text**. **Update current** only when you intend to change the bucket. For Run, keep the same spelling as previous streams. |
| Overlay is missing from your OBS preview | Expected. Watch your channel on twitch.tv (or a second account / incognito) to see what viewers see. |
| Config stuck on **Waiting for Twitch authorization…** | Open it from Twitch’s **Configure** button, not as a random browser tab. |

Still stuck: ask whoever installed the extension to confirm it is activated and that the backend is running. Streamers don’t need to change API URLs themselves.
