// Loads the logged-in user once, before the auth middleware runs. On a server-rendered
// page this happens on the server and the result is sent to the browser with the page.
export default defineNuxtPlugin(async (nuxtApp) => {
  if (import.meta.server || !nuxtApp.payload.serverRendered) {
    await useAuth().fetch()
  }
})
