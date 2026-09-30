# Demo recording (submission)

The take-home assignment asks for an **application walkthrough** that evaluators can open **without signing in** to your accounts (public or “anyone with the link” access).

## Where to put the link

After you upload the video, set the **public URL** in the root [README.md](../README.md) under **Demo recording**. That is the single link evaluators should use.

Do not commit private/unlisted links that require your Google/Zoom login only.

## What to record (~5–10 minutes)

Follow the live flow in [demo-script.md](demo-script.md). At minimum, show:

1. Login as merchant owner (`acme` / `owner@acme.test`)
2. Plans, customers, subscriptions (including pricing snapshot)
3. Usage ingest and daily usage
4. Plan change (mid-cycle) if time allows
5. Invoice generation (UI or command) and invoice detail
6. Dashboard metrics
7. Brief mention of 50L+ approach ([architecture.md](architecture.md))

You may narrate architecture and trade-offs while showing the UI; no need to read code unless you want to.

## Hosting options (pick one)

| Service | Public access |
|---------|----------------|
| [YouTube](https://www.youtube.com/) | Upload as **Public** or **Unlisted** (unlisted is fine if the URL is in README) |
| [Loom](https://www.loom.com/) | Share link with **Anyone with the link** |
| Google Drive | File → Share → **Anyone with the link** → Viewer |
| Vimeo | Public or unlisted with link |

Test the link in a **private/incognito** browser window (logged out) before submitting.

## Recording tips

- Run `docker compose up -d`, `migrate`, `db:seed`, and `queue` first (see demo script).
- Use **http://localhost:8000** or your deployed URL; say which in the first 10 seconds.
- 1080p screen capture is enough; show the sidebar navigation after the UI refresh.
- Avoid showing real secrets; dev credentials in README are intentional for local demo only.

## Checklist before you submit

- [ ] Recording covers the demo script flow
- [ ] Link opens without authentication
- [ ] README **Demo recording** section updated with the URL
- [ ] Link tested in incognito
