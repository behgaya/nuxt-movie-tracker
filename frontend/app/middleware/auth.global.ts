// Runs before every page. Only a convenience redirect: the real protection is
// access_control in the Symfony API (api/config/packages/security.yaml).
export default defineNuxtRouteMiddleware((to) => {
  const { loggedIn } = useAuth()

  // Public folders can be shared with people who have no account; the API hides private ones
  const isFolderPage = /^\/folders\/\d+$/.test(to.path)

  if (!loggedIn.value && to.path !== '/login' && !isFolderPage) {
    return navigateTo('/login')
  }
  if (loggedIn.value && to.path === '/login') {
    return navigateTo('/')
  }
})
