export default defineEventHandler(async (event) => {
  const { user } = await requireUserSession(event)
  const movie = await readValidatedBody(event, validateMovie)

  const list = await getWatched(user.id)

  if (!list.some(m => m.id === movie.id)) {
    list.unshift({ ...movie, watchedAt: new Date().toISOString() })
    await setWatched(user.id, list)
  }

  return list
})
