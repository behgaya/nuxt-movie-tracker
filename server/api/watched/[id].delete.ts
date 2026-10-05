export default defineEventHandler(async (event) => {
  const { user } = await requireUserSession(event)
  const id = Number(getRouterParam(event, 'id'))
  if (!Number.isInteger(id)) {
    throw createError({ statusCode: 400, statusMessage: 'id must be an integer' })
  }

  const list = (await getWatched(user.id)).filter(m => m.id !== id)
  await setWatched(user.id, list)
  return list
})
