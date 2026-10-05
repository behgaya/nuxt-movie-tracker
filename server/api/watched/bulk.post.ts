const MAX_ITEMS = 500

function validateBody(body: any) {
  const add = body?.add ?? []
  const remove = body?.remove ?? []

  if (!Array.isArray(add) || !Array.isArray(remove) || add.length + remove.length > MAX_ITEMS) {
    throw createError({ statusCode: 400, statusMessage: `add and remove must be arrays (at most ${MAX_ITEMS} items in total)` })
  }
  if (!remove.every(Number.isInteger)) {
    throw createError({ statusCode: 400, statusMessage: 'remove must contain integer ids' })
  }

  return { add: add.map(validateMovie), remove: remove as number[] }
}

// Adds and/or removes many movies with a single read + write of the list
export default defineEventHandler(async (event) => {
  const { user } = await requireUserSession(event)
  const { add, remove } = await readValidatedBody(event, validateBody)

  const removeIds = new Set(remove)
  const list = (await getWatched(user.id)).filter(m => !removeIds.has(m.id))

  const existing = new Set(list.map(m => m.id))
  const watchedAt = new Date().toISOString()
  for (const movie of add) {
    if (!existing.has(movie.id)) {
      list.unshift({ ...movie, watchedAt })
      existing.add(movie.id)
    }
  }

  await setWatched(user.id, list)
  return list
})
