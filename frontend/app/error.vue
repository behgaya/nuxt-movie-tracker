<script setup lang="ts">
import type { NuxtError } from '#app'

// Shown instead of app.vue for unknown URLs and fatal errors (e.g. a movie that doesn't exist)
const props = defineProps<{ error: NuxtError }>()

const notFound = computed(() => props.error.statusCode === 404)

// app.vue (and its title template) isn't rendered here, so write the full title
useSeoMeta({ title: () => `${notFound.value ? 'Not found' : 'Error'} · Movie Tracker` })

// clearError resets the error state, then navigates
const goHome = () => clearError({ redirect: '/' })
</script>

<template>
  <v-app>
    <NuxtLayout>
      <v-container class="d-flex flex-column align-center justify-center text-center" style="min-height: 70vh">
        <v-icon :icon="notFound ? 'mdi-movie-off-outline' : 'mdi-alert-circle-outline'" size="80" color="primary" />
        <div class="text-overline text-medium-emphasis mt-4">Error {{ error.statusCode }}</div>
        <h1 class="page-title text-h4 mb-2">
          {{ notFound ? 'This scene was cut' : 'Something went wrong' }}
        </h1>
        <p class="text-medium-emphasis mb-6" style="max-width: 48ch">
          {{ notFound ? 'The page or movie you are looking for does not exist.' : error.statusMessage || error.message }}
        </p>
        <v-btn color="primary" variant="flat" prepend-icon="mdi-home" @click="goHome">Back to my movies</v-btn>
      </v-container>
    </NuxtLayout>
  </v-app>
</template>
