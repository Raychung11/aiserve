# eBizMedic — Complete Beginner Deployment Guide

Deploy eBizMedic to the internet for **free** using:
- **Vercel** — hosts your Next.js app (free)
- **Supabase** — hosts your PostgreSQL database (free)

Total time: about 45–60 minutes.

---

## What You Will Have at the End

A live URL like `https://ebizmedic-yourname.vercel.app` that you can share and access from any device.

---

## Before You Start — Things You Need

1. A computer (Windows, Mac, or Linux)
2. Internet connection
3. An email address (to create accounts)

That's it. Everything else is free and we install it below.

---

## PART 1 — Install Tools on Your Computer

### Step 1.1 — Install Node.js

Node.js is the engine that runs the eBizMedic code.

1. Go to **https://nodejs.org**
2. Click the big green button that says **"LTS"** (Long Term Support)
3. Download and run the installer
4. Click "Next" through all the steps, keep all defaults
5. When finished, **restart your computer**

**Verify it worked:**
- On Windows: press `Win + R`, type `cmd`, press Enter
- On Mac: press `Cmd + Space`, type `terminal`, press Enter
- Type this and press Enter:
```
node --version
```
You should see something like `v20.11.0`. Any number is fine.

---

### Step 1.2 — Install Git

Git is used to save and upload your code.

1. Go to **https://git-scm.com/downloads**
2. Download for your operating system
3. Run the installer, click "Next" through everything, keep all defaults

**Verify it worked:**
```
git --version
```
You should see something like `git version 2.43.0`

---

### Step 1.3 — Install VS Code (Code Editor — Optional but Recommended)

1. Go to **https://code.visualstudio.com**
2. Download and install it
3. This is where you can view and edit the code

---

## PART 2 — Get the Code on Your Computer

### Step 2.1 — Open a Terminal / Command Prompt

- **Windows**: press `Win + R`, type `cmd`, press Enter
- **Mac/Linux**: press `Cmd + Space`, type `terminal`, press Enter

You will see a black (or white) window with a blinking cursor. This is where you type commands.

---

### Step 2.2 — Choose Where to Save the Project

Type this to go to your Desktop (or choose any folder you like):

**Windows:**
```
cd %USERPROFILE%\Desktop
```

**Mac/Linux:**
```
cd ~/Desktop
```

---

### Step 2.3 — Download the Code

```
git clone https://github.com/Raychung11/aiserve.git
```

This downloads the entire project. You will see files being downloaded.

Then go into the project:
```
cd aiserve\ebizmedic
```
(On Mac/Linux use forward slash: `cd aiserve/ebizmedic`)

---

### Step 2.4 — Install the Code Dependencies

This downloads all the libraries the project needs:
```
npm install
```

This will take 2–5 minutes. You will see lots of text scrolling. Wait for it to finish.

> **What is npm?** npm stands for "Node Package Manager". It downloads code libraries, similar to how you install apps on your phone.

---

## PART 3 — Set Up Your Free Database (Supabase)

Your app needs a database to store all the data (users, bookings, wallet transactions, etc.). We use Supabase which gives a free PostgreSQL database.

### Step 3.1 — Create a Supabase Account

1. Go to **https://supabase.com**
2. Click **"Start your project"**
3. Sign up with your GitHub account or email
4. Verify your email if required

---

### Step 3.2 — Create a New Project

1. After logging in, click **"New project"**
2. Fill in the form:
   - **Name**: `ebizmedic` (or anything you like)
   - **Database Password**: create a strong password — **SAVE THIS PASSWORD, YOU WILL NEED IT**
   - **Region**: choose the one closest to your location (e.g., Southeast Asia → Singapore)
3. Click **"Create new project"**
4. Wait about 1–2 minutes while Supabase sets up your database

---

### Step 3.3 — Get Your Database Connection String

1. In Supabase, click the **Settings** icon (gear icon) on the left sidebar
2. Click **"Database"**
3. Scroll down to **"Connection string"**
4. Click the **"URI"** tab
5. You will see something like:
   ```
   postgresql://postgres:[YOUR-PASSWORD]@db.abcdefghijk.supabase.co:5432/postgres
   ```
6. **Copy this entire string**
7. Replace `[YOUR-PASSWORD]` with the password you created in Step 3.2

> **Save this somewhere safe** — this is your `DATABASE_URL`

---

## PART 4 — Set Up the Project Configuration File

### Step 4.1 — Create Your .env File

