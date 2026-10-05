// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15', // keep whatever date your project already has
  devtools: { enabled: true },

  modules: ['vuetify-nuxt-module', 'nuxt-auth-utils'],
  css: ['~/assets/css/main.css'],

  vuetify: {
    vuetifyOptions: {
      theme: {
        defaultTheme: 'cinema',
        themes: {
          cinema: {
            dark: true,
            colors: {
              'background': '#0E0F13',
              'surface': '#171920',
              'surface-variant': '#22252E',
              'primary': '#F5B50A',
              'on-primary': '#111111',
              'secondary': '#8B93A7',
              'success': '#2EBD85',
              'error': '#EF5350',
            },
          },
        },
      },
      // Shared component defaults, so pages don't repeat these props
      defaults: {
        VCard: { rounded: 'lg', elevation: 0 },
        VTextField: { variant: 'solo-filled', flat: true, rounded: 'lg' },
        VSelect: { variant: 'solo-filled', flat: true, rounded: 'lg' },
        VBtn: { rounded: 'lg' },
      },
    },
  },

  runtimeConfig: {
    tmdbToken: '', // filled from NUXT_TMDB_TOKEN in .env, only available on the server
  },
})
