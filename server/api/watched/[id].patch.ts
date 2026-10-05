function validateBody(body: any): ReviewInput {
  const rating = body?.rating ?? null
  const review = typeof body?.review === 'string' ? body.review.trim() : ''

  if (rating !== null && (!Number.isInteger(rating) || rating < 1 || rating > 5)) {
    throw createError({ statusCode: 400, statusMessage: 'rating must be an integer from 1 to 5, or null' })
  }
  if (review.length > 1000) {
    throw createError({ statusCode: 400, statusMessage: 'review must be at most 1000 characters' })
  }

  return { rating, review }
}

export default defineEventHandler(async (event) => {
  const { user } = await requireUserSession(event)
  const id = Number(getRouterParam(event, 'id'))
  if (!Number.isInteger(id)) {
    throw createError({ statusCode: 400, statusMessage: 'id must be an integer' })
  }
  const { rating, review } = await readValidatedBody(event, validateBody)

  const list = await getWatched(user.id)
  const movie = list.find(m => m.id === id)

  // Only watched movies can be reviewed
  if (!movie) {
    throw createError({ statusCode: 404, statusMessage: 'Movie is not in the watched list' })
  }

  movie.rating = rating ?? undefined
  movie.review = review || undefined
  movie.reviewedAt = new Date().toISOString()
  await setWatched(user.id, list)

  return list
})
