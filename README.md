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
| Framework | [Nuxt 4](https://nuxt.com) (Vue 3, Nitro server)                                     |
| UI        | [Vuetify](https://vuetifyjs.com) + [Material Design Icons](https://pictogrammers.com/library/mdi/) |
| Auth      | [nuxt-auth-utils](https://github.com/atinux/nuxt-auth-utils)                         |
| Data      | [TMDB API](https://developer.themoviedb.org/) + Nitro storage (file-based key-value) |

## Getting started

### Requirements

- Node.js 20 or later
- A free TMDB account and API key: https://www.themoviedb.org/settings/api

### Setup

```bash
git clone https://github.com/behgaya/nuxt-movie-tracker.git
cd nuxt-movie-tracker
npm install
cp .env.example .env
```

Fill in `.env`:

| Variable                | Description                                                                         |
| ----------------------- | ----------------------------------------------------------------------------------- |
| `NUXT_TMDB_TOKEN`       | Your TMDB **v3 API key** or **v4 read access token**. Either works.                 |
| `NUXT_SESSION_PASSWORD` | Secret used to encrypt the login cookie, at least 32 characters. Generate one with `openssl rand -hex 32`. |

### Run

```bash
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
  pages/          index (watched list), movies (discover), login
  components/     MovieCard, MovieGrid, ReviewDialog, SelectionBar, ConfirmDialog
  composables/    useWatched (list state + API calls), useSelection
  middleware/     auth.global.ts: redirects to /login when signed out
server/
  api/auth/       login and register
  api/movies      TMDB proxy (popular + search, cached for 1 hour)
  api/watched/    CRUD for the watched list, plus bulk add/remove
  utils/          user store, watched-list store, input validation
shared/types/     types shared by the client and the server
```

## API

Every route except `auth/*` requires a logged-in session.

| Method   | Route                  | Description                                     |
| -------- | ---------------------- | ----------------------------------------------- |
| `POST`   | `/api/auth/register`   | Create an account and log in                    |
| `POST`   | `/api/auth/login`      | Log in                                          |
| `GET`    | `/api/movies?q=&page=` | Popular movies, or search results when `q` is set |
| `GET`    | `/api/watched`         | Your watched list, newest first                 |
| `POST`   | `/api/watched`         | Mark a movie as watched                         |
| `PATCH`  | `/api/watched/:id`     | Set or clear the rating and review              |
| `DELETE` | `/api/watched/:id`     | Remove a movie from the list                    |
| `POST`   | `/api/watched/bulk`    | Add and remove many movies in one request (`{ add, remove }`) |

The TMDB key only ever lives on the server. The browser talks to `/api/movies`, which is restricted to logged-in users, so the key can't be read or abused from outside.

## Data storage

Users and watched lists are saved as JSON files in `.data/kv/`, using Nitro's default `data` storage. That folder is git-ignored. This is fine for local use and small deployments. For serverless or multi-instance hosting, point the `data` storage at Redis, a database or another [unstorage driver](https://unstorage.unjs.io/drivers) in `nuxt.config.ts`.

## Credits

This product uses the TMDB API but is not endorsed or certified by TMDB.
