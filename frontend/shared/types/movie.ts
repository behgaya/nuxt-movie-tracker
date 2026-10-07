export interface WatchedMovie {
  id: number
  title: string
  poster_path: string | null
  release_date?: string
  watchedAt: string
  rating?: number // 1–5
  review?: string // up to 1000 chars
  reviewedAt?: string // ISO date of the last review edit
}

/** What the review dialog sends; null / '' clear the field */
export interface ReviewInput {
  rating: number | null
  review: string
}

/** A movie as returned by TMDB's /movie/popular and /search/movie (only the fields we use) */
export interface TmdbMovie {
  id: number
  title: string
  poster_path: string | null
  release_date?: string
  overview: string
  vote_average: number // TMDB user score, 0–10
  vote_count: number
}

/** One movie from GET /api/recommendations; "because" holds the titles of the watched movies that led to it */
export interface RecommendedMovie extends TmdbMovie {
  because: string[]
}

export interface TmdbPage {
  page: number
  results: TmdbMovie[]
  total_pages: number
  total_results: number
}

/** TMDB's /movie/{id} with credits and videos appended (only the fields we use) */
export interface TmdbMovieDetails extends TmdbMovie {
  backdrop_path: string | null
  tagline: string
  runtime: number | null
  genres: { id: number, name: string }[]
  credits: {
    cast: { id: number, name: string, character: string, profile_path: string | null }[]
    crew: { id: number, name: string, job: string }[]
  }
  videos: {
    results: { key: string, site: string, type: string, official: boolean }[]
  }
}
