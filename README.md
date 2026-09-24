# Spaceship hosting pilot

A throwaway Vite + React + TypeScript SPA, purpose-built to validate
[Spaceship](https://www.spaceship.com/web-hosting/) as a host for static
React/Vite sites before committing two real production sites to it. Nothing
here is meant to go live permanently — once the checklist below passes (or
fails), retire this repo.

**Status: viable, with workarounds — see Verdict at the bottom.**

## What's in it

- 3 routes (`/`, `/about`, `/contact`) via `react-router-dom`, to test
  client-side routing.
- A contact form (`src/pages/Contact.tsx`) posting JSON to a configurable
  `VITE_CONTACT_ENDPOINT`, mirroring the pattern used in the real
  selfdojoweb site's `contactAdapter.ts`.
- `public/.htaccess` — an Apache `mod_rewrite` SPA fallback. Turned out to
  be required (see Verdict).
- `public/api/contact.php` — a PHP endpoint that sends mail via real SMTP
  to Spacemail (not PHP's local `mail()`, which doesn't reach it — see
  Verdict). Edit `$smtp_pass` and `$recipient` before testing; never commit
  a real password.

## Local setup

```bash
npm install
npm run dev       # http://localhost:5173
npm run build     # type-check + production build to dist/
```

## Test checklist and results

### 1. Build pipeline

- **Not tested via Hyperlift** — it's a paid add-on, not included in the
  Web Hosting Pro trial used for this pilot. Deploy instead used a manual
  build (`npm run build` run locally/in CI) + upload of `dist/` via
  cPanel File Manager. This sidesteps the whole "does the host's build
  step work" question, at the cost of losing push-to-deploy automation
  unless you wire up your own CI (e.g. a GitHub Action that builds and
  FTP/SFTP-uploads `dist/`).

### 2. SPA routing fallback

- **Pass, with a gotcha.** `/about` 404'd on first deploy. Root cause:
  cPanel File Manager hides dotfiles by default, so `.htaccess` wasn't
  selected/moved along with the rest of `dist/`'s contents. After enabling
  "Show Hidden Files" and moving `.htaccess` into place, direct loads of
  `/about` worked correctly — confirms Spaceship's LiteSpeed server honors
  Apache-style `mod_rewrite` rules.
- **Second gotcha**: the domain's actual document root was a separate
  folder (`/home/<user>/garrymconsulting.com/`) from the account's default
  `public_html/`, created by cPanel's "Custom website" wizard. Uploaded
  files initially landed in the wrong folder and served an empty
  directory listing until moved to the right one. Double-check which
  folder is actually mapped to the domain before uploading.

### 3. Deploy workflow

- **Manual only** (no Hyperlift). Each update means re-uploading changed
  files via File Manager or FTP. Fine for a pilot; for real sites, set up
  FTP/SFTP + a small CI workflow if you want push-to-deploy convenience.

### 4. HTTPS

- Not explicitly re-verified in this pass, but the domain served over
  `https://` throughout testing without issue.

### 5. Contact form / email delivery

- **PHP's `mail()` does not work** — it reported success but no email ever
  arrived, even to a mailbox confirmed to work for real inbound mail.
  Root cause: **Spacemail is a separate backend
  (`mail.spacemail.com`) from the web hosting account**, not a local mail
  server PHP's `mail()` can reach. Confirmed by the account's cPanel
  having no "Track Delivery"/"Email Deliverability" tool at all.
- **Fix: send via real SMTP** using Spacemail's IMAP/SMTP/POP3 credentials
  (`mail.spacemail.com:465`, SSL, AUTH LOGIN) instead of `mail()`. Wrote a
  small dependency-free SMTP client directly in `contact.php` (no
  Composer/PHPMailer needed) — **this works**, confirmed via a real test
  submission received in the inbox.
- Two bugs surfaced and fixed along the way: a PHP 7.3-only trailing
  comma in a function call (harmless on this host's PHP 8.2, but fixed
  for portability), and `catch (Exception $e)` not catching PHP 8's
  `Error`-based fatals — broadened to `catch (\Throwable $e)` plus a
  `register_shutdown_function` fallback, so any future fatal error
  surfaces as JSON instead of a blank 500.

### 6. Cost check

- Not fully priced out — this pilot ran on a trial. Confirm actual
  renewal pricing for whichever plan tier before committing.

## Verdict

**Viable for the two new sites, with workarounds — not a "just works"
platform the way Netlify was for selfdojoweb.** Specifically:

1. Use the base Web Hosting plan; skip Hyperlift (unnecessary for a
   pre-built static SPA, and paid).
2. Deploy is manual (File Manager/FTP upload of `dist/`), or you build
   your own CI-based auto-deploy — no Netlify-style git integration out
   of the box on this plan.
3. Watch for hidden files (`.htaccess`) not transferring by default in
   cPanel's File Manager, and confirm which folder is actually the live
   document root before uploading.
4. For the contact form, use real SMTP against Spacemail's credentials
   (a small vanilla-PHP SMTP client works fine, as proven here) —
   **not** PHP's `mail()`, which silently fails to reach Spacemail. The
   working `contact.php` in this repo can be adapted directly for the
   two real sites.
