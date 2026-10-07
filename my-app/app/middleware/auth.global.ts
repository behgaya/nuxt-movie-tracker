// Runs before every page. Only a convenience redirect: the real protection is
// access_control in the Symfony API (api/config/packages/security.yaml).
export default defineNuxtRouteMiddleware((to) => {
  const { loggedIn } = useAuth()

  if (!loggedIn.value && to.path !== '/login') {
    return navigateTo('/login')
  }
  if (loggedIn.value && to.path === '/login') {
    return navigateTo('/')
  }
})
