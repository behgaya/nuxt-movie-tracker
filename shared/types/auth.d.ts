// Tells nuxt-auth-utils what we keep in the session cookie, so session.user is typed
declare module '#auth-utils' {
  interface User {
    id: string
    username: string
  }
}

export {}
