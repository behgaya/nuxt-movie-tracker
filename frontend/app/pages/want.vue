<script setup lang="ts">
useSeoMeta({ title: 'Want to watch' })

const { wanted, status, wantedIds, removeWanted } = useWantToWatch()
const { watchedById, markWatched, saveReview } = useWatched()

const filter = ref('')

const filtered = computed(() => {
  const term = (filter.value ?? '').toLowerCase()
  // Server order: most recently added first
  return term ? wanted.value.filter(m => m.title.toLowerCase().includes(term)) : wanted.value
})
</script>

<template>
  <v-container>
    <div class="d-flex align-center ga-3 mt-4 mb-6">
      <div>
        <h1 class="page-title text-h4">Want to watch</h1>
        <p class="text-medium-emphasis">Mark one as seen and it moves to your watched list.</p>
      </div>
      <v-chip color="primary" variant="tonal" size="small">{{ wanted.length }}</v-chip>
    </div>

    <v-progress-linear v-if="status === 'pending'" indeterminate />

    <template v-else-if="wanted.length">
      <v-text-field
        v-model="filter"
        label="Filter my list"
        prepend-inner-icon="mdi-filter-variant"
        clearable
      />

      <!-- "Mark seen" moves the movie to the watched list, so it leaves this page -->
      <MovieGrid
        :movies="filtered"
        :watched-by-id="watchedById"
        :wanted-ids="wantedIds"
        @toggle="markWatched"
        @want="m => removeWanted(m.id)"
        @review="saveReview"
      />
    </template>

    <v-empty-state
      v-else
      icon="mdi-bookmark-outline"
      headline="Nothing on your list yet"
      text="Browse all movies and tap the bookmark on the ones you want to see."
    >
      <template #actions>
        <v-btn color="primary" to="/movies">Browse movies</v-btn>
      </template>
    </v-empty-state>
  </v-container>
</template>
