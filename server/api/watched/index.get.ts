export default defineEventHandler(async (event) => {
  const { user } = await requireUserSession(event)
  const list = await getWatched(user.id)
  return list.sort((a, b) => b.watchedAt.localeCompare(a.watchedAt))
})
