/** The logged-in user. The session itself is a cookie owned by the Symfony API. */
export function useAuth() {
  const user = useState<AuthUser | null>('auth-user', () => null)
  // During SSR this forwards the browser's cookie to the API; in the browser it is plain $fetch
  const requestFetch = useRequestFetch()

  // Asks the API who is logged in, e.g. right after logging in
  async function fetch() {
    const res = await requestFetch<{ user: AuthUser | null }>('/api/auth/me')
    user.value = res.user
  }

  // Deletes the session on the API
  async function clear() {
    await $fetch('/api/auth/logout', { method: 'POST' })
    user.value = null
  }

  return { user, loggedIn: computed(() => !!user.value), fetch, clear }
}
