<script setup lang="ts" generic="T extends { id: number, title: string, poster_path: string | null }">
const props = defineProps<{
  movies: T[]
  watchedById: Map<number, WatchedMovie>
  // Pass it to show a want-to-watch button on unwatched cards
  wantedIds?: Set<number>
  selectable?: boolean
  selected?: number[]
  readonly?: boolean
}>()

const emit = defineEmits<{
  toggle: [movie: T]
  want: [movie: T]
  review: [id: number, input: ReviewInput]
  select: [id: number]
}>()

// Optional line under each card's title, filled by the page: <template #note="{ movie }">
defineSlots<{ note?: (props: { movie: T }) => any }>()

// One dialog shared by every card in the grid
const dialogOpen = ref(false)
const reviewing = ref<WatchedMovie | null>(null)

function openReview(id: number) {
  reviewing.value = props.watchedById.get(id) ?? null
  dialogOpen.value = !!reviewing.value
}
</script>

<template>
  <!-- As many columns as fit at a minimum card width (main.css), so smaller screens show fewer, wider cards -->
  <div class="movie-grid">
    <div v-for="movie in movies" :key="movie.id">
      <MovieCard
        :movie="movie"
        :watched="watchedById.has(movie.id)"
        :wanted="wantedIds?.has(movie.id)"
        :review="watchedById.get(movie.id)"
        :selectable="selectable"
        :readonly="readonly"
        :selected="selected?.includes(movie.id)"
        @toggle="emit('toggle', movie)"
        @want="emit('want', movie)"
        @review="openReview(movie.id)"
        @select="emit('select', movie.id)"
      >
        <slot name="note" :movie="movie" />
      </MovieCard>
    </div>
  </div>

  <ReviewDialog
    v-model="dialogOpen"
    :movie="reviewing"
    @save="input => reviewing && emit('review', reviewing.id, input)"
  />
</template>
