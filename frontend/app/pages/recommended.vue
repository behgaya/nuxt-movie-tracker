<script setup lang="ts">
useSeoMeta({ title: 'For you' })

const { watched, watchedById, toggle, saveReview } = useWatched()
const { wantedIds, toggleWanted } = useWantToWatch()

// The API builds these from your watched list and ratings, so they are fetched fresh on every visit.
// lazy + server: false: the page shows straight away with placeholder cards, and the
// recommendations (several TMDB calls on a first visit) load in the browser afterwards.
const { data, status, error } = useLazyFetch<RecommendedMovie[]>('/api/recommendations', {
  server: false,
  default: () => [],
})
// 'idle' is the moment between the page appearing and the browser starting the request
const loading = computed(() => status.value === 'idle' || status.value === 'pending')

// A movie you mark as seen here moves to your watched list and leaves this page
const movies = computed(() => data.value.filter(m => !watchedById.value.has(m.id)))

// Cards are narrow, so name at most two movies and count the rest
function because(movie: RecommendedMovie) {
  const [first, second, ...rest] = movie.because
  if (!second) return first
  return rest.length ? `${first}, ${second} and ${rest.length} more` : `${first} and ${second}`
}
</script>

<template>
  <v-container>
    <div class="mt-4 mb-6">
      <h1 class="page-title text-h4">For you</h1>
      <p class="text-medium-emphasis">Based on what you've watched. Rate movies 4 or 5 stars to steer these.</p>
    </div>

    <!-- Placeholder cards in the same grid as the real ones, so nothing jumps when they arrive -->
    <div v-if="loading" class="movie-grid" aria-busy="true" aria-label="Loading recommendations">
      <v-skeleton-loader v-for="n in 12" :key="n" type="image, list-item-two-line, button" class="skeleton-card" />
    </div>

    <v-alert
      v-else-if="error"
      type="error"
      variant="tonal"
      :text="error.data?.message ?? 'Could not load recommendations, try again later'"
    />

    <v-empty-state
      v-else-if="!watched.length"
      icon="mdi-star-shooting-outline"
      headline="Nothing to go on yet"
      text="Mark a few movies you've seen, and recommendations will show up here."
    >
      <template #actions>
        <v-btn color="primary" to="/movies">Browse movies</v-btn>
      </template>
    </v-empty-state>

    <v-empty-state
      v-else-if="!movies.length"
      icon="mdi-movie-search-outline"
      headline="No recommendations right now"
      text="TMDB had nothing new for the movies in your list. Watch or rate a few more and check back."
    />

    <MovieGrid
      v-else
      :movies="movies"
      :watched-by-id="watchedById"
      :wanted-ids="wantedIds"
      @toggle="toggle"
      @want="toggleWanted"
      @review="saveReview"
    >
      <template #note="{ movie }">
        <!-- Hovering shows every movie that led to this one -->
        <div class="because mt-1" :title="`Because you watched ${movie.because.join(', ')}`">
          <span class="text-disabled">Because you watched</span>
          <span class="d-block text-medium-emphasis">{{ because(movie) }}</span>
        </div>
      </template>
    </MovieGrid>
  </v-container>
</template>

<style scoped>
/* Poster-shaped image block, like the 2:3 posters on real cards */
.skeleton-card :deep(.v-skeleton-loader__image) {
  height: auto;
  aspect-ratio: 2 / 3;
}

/* No line limit: the note grows with the titles, and cards in a row still match heights (h-100) */
.because {
  font-size: 0.6875rem; /* 11px, a step below the year caption above it */
  line-height: 1.35;
  overflow-wrap: anywhere; /* a very long title wraps instead of running off the card */
}
</style>