The `.env` file stores secret settings. It must NEVER be shared or uploaded to GitHub.

In your terminal (make sure you are in the `ebizmedic` folder), type:

**Windows:**
```
copy .env.example .env
```

**Mac/Linux:**
```
cp .env.example .env
```

---

### Step 4.2 — Edit the .env File

Open the `.env` file in VS Code or any text editor:
- **With VS Code**: type `code .env` in the terminal
- **With Notepad (Windows)**: type `notepad .env`
- **With nano (Mac/Linux)**: type `nano .env`

You will see this content:
```
DATABASE_URL="postgresql://user:password@localhost:5432/ebizmedic"
JWT_SECRET="your-super-secret-jwt-key-min-32-chars-change-this"
...
```

**Change only these two lines for now:**

**Line 1 — DATABASE_URL:**
Replace the entire value with your Supabase connection string from Step 3.3:
```
DATABASE_URL="postgresql://postgres:YourPassword@db.abcdefghijk.supabase.co:5432/postgres"
```

**Line 2 — JWT_SECRET:**
This needs to be a long random string (at least 32 characters). Generate one:
- Go to **https://generate-secret.vercel.app/32** in your browser
- Copy the random string shown
- Paste it:
```
JWT_SECRET="paste-your-random-string-here-minimum-32-characters"
```

**Save the file** (Ctrl+S on Windows, Cmd+S on Mac).

> **Leave all other lines as they are for now.** Zoom and email settings can be configured later.

---

## PART 5 — Set Up the Database Tables

Now we create all the database tables that eBizMedic needs.

### Step 5.1 — Generate Prisma Client

In your terminal (still in the `ebizmedic` folder):
```
npm run db:generate
```

You should see: `✔ Generated Prisma Client`

---

### Step 5.2 — Push the Database Schema

This creates all 20+ tables in your Supabase database:
```
npm run db:push
```

You will see it creating tables. This takes about 30–60 seconds.

If successful, you will see:
```
✔ Your database is now in sync with your Prisma schema.
```

> **If you see an error** mentioning "connection refused" or "password authentication failed":
> - Double-check your DATABASE_URL in the `.env` file
> - Make sure the password is correct and there are no extra spaces

---

### Step 5.3 — Add Demo Data (Seed)

This creates 5 demo accounts so you can log in immediately:
```
npm run db:seed
```

You will see:
```
✓ Super admin: admin@ebizmedic.com
✓ Organisation: Demo Corporation Sdn Bhd
✓ Doctor: Dr. Sarah Lee
✓ Patient: Nurul Izzah
✓ Dispensary & 5 products seeded
✓ Zoom host seeded

✅ Seed complete.

Demo accounts:
  Admin:       admin@ebizmedic.com       / Admin@1234
  Org Admin:   orgadmin@demo.com         / OrgAdmin@1234
  Doctor:      doctor@demo.com           / Doctor@1234
  Patient:     patient@demo.com          / Patient@1234
  Dispensary:  dispensary@demo.com       / Dispensary@1234
```

---

## PART 6 — Test it Locally First

Before deploying online, test it on your computer.

### Step 6.1 — Start the Dev Server

```
npm run dev
```

You will see:
```
▲ Next.js 14.2.13
- Local:        http://localhost:3000
```

### Step 6.2 — Open in Browser

Open your browser and go to: **http://localhost:3000**

You should be redirected to the login page.

### Step 6.3 — Log In

Try logging in with:
- Email: `admin@ebizmedic.com`
- Password: `Admin@1234`

You should see the Admin Dashboard with platform stats.

**Test all roles:**
| Email | Password | What you see |
|-------|----------|-------------|
| admin@ebizmedic.com | Admin@1234 | Platform admin dashboard |
| orgadmin@demo.com | OrgAdmin@1234 | Organisation dashboard |
| doctor@demo.com | Doctor@1234 | Doctor dashboard |
| patient@demo.com | Patient@1234 | Patient dashboard |
| dispensary@demo.com | Dispensary@1234 | Dispensary dashboard |

### Step 6.4 — Stop the Server

Press `Ctrl + C` in the terminal to stop it.

---

## PART 7 — Create a GitHub Account and Push Your Code

Vercel deploys from GitHub. We need to put the code on GitHub.

### Step 7.1 — Create a GitHub Account

1. Go to **https://github.com**
2. Click **"Sign up"**
3. Follow the steps (use your email, create a username and password)
4. Verify your email

