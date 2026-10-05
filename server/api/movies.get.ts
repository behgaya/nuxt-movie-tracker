function validateQuery(query: Record<string, unknown>) {
  const q = typeof query.q === 'string' ? query.q.trim() : ''
  const page = query.page === undefined ? 1 : Number(query.page)

  // TMDB only serves pages 1–500
  if (!Number.isInteger(page) || page < 1 || page > 500) {
    throw createError({ statusCode: 400, statusMessage: 'page must be an integer between 1 and 500' })
  }

  return { q, page }
}

// Cached for one hour per search term + page. Only the TMDB call is cached (not the whole
// route), so the login check below still runs on every request.
const fetchTmdb = defineCachedFunction(async (q: string, page: number) => {
  const { tmdbToken } = useRuntimeConfig()
  const path = q ? '/search/movie' : '/movie/popular'

  // v4 read access tokens are JWTs (contain dots); v3 API keys are 32-char hex strings
  const isV4 = tmdbToken.includes('.')

  return $fetch<TmdbPage>(`https://api.themoviedb.org/3${path}`, {
    headers: isV4 ? { Authorization: `Bearer ${tmdbToken}` } : {},
    query: { query: q || undefined, page, ...(isV4 ? {} : { api_key: tmdbToken }) },
  })
}, { maxAge: 60 * 60, name: 'tmdb', getKey: (q: string, page: number) => `${q}:${page}` })

export default defineEventHandler(async (event) => {
  // Logged-in users only, so nobody can use your TMDB key through this route
  await requireUserSession(event)
  const { q, page } = await getValidatedQuery(event, validateQuery)
  return fetchTmdb(q, page)
})
