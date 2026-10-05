export default defineEventHandler(async (event) => {
  const { username, password } = await readValidatedBody(event, validateCredentials)

  const user = await getUser(username)

  // Same message whether the username or the password is wrong, so nobody can probe which usernames exist
  if (!user || !(await verifyPassword(user.passwordHash, password))) {
    throw createError({ statusCode: 401, message: 'Invalid username or password' })
  }

  await setUserSession(event, { user: { id: user.id, username: user.username } })
  return { username: user.username }
})