---

### Step 7.2 — Create a New Repository on GitHub

1. After logging in, click the **"+"** icon (top right) → **"New repository"**
2. Fill in:
   - **Repository name**: `ebizmedic`
   - **Visibility**: Private (recommended for a healthcare app)
3. **DO NOT** check "Initialize this repository with a README"
4. Click **"Create repository"**
5. You will see a page with setup instructions — keep this page open

---

### Step 7.3 — Make Sure .env is Protected

The `.env` file must never go to GitHub. Check that `.gitignore` includes it:

In your terminal (in the `ebizmedic` folder):

**Windows:**
```
type .gitignore 2>nul || echo ".env file check - create .gitignore if missing"
```

**Mac/Linux:**
```
cat .gitignore 2>/dev/null || echo "No .gitignore found"
```

If you don't see a `.gitignore` file, create one:
```
echo ".env" > .gitignore
echo "node_modules" >> .gitignore
echo ".next" >> .gitignore
```

---

### Step 7.4 — Push Your Code to GitHub

In your terminal (in the `ebizmedic` folder), run these commands ONE BY ONE:

```
git init
```
```
git add .
```
```
git commit -m "Initial eBizMedic setup"
```

Now connect to your GitHub repository. Go back to your GitHub page from Step 7.2 and copy the URL that looks like `https://github.com/YourUsername/ebizmedic.git`, then run:

```
git remote add origin https://github.com/YourUsername/ebizmedic.git
```
(Replace `YourUsername` with your actual GitHub username)

```
git branch -M main
```
```
git push -u origin main
```

GitHub will ask for your username and password.

> **Note**: GitHub no longer accepts passwords for `git push`. You need a **Personal Access Token** instead:
> 1. Go to https://github.com/settings/tokens
> 2. Click "Generate new token" → "Generate new token (classic)"
> 3. Give it a name like "ebizmedic deploy"
> 4. Check the `repo` checkbox
> 5. Click "Generate token"
> 6. Copy the token — **use this as your password** when git asks

After pushing, refresh your GitHub page. You should see all the files there.

---

## PART 8 — Deploy to Vercel

### Step 8.1 — Create a Vercel Account

1. Go to **https://vercel.com**
2. Click **"Sign Up"**
3. Choose **"Continue with GitHub"** — this links your GitHub automatically
4. Authorize Vercel to access your GitHub

---

### Step 8.2 — Import Your Project

1. After logging in to Vercel, click **"Add New…"** → **"Project"**
2. You will see a list of your GitHub repositories
3. Find `ebizmedic` and click **"Import"**

---

### Step 8.3 — Configure the Project

On the configuration page:

1. **Framework Preset**: Vercel should automatically detect "Next.js" ✓
2. **Root Directory**: Click "Edit" and type `ebizmedic` (because the Next.js app is inside the `ebizmedic` folder, not the root)
   - After typing, click the checkbox next to it
3. **Build Command**: Leave as default (`npm run build`)
4. **Output Directory**: Leave as default

**Do NOT click Deploy yet.** First set up the environment variables below.

---

### Step 8.4 — Add Environment Variables

On the same page, scroll down to **"Environment Variables"**.

You need to add these one by one. For each one:
- Type the **Name** in the first box
- Type the **Value** in the second box
- Click **"Add"**

| Name | Value |
|------|-------|
| `DATABASE_URL` | Your Supabase connection string (from Part 3) |
| `JWT_SECRET` | Your random 32-character string (from Part 4) |
| `NEXT_PUBLIC_APP_URL` | `https://ebizmedic-yourname.vercel.app` (you can update this after you know your URL) |
| `NODE_ENV` | `production` |

> **Zoom and email variables** — leave these out for now. The app works without them (bookings are created, just without video meeting links). You can add them later.

---

### Step 8.5 — Deploy!

Click the **"Deploy"** button.

You will see a deployment screen with logs scrolling. This takes 2–4 minutes.

If successful, you will see:
- A big **"Congratulations!"** message
- A preview of your app
- A URL like `https://ebizmedic-yourname.vercel.app`

Click the URL to open your live app!

---

## PART 9 — Troubleshooting Common Errors

### Error: "Cannot find module '.prisma/client'"

**Fix**: In Vercel, go to your project → Settings → General → Build Command. Change it to:
```
npx prisma generate && next build
```

---

### Error: "Environment variable not found: DATABASE_URL"

