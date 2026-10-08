<script setup lang="ts">
// A non-numeric id (e.g. /movies/abc) fails validation and shows the 404 page without calling the API
definePageMeta({
  validate: route => /^\d+$/.test(String(route.params.id)),
})

const route = useRoute()
const id = Number(route.params.id)

const { data: movie, error } = await useFetch(`/api/movies/${id}`)

// Unknown movie or TMDB down: show error.vue instead of an empty page
if (error.value || !movie.value) {
  throw createError({
    statusCode: error.value?.statusCode ?? 404,
    statusMessage: error.value?.statusMessage ?? 'Movie not found',
    fatal: true,
  })
}

const year = computed(() => movie.value?.release_date?.slice(0, 4))
const runtime = computed(() => {
  const minutes = movie.value?.runtime
  return minutes ? `${Math.floor(minutes / 60)}h ${minutes % 60}m` : null
})
const directors = computed(() => movie.value?.credits.crew.filter(c => c.job === 'Director').map(c => c.name) ?? [])
const cast = computed(() => movie.value?.credits.cast.slice(0, 12) ?? [])

// Prefer an official YouTube trailer, fall back to any YouTube trailer
const trailer = computed(() => {
  const trailers = movie.value?.videos.results.filter(v => v.site === 'YouTube' && v.type === 'Trailer') ?? []
  return trailers.find(v => v.official) ?? trailers[0]
})
const showTrailer = ref(false)
// Built here, not in the template: Vue's type checker mangles `//` inside template expressions
const trailerUrl = computed(() => trailer.value && `https://www.youtube-nocookie.com/embed/${trailer.value.key}?autoplay=1`)

useSeoMeta({
  title: () => movie.value?.title,
  description: () => movie.value?.overview,
  ogTitle: () => movie.value?.title,
  ogDescription: () => movie.value?.overview,
  ogImage: () => movie.value?.backdrop_path ? tmdbImage(movie.value.backdrop_path, 'w1280') : undefined,
})

// ── Watched, want to watch & review ────────────────────
const { watchedById, toggle, saveReview } = useWatched()
const watchedEntry = computed(() => watchedById.value.get(id))
const { wantedIds, toggleWanted } = useWantToWatch()
const wanted = computed(() => wantedIds.value.has(id))
const reviewOpen = ref(false)
</script>

