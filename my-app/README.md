# Movie Tracker: frontend

The Nuxt 4 app for the [Movie Tracker](../README.md): pages, components and login state. All data comes from the Symfony API in [`../api`](../api).

Built with **Nuxt 4**, **Vue 3** and **Vuetify**.

## Run

Start the API first (see the [main README](../README.md#getting-started)), then:

```bash
npm install
npm run dev
```

Open http://localhost:3000.

The API is expected on http://localhost:8000. If it runs somewhere else, set `API_URL` in `.env` (see `.env.example`) before starting the dev server or building.

## Scripts

| Command           | What it does                         |
| ----------------- | ------------------------------------ |
| `npm run dev`     | Start the dev server with hot reload |
| `npm run build`   | Build for production                 |
| `npm run preview` | Preview the production build locally |

## How it talks to the API

Nuxt has no server routes of its own: `routeRules` in `nuxt.config.ts` proxies every `/api/**` request to Symfony, cookies included. The browser only talks to one origin, so there is no CORS setup and the Symfony session cookie works as is. During server-side rendering, the browser's cookie is forwarded too, so a logged-in user's data is in the first HTML.

## Project structure

```
app/
  pages/          index (watched list), movies (discover + details), login
  components/     MovieCard, MovieGrid, ReviewDialog, SelectionBar, ConfirmDialog
  composables/    useWatched (list state + API calls), useSelection, useAuth (logged-in user)
  plugins/        auth.ts: loads the logged-in user before the first page renders
  middleware/     auth.global.ts: redirects to /login when signed out
  utils/          tmdbImage: builds TMDB image URLs
shared/types/     types for the API's responses
```