**Fix**:
1. Go to your Vercel project dashboard
2. Click **Settings** → **Environment Variables**
3. Check that `DATABASE_URL` is there
4. After adding/changing variables, you must **Redeploy**:
   - Go to **Deployments** tab
   - Click the three dots `...` on the latest deployment
   - Click **Redeploy**

---

### Error: "P1001: Can't reach database server"

**Fix**: Your Supabase database may be paused (free tier pauses after 1 week of inactivity).
1. Go to https://supabase.com
2. Open your project
3. If you see a "Resume" button, click it
4. Wait 1–2 minutes, then redeploy on Vercel

---

### Error on login: "500 Internal Server Error"

**Fix**: Most likely the database tables don't exist yet in the production database. You need to run the schema push against your Supabase database again from your local computer:
```
npm run db:push
```
(Make sure your `.env` file has the correct Supabase `DATABASE_URL`)

---

### The page loads but login shows "Invalid email or password"

**Fix**: The demo data (seed) hasn't been run. Run this locally:
```
npm run db:seed
```
This pushes the demo accounts to your Supabase database.

---

### Changes I make to the code don't appear on the live site

**Fix**: Vercel automatically rebuilds when you push to GitHub. Do this:
```
git add .
git commit -m "my changes"
git push
```
Vercel will automatically detect the push and redeploy in 2–3 minutes.

---

## PART 10 — After Deployment Checklist

✅ Live site is accessible at your Vercel URL  
✅ Login works with all 5 demo accounts  
✅ Each role shows the correct dashboard  
✅ Patient can see the "Book Appointment" page  
✅ Doctor dashboard shows the schedule  
✅ Organisation wallet page loads  

---

## PART 11 — Setting Up Your Custom Domain (Optional)

If you have a domain name (e.g., from Hostinger):

1. In Vercel, go to your project → **Settings** → **Domains**
2. Type your domain (e.g., `app.ebizmedic.com`) and click **Add**
3. Vercel will show you DNS records to add
4. Log in to **Hostinger** → **DNS Zone** for your domain
5. Add the records Vercel shows you (usually a CNAME record)
6. Wait up to 24 hours for DNS to update

---

## PART 12 — Setting Up Zoom (For Video Consultations)

Skip this for now during testing. Do this when you're ready to go live.

### 12.1 Create a Zoom Account

1. Go to **https://zoom.us** and sign up for a free account
2. Go to **https://marketplace.zoom.us**
3. Click **Develop** → **Build App**
4. Choose **Server-to-Server OAuth**
5. Fill in the app name (e.g., "eBizMedic")
6. Copy the **Account ID**, **Client ID**, and **Client Secret**

### 12.2 Add Zoom Variables to Vercel

In Vercel → Settings → Environment Variables, add:

| Name | Value |
|------|-------|
| `ZOOM_ACCOUNT_ID` | From your Zoom app |
| `ZOOM_CLIENT_ID` | From your Zoom app |
| `ZOOM_CLIENT_SECRET` | From your Zoom app |
| `ZOOM_WEBHOOK_SECRET_TOKEN` | From your Zoom webhook settings |

Then redeploy.

---

## Summary — Your Free Hosting Stack

```
Users' Browser
      ↓
  Vercel (Free)
  next.js app hosted here
  auto-deploys when you push to GitHub
      ↓
  Supabase (Free)
  PostgreSQL database
  all your data lives here
```

**Monthly cost: MYR 0.00** for the free tiers.

Free tier limits:
- Vercel: 100GB bandwidth/month, unlimited deployments
- Supabase: 500MB database, 2GB bandwidth — plenty for development and early users

When you need to scale up, Vercel Pro is ~$20/month and Supabase Pro is ~$25/month.

---

## Quick Reference Commands

Run these in the `ebizmedic` folder in your terminal:

| Command | What it does |
|---------|-------------|
| `npm run dev` | Start local development server |
| `npm run db:push` | Sync database schema |
| `npm run db:seed` | Add demo data |
| `npm run db:studio` | Open visual database browser |
| `npm run build` | Build for production (test before deploying) |
| `git add . && git commit -m "changes" && git push` | Deploy latest code to Vercel |

---

## Need Help?

- **Vercel docs**: https://vercel.com/docs
- **Supabase docs**: https://supabase.com/docs
- **Next.js docs**: https://nextjs.org/docs
- **Prisma docs**: https://www.prisma.io/docs
