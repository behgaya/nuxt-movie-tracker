export default defineEventHandler(async (event) => {
  const { username, password } = await readValidatedBody(event, validateCredentials)

  if (await getUser(username)) {
    throw createError({ statusCode: 409, message: 'That username is already taken' })
  }

  const user = await createUser(username, password)

  // One-time migration: the list from before accounts existed goes to the first account created
  const storage = useStorage('data')
  const legacy = await storage.getItem<WatchedMovie[]>('watched')
  if (legacy) {
    await setWatched(user.id, legacy)
    await storage.removeItem('watched')
  }

  await setUserSession(event, { user: { id: user.id, username: user.username } })
  return { username: user.username }
})
