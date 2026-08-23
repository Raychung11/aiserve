# Deploying AiServe.my via SSH + GitHub (Hostinger)

This project deploys by pulling from GitHub over SSH — no more manual FTP uploads.
Because Git transfers dotfiles too, your `.htaccess` routing is included automatically.

**Paths used below** (adjust the domain folder to match yours):

```
Site root (web root):  ~/domains/lime-aardvark-648594.hostingersite.com/public_html
Secrets (.env):        ~/domains/lime-aardvark-648594.hostingersite.com/.env
Repo:                  git@github.com:Raychung11/aiservehome.git
```

> `.env` is loaded from **one directory above** `public_html`, so it stays out of
> the web root and out of Git. `git pull` never overwrites it.

---

## One-time setup

### 1. Enable SSH on Hostinger
hPanel → **Advanced → SSH Access** → turn it on and note the host, port, and username.
Then connect from your computer:

```bash
ssh -p <port> <user>@<host>
```

### 2. Create an SSH key on the server (for GitHub auth)

```bash
ssh-keygen -t ed25519 -C "hostinger-deploy" -f ~/.ssh/id_ed25519 -N ""
cat ~/.ssh/id_ed25519.pub
```

Copy the printed public key.

### 3. Add the key to GitHub as a Deploy Key
GitHub → repo **Settings → Deploy keys → Add deploy key** → paste the key.
Leave **"Allow write access" unchecked** (pull-only is all a server needs).
Give it a title like `hostinger-deploy`.

Verify the connection (type `yes` to trust the host the first time):

```bash
ssh -T git@github.com
```

You should see: *"Hi Raychung11/aiservehome! You've successfully authenticated..."*

### 4. Put the site under Git (in-place, keeps the live files)

```bash
cd ~/domains/lime-aardvark-648594.hostingersite.com/public_html
git init
git remote add origin git@github.com:Raychung11/aiservehome.git
git fetch origin
# Replace tracked files with the repo version; untracked files (.env is
# outside; new uploads) are left alone:
git checkout -f -B main origin/main
```

> Deploying a feature branch instead of `main`? Swap `main` for the branch name,
> e.g. `git checkout -f -B claude/code-review-zanot3 origin/claude/code-review-zanot3`.

### 5. Create the `.env` file (one level above `public_html`)

```bash
cd ~/domains/lime-aardvark-648594.hostingersite.com
cp public_html/.env.example .env
nano .env      # fill in real DB_* and OPENAI_API_KEY values, then save
```

### 6. Make uploads writable

```bash
chmod -R 755 ~/domains/lime-aardvark-648594.hostingersite.com/public_html/uploads
```

Open the site — it should load with routing and images intact.

---

## Every deploy after that

From your computer, connect and pull:

```bash
cd ~/domains/lime-aardvark-648594.hostingersite.com/public_html
git pull origin main
```

…or deploy a specific branch:

```bash
git pull origin claude/code-review-zanot3
```

Or use the helper script (see below):

```bash
./deploy.sh                 # deploys main
./deploy.sh claude/code-review-zanot3   # deploys a branch
```

---

## Notes & safety

- **Secrets** — `.env` is outside `public_html` and Git-ignored; deploys never touch it.
- **Uploaded / AI-generated images** — files under `uploads/media/` created through the
  admin are untracked, so `git pull` leaves them in place.
- **Never `git push` from the server.** The server only pulls. Make code changes in your
  dev environment / via pull requests, merge, then pull on the server.
- **Switching to a different branch** on the server:
  ```bash
  git fetch origin
  git checkout -f -B <branch> origin/<branch>
  ```
- **If a pull is ever rejected** because the server's tracked files were edited directly,
  reset to the remote (this discards *server-side code edits only* — never `.env`/uploads):
  ```bash
  git fetch origin
  git reset --hard origin/<branch>
  ```
