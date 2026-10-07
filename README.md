# 🎬 Movie Tracker

Keep track of the movies you've watched. Browse popular titles or search the whole [TMDB](https://www.themoviedb.org/) catalogue, mark what you've seen, and give each one a rating and a short review.

A **Nuxt 4** frontend and a **Symfony 8** API, kept together in one repository.

## Features

- **Accounts.** Register and log in with a username and password. Passwords are hashed and the session is kept on the server.
- **Discover.** See what's popular right now or search any movie, with pagination.
- **Movie pages.** Details, cast, director and the trailer for every movie.
- **Watched list.** Mark or unmark movies with one click, then filter by title or sort by most recent, highest rated or title.
- **Ratings and reviews.** Rate a movie from 1 to 5 stars and write a review of up to 1000 characters.
- **Bulk actions.** Select several movies at once to add them to or remove them from your list.
- **Dark "cinema" theme** that works on desktop and mobile.

## How it fits together

```
Browser ──► Nuxt (pages, SSR) ──/api/**──► Symfony API ──► PostgreSQL
            my-app/, port 3000             api/, port 8000  └──► TMDB
```

The browser only talks to Nuxt. Nuxt forwards every `/api/**` request to Symfony, cookies included, so there is no CORS setup. Only Symfony reaches the database and TMDB, and the TMDB key never leaves the server.

| Folder | What it is | Stack |
| --- | --- | --- |
| [`my-app/`](my-app) | the frontend: pages, components, login state | Nuxt 4, Vue 3, Vuetify |
| [`api/`](api) | the backend: accounts, watched lists, TMDB proxy | Symfony 8.1, PHP 8.5, Doctrine, PostgreSQL 17, Docker |

## Getting started

### Requirements

- Docker with Compose (runs the API and the database; no local PHP needed)
- Node.js 20 or later
- A free TMDB API key: https://www.themoviedb.org/settings/api (a v3 key or a v4 read access token)

### 1. Start the API

```bash
git clone https://github.com/behgaya/nuxt-movie-tracker.git
cd nuxt-movie-tracker/api
echo "TMDB_TOKEN=<your key>" > .env.local
docker compose up -d --build
docker compose exec php bin/console doctrine:migrations:migrate -n
```

The API now runs on http://localhost:8000. It only listens on localhost, so nobody else on your network can reach it.

### 2. Start the frontend

In a second terminal:

```bash
cd nuxt-movie-tracker/my-app
npm install
npm run dev
```

Open http://localhost:3000, create an account, and start adding movies.

### Stop

Press `Ctrl+C` in the frontend terminal, then run `docker compose down` in `api/`. Your data stays in a Docker volume; `docker compose down -v` deletes it.

## Tests

```bash
cd api
docker compose exec php sh -c 'bin/console doctrine:database:create --env=test --if-not-exists && bin/console doctrine:migrations:migrate --env=test -n'   # once
docker compose exec php bin/phpunit
```

## More documentation

- [`api/README.md`](api/README.md): every API route, how the code is organized, and what to change before production
- [`my-app/README.md`](my-app/README.md): frontend scripts and structure

## Credits

This product uses the TMDB API but is not endorsed or certified by TMDB.
