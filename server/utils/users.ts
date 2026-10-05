export interface StoredUser {
  id: string
  username: string
  passwordHash: string
  createdAt: string
}

export function getUser(username: string) {
  return useStorage('data').getItem<StoredUser>(`users:${username}`)
}

export async function createUser(username: string, password: string): Promise<StoredUser> {
  const user: StoredUser = {
    id: crypto.randomUUID(),
    username,
    passwordHash: await hashPassword(password),
    createdAt: new Date().toISOString(),
  }
  await useStorage('data').setItem(`users:${username}`, user)
  return user
}

/** Checks the login/register body: username 3–30 chars of a-z 0-9 _, password at least 8 chars */
export function validateCredentials(body: any) {
  const username = typeof body?.username === 'string' ? body.username.trim().toLowerCase() : ''
  const password = typeof body?.password === 'string' ? body.password : ''

  if (!/^[a-z0-9_]{3,30}$/.test(username)) {
    throw createError({ statusCode: 400, message: 'Username must be 3–30 characters: letters, numbers or _' })
  }
  if (password.length < 8 || password.length > 200) {
    throw createError({ statusCode: 400, message: 'Password must be at least 8 characters' })
  }

  return { username, password }
}