<template>
  <div v-if="movie">
    <section
      class="movie-hero"
      :style="movie.backdrop_path ? { backgroundImage: `url(${tmdbImage(movie.backdrop_path, 'w1280')})` } : undefined"
    >
      <v-container class="py-8 py-md-12">
        <v-btn variant="text" prepend-icon="mdi-arrow-left" class="mb-4 ml-n3" @click="$router.back()">Back</v-btn>

        <v-row>
          <v-col cols="5" sm="4" md="3">
            <v-img
              v-if="movie.poster_path"
              :src="tmdbImage(movie.poster_path, 'w500')"
              :aspect-ratio="2 / 3"
              class="detail-poster rounded-lg"
              cover
            />
            <v-sheet v-else :aspect-ratio="2 / 3" color="surface-variant" class="detail-poster rounded-lg d-flex align-center justify-center">
              <v-icon icon="mdi-image-off" size="64" color="secondary" />
            </v-sheet>
          </v-col>

          <v-col cols="12" sm="8" md="9">
            <h1 class="page-title text-h4 text-md-h3">
              {{ movie.title }}
              <span v-if="year" class="text-medium-emphasis font-weight-regular">({{ year }})</span>
            </h1>
            <p v-if="movie.tagline" class="text-body-1 font-italic text-medium-emphasis mt-1">{{ movie.tagline }}</p>

            <div class="d-flex flex-wrap align-center ga-2 mt-4">
              <v-chip v-if="movie.vote_average" color="primary" variant="flat" size="small" prepend-icon="mdi-star">
                {{ movie.vote_average.toFixed(1) }}
              </v-chip>
              <v-chip v-if="runtime" size="small" variant="tonal" prepend-icon="mdi-clock-outline">{{ runtime }}</v-chip>
              <v-chip v-for="genre in movie.genres" :key="genre.id" size="small" variant="outlined">{{ genre.name }}</v-chip>
            </div>

            <div class="d-flex flex-wrap ga-3 mt-6">
              <v-btn
                :color="watchedEntry ? 'success' : 'primary'"
                :variant="watchedEntry ? 'tonal' : 'flat'"
                :prepend-icon="watchedEntry ? 'mdi-check' : 'mdi-eye-plus'"
                @click="toggle(movie)"
              >
                {{ watchedEntry ? 'Watched' : 'Mark seen' }}
              </v-btn>
              <v-btn
                v-if="!watchedEntry"
                variant="tonal"
                :color="wanted ? 'primary' : undefined"
                :prepend-icon="wanted ? 'mdi-bookmark' : 'mdi-bookmark-outline'"
                @click="toggleWanted(movie)"
              >
                {{ wanted ? 'On your list' : 'Want to watch' }}
              </v-btn>
              <v-btn v-if="watchedEntry" variant="tonal" prepend-icon="mdi-star-outline" @click="reviewOpen = true">
                {{ watchedEntry.rating || watchedEntry.review ? 'Edit review' : 'Rate this movie' }}
              </v-btn>
              <AddToFolderMenu :movie="movie" />
              <v-btn v-if="trailer" variant="tonal" prepend-icon="mdi-play" @click="showTrailer = true">Trailer</v-btn>
            </div>

            <v-card v-if="watchedEntry?.rating || watchedEntry?.review" class="mt-6 pa-4" color="surface" max-width="640">
              <div class="text-overline text-medium-emphasis">Your review</div>
              <v-rating v-if="watchedEntry.rating" :model-value="watchedEntry.rating" color="primary" density="compact" readonly />
              <p v-if="watchedEntry.review" class="font-italic mt-1">“{{ watchedEntry.review }}”</p>
            </v-card>

            <h2 class="text-h6 font-weight-bold mt-6 mb-2">Overview</h2>
            <p class="text-body-1" style="max-width: 72ch">{{ movie.overview || 'No overview available.' }}</p>

            <p v-if="directors.length" class="mt-4">
              <span class="text-medium-emphasis">Directed by</span> <strong>{{ directors.join(', ') }}</strong>
            </p>
          </v-col>
        </v-row>
      </v-container>
    </section>

    <v-container v-if="cast.length" class="pb-12">
      <h2 class="text-h6 font-weight-bold mb-4">Top cast</h2>
      <div class="cast-scroller">
        <div v-for="actor in cast" :key="actor.id" class="cast-member">
          <v-avatar size="104" color="surface-variant" rounded="lg">
            <v-img v-if="actor.profile_path" :src="tmdbImage(actor.profile_path, 'w185')" cover />
            <v-icon v-else icon="mdi-account" size="48" color="secondary" />
          </v-avatar>
          <div class="text-body-2 font-weight-bold mt-2">{{ actor.name }}</div>
          <div class="text-caption text-medium-emphasis">{{ actor.character }}</div>
        </div>
      </div>
    </v-container>

    <v-dialog v-model="showTrailer" max-width="960">
      <v-card v-if="trailer" rounded="xl">
        <v-responsive :aspect-ratio="16 / 9">
          <!-- Mounted only while the dialog is open, so the video stops when it closes -->
          <iframe
            :src="trailerUrl"
            title="Trailer"
            allow="autoplay; encrypted-media; picture-in-picture"
            allowfullscreen
            style="border: 0; width: 100%; height: 100%"
          />
        </v-responsive>
      </v-card>
    </v-dialog>

    <ReviewDialog v-model="reviewOpen" :movie="watchedEntry ?? null" @save="input => saveReview(id, input)" />
  </div>
</template>
