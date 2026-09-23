# Spaceship hosting pilot

A throwaway Vite + React + TypeScript SPA, purpose-built to validate
[Spaceship](https://www.spaceship.com/web-hosting/) as a host for static
React/Vite sites before committing two real production sites to it. Nothing
here is meant to go live permanently — once the checklist below passes (or
fails), retire this repo.

## What's in it

- 3 routes (`/`, `/about`, `/contact`) via `react-router-dom`, to test
  client-side routing.
- A contact form (`src/pages/Contact.tsx`) posting JSON to a configurable
  `VITE_CONTACT_ENDPOINT`, mirroring the pattern used in the real
  selfdojoweb site's `contactAdapter.ts`.
- `public/.htaccess` — an Apache `mod_rewrite` SPA fallback, in case
  Spaceship's deploy pipeline doesn't configure one automatically.
- `public/api/contact.php` — a minimal PHP mail-relay endpoint, to test
  whether Spaceship's hosting can send mail server-side (backed by
  Spacemail) from a script sitting alongside the static build. **Edit the
  `$recipient` value in that file before testing.** This only works if the
  host actually executes PHP for that path — confirming that is one of the
  things this pilot exists to find out.

## Local setup

```bash
npm install
npm run dev       # http://localhost:5173
npm run build     # type-check + production build to dist/
```

## Test checklist

Run through these in order once the repo is connected to Spaceship
(Hyperlift or however Spaceship builds from GitHub). Record pass/fail for
each — this determines whether Spaceship is viable for the two real sites.

### 1. Build pipeline

- [ ] Spaceship/Hyperlift runs `npm install && npm run build` and serves
      the `dist/` output (or explain what it does instead, if different).
- [ ] Confirm the Node version used is 20+ (check for a version picker or
      `.nvmrc`/`engines` support — this repo doesn't ship one; add one if
      the build fails on an old Node version).
- [ ] Set `VITE_BUILD_MARKER` in Spaceship's environment variable settings
      to some distinct string, redeploy, and confirm it shows on the home
      page — proves env vars are actually injected at build time.

### 2. SPA routing fallback (the most important test)

- [ ] After deploying, open `/about` **directly** in a fresh browser tab
      (type the full URL, don't click the nav link). It must render the
      About page, not a 404.
- [ ] If it 404s, confirm whether `public/.htaccess` (already in this
      repo, so it's in `dist/.htaccess` after build) fixes it once
      deployed. If Spaceship's deploy model isn't classic
      Apache/cPanel-file-serving, `.htaccess` may not apply — note what
      does or doesn't work.

### 3. Deploy workflow

- [ ] Push a small commit (e.g. edit the Home page text), confirm
      auto-redeploy triggers and finishes in a reasonable time.
- [ ] Check whether there's a rollback/previous-deploy mechanism if a push
      breaks the site.

### 4. HTTPS

- [ ] Confirm free SSL auto-provisions for the connected domain (apex and
      `www`) and note how long it took.

### 5. Contact form / email delivery

- [ ] Edit `public/api/contact.php`'s `$recipient` to a real test mailbox
      (ideally the Spacemail inbox you'd use for real, e.g.
      `info@yourtestdomain`).
- [ ] Set `VITE_CONTACT_ENDPOINT` (Spaceship env vars) to
      `https://<your-test-domain>/api/contact.php`, redeploy.
- [ ] Submit the form on `/contact` with real values, confirm the success
      state renders.
- [ ] Check the target inbox — did the email arrive? How long did it take?
      Check spam. If `api/contact.php` doesn't execute at all (404 or raw
      PHP source returned instead of running), that means Spaceship's
      pipeline for this repo doesn't run PHP alongside the static build —
      note that as a hard blocker for the "PHP mail relay" approach, and
      it'd mean falling back to a Node/Express endpoint (via
      `nodemailer` + Spacemail SMTP credentials) or a third-party service
      like Formspree instead, same as the workaround used for
      selfdojoweb's Netlify deployment.

### 6. Cost check

- [ ] Confirm actual renewal price for the plan tier used (not just the
      first-year intro price) and multiply by however many sites you'd
      actually run through Spaceship.

## Verdict

After running through the checklist, summarize: is Spaceship viable for
the two new sites as-is, viable with workarounds (note which), or not
viable (note the blocking failure)?
