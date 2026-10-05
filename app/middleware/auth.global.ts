// Runs before every page. Only a convenience redirect: the real protection is
// requireUserSession() in the server API routes.
export default defineNuxtRouteMiddleware((to) => {
  const { loggedIn } = useUserSession()

  if (!loggedIn.value && to.path !== '/login') {
    return navigateTo('/login')
  }
  if (loggedIn.value && to.path === '/login') {
    return navigateTo('/')
  }
})
