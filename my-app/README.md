# 🎬 Nuxt Movie Tracker

Keep track of the movies you've watched. Browse popular titles or search the whole [TMDB](https://www.themoviedb.org/) catalogue, mark what you've seen, and give each one a rating and a short review.

Built with **Nuxt 4**, **Vue 3** and **Vuetify**.

## Features

- **Accounts.** Register and log in with a username and password. Passwords are hashed and the session lives in an encrypted cookie.
- **Discover.** See what's popular right now or search any movie, with pagination.
- **Watched list.** Mark or unmark movies with one click, then filter by title or sort by most recent, highest rated or title.
- **Ratings and reviews.** Rate a movie from 1 to 5 stars and write a review of up to 1000 characters.
- **Bulk actions.** Select several movies at once to add them to or remove them from your list.
- **Dark "cinema" theme** that works on desktop and mobile.

## Tech stack

| Layer     | Tools                                                                                |
| --------- | ------------------------------------------------------------------------------------ |
| Frontend  | [Nuxt 4](https://nuxt.com) (Vue 3, SSR) + [Vuetify](https://vuetifyjs.com) + [Material Design Icons](https://pictogrammers.com/library/mdi/) |
| API       | [Symfony 8](https://symfony.com) in [`../api`](../api): Security, Validator, Doctrine ORM, HttpClient |
| Data      | PostgreSQL + the [TMDB API](https://developer.themoviedb.org/)                       |

```
Browser ──► Nuxt (pages, SSR) ──/api/**──► Symfony API ──► PostgreSQL
                                                 └───────► TMDB
```

Nuxt has no server routes of its own: `routeRules` in `nuxt.config.ts` proxies every `/api/**` request to Symfony, cookies included. The browser only talks to one origin, so there is no CORS setup and the Symfony session cookie works as is.

## Getting started

### Requirements

- Node.js 20 or later
- Docker (the API runs in containers)
- A free TMDB account and API key: https://www.themoviedb.org/settings/api

### Run

Start the API first (see [`../api/README.md`](../api/README.md)), then:

```bash
npm install
cp .env.example .env   # only needed if the API is not on http://localhost:8000
npm run dev
```

Open http://localhost:3000, create an account, and start adding movies.

## Scripts

| Command           | What it does                         |
| ----------------- | ------------------------------------ |
| `npm run dev`     | Start the dev server with hot reload |
| `npm run build`   | Build for production                 |
| `npm run preview` | Preview the production build locally |

## Project structure

```
app/
  pages/          index (watched list), movies (discover + details), login
  components/     MovieCard, MovieGrid, ReviewDialog, SelectionBar, ConfirmDialog
  composables/    useWatched (list state + API calls), useSelection, useAuth (logged-in user)
  plugins/        auth.ts: loads the logged-in user before the first page renders
  middleware/     auth.global.ts: redirects to /login when signed out
shared/types/     types for the API's responses
```

## API

The API, its routes and its tests are documented in [`../api/README.md`](../api/README.md).

## Credits

This product uses the TMDB API but is not endorsed or certified by TMDB.
