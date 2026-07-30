# Analytics

Cookieless by design, so the site needs no consent banner. Two independent
pieces, both governed by the same switch.

## The switch

```
ANALYTICS_ENABLED=true
UMAMI_SCRIPT_URL=https://stats.tustack.es/script.js
UMAMI_WEBSITE_ID=...
```

Nothing is measured unless `ANALYTICS_ENABLED` is true, the environment is
production, and the visitor is not an admin. See `App\Support\Analytics`.

## What is stored

Umami keeps page views, referrers, countries and devices. No cookies, no
identifiers.

The `search_logs` table keeps the technologies and the province of each
search, with a timestamp. No ip address, no user agent, no user id. The
browser talks to Meilisearch directly, so searches are reported by the page
itself through `POST /track-search`, rate limited to 30 a minute.

## Excluding your own visits

The admin check only applies while logged in. To stop Umami counting a
browser for good, run this once in its console:

```js
localStorage.setItem('umami.disabled', 1)
```

Do it on every device you browse the live site from.

## Where Umami runs

Self-hosted at `stats.tustack.es` on the same VPS, as a CloudPanel Node.js
site owned by its own system user, listening on 127.0.0.1:3000 behind the
panel's proxy. PM2 keeps it alive: `pm2 status`, `pm2 logs umami`,
`pm2 restart umami` after changing its environment.

Two things worth knowing before touching it:

- Umami v3 runs on PostgreSQL only. CloudPanel does not manage PostgreSQL,
  so it was installed by hand and sits outside the panel's backups.
- Its dependencies install with `pnpm`. Plain `npm install` fails on a peer
  conflict between react 19 and react-simple-maps.

Credentials and the server runbook live outside this repository.
