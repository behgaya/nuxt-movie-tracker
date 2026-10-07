<script setup lang="ts" generic="T extends { id: number, title: string, poster_path: string | null }">
const props = defineProps<{
  movies: T[]
  watchedById: Map<number, WatchedMovie>
  selectable?: boolean
  selected?: number[]
}>()

const emit = defineEmits<{
  toggle: [movie: T]
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
  <v-row>
    <v-col v-for="movie in movies" :key="movie.id" cols="6" sm="4" md="3" lg="2">
      <MovieCard
        :movie="movie"
        :watched="watchedById.has(movie.id)"
        :review="watchedById.get(movie.id)"
        :selectable="selectable"
        :selected="selected?.includes(movie.id)"
        @toggle="emit('toggle', movie)"
        @review="openReview(movie.id)"
        @select="emit('select', movie.id)"
      >
        <slot name="note" :movie="movie" />
      </MovieCard>
    </v-col>
  </v-row>

  <ReviewDialog
    v-model="dialogOpen"
    :movie="reviewing"
    @save="input => reviewing && emit('review', reviewing.id, input)"
  />
</template>
