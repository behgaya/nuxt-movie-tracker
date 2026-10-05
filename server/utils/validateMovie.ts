/** Checks one movie from a request body and keeps only the fields we store */
export function validateMovie(body: any): Omit<WatchedMovie, 'watchedAt'> {
  if (!Number.isInteger(body?.id) || typeof body?.title !== 'string' || !body.title.trim()) {
    throw createError({ statusCode: 400, statusMessage: 'id (integer) and title (non-empty string) are required' })
  }

  return {
    id: body.id,
    title: body.title.trim(),
    poster_path: typeof body.poster_path === 'string' ? body.poster_path : null,
    release_date: typeof body.release_date === 'string' ? body.release_date : undefined,
  }
}
