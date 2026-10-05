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
  vote_average: number
}

export interface TmdbPage {
  page: number
  results: TmdbMovie[]
  total_pages: number
  total_results: number
}
