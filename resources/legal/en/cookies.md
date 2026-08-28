# Cookie policy

## Why there is no cookie banner

Because we do not need one. TuStack **uses no analytics, advertising or third party cookies**.

Article 22.2 of Spanish Law 34/2002 requires consent before setting cookies, except those strictly necessary to provide a service the user asked for. The only ones we set are of that kind, which is why there is no banner. We would rather set nothing than ask permission to track you.

## The cookies we set

All of them are ours, technical and necessary. None is used to analyse your behaviour or for advertising.

On an ordinary visit only two are set:

| Cookie | What for | Lifetime |
|---|---|---|
| `tustack-session` | Keeping your session while you browse and, if you signed in, knowing it is you | 2 hours |
| `XSRF-TOKEN` | Protecting forms against fraudulent submissions from other sites | 2 hours |

And only if you sign in with the box ticked:

| Cookie | What for | Lifetime |
|---|---|---|
| `remember_web_*` | Remembering your session so you need not sign in again | Long lived, until you sign out |

If you register and use saved companies or saved searches, the session cookie is essential: without it we could not tell whose they are.

## What we keep in your browser that is not a cookie

Two preferences live in your device's local storage (`localStorage`). They are never sent to our server and cannot identify you:

- `theme`: whether you prefer light or dark mode.
- `umami.disabled`: whether you asked to be left out of the statistics, as explained below.

## How we measure visits without cookies

We use **Umami**, an analytics tool we host on our own server, which sets no cookies and creates no identifiers. What we get is aggregated and anonymous: how many pages were viewed, from which country, on what kind of device and from which referring page. None of it can recognise you or follow you across sites.

If you would still rather stay out of those statistics, open your browser console on this site and run:

```js
localStorage.setItem('umami.disabled', 1)
```

Umami honours that flag and stops counting in that browser.

## Removing or blocking cookies

You can delete or block them from your browser settings:

- **Chrome:** Settings → Privacy and security → Cookies and other site data
- **Firefox:** Settings → Privacy & Security → Cookies and Site Data
- **Safari:** Preferences → Privacy → Manage Website Data
- **Edge:** Settings → Cookies and site permissions

Blocking the technical cookies still lets you browse and search normally, but you will not be able to sign in or save companies and searches.

## Changes

If we ever add cookies that are not strictly necessary, we will update this page and ask your permission before setting them, with the option to refuse.
