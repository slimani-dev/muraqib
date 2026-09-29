# Security Policy

Muraqib stores credentials for your infrastructure (Cloudflare, Portainer, media apps, Git hosts), so security reports are taken seriously.

## Reporting a vulnerability

**Please don't open a public issue for security problems.**

Report them privately through [GitHub's private vulnerability reporting](https://github.com/slimani-dev/muraqib/security/advisories/new) for this repository. Include:

- what the issue is and where (file, route, or feature),
- steps to reproduce or a proof of concept,
- the impact you think it has,
- the version or commit you tested.

You can expect an acknowledgement within a few days. Once the issue is confirmed, a fix is prepared and released, and you'll be credited in the advisory unless you prefer not to be.

## Supported versions

Only the latest release (and the `main` branch) receives security fixes.

## How Muraqib protects your data

- **No public sign-up.** Accounts are created by an administrator in the admin panel. The first admin comes from `php artisan db:seed` (`ADMIN_EMAIL` / `ADMIN_PASSWORD`).
- **The admin panel is for admins only.** Integrations, credentials and users are managed there; regular users only see the dashboards of their teams.
- **Secrets are encrypted at rest** with your `APP_KEY`: API keys and tokens for Cloudflare, media services and Git accounts, service passwords, and status-check headers. Losing `APP_KEY` makes them unreadable; leaking it exposes them.
- **Secrets never reach the browser.** Service APIs are called by the server; forms never show stored secrets (leave a field blank to keep the stored value).
- **Browser status checks only use public URLs** you configure, without credentials.

## Running it safely

- Keep `APP_DEBUG=false` and a strong, unique `APP_KEY` in production.
- Serve it over HTTPS, ideally behind an access layer (VPN, Cloudflare Access, or similar), since it holds keys to your infrastructure.
- Give Muraqib the least access it needs: read-only API tokens where the service supports them.
- Change the seeded admin password after the first login, and enable two-factor authentication.
