<script setup lang="ts">
useSeoMeta({ title: 'Watched' })

const { watched, status, watchedById, unmarkWatched, bulkUpdate, saveReview } = useWatched()

const filter = ref('')
const sortBy = ref<'recent' | 'rating' | 'title'>('recent')

const sortOptions = [
  { title: 'Recently watched', value: 'recent' },
  { title: 'Highest rated', value: 'rating' },
  { title: 'Title', value: 'title' },
]

const filtered = computed(() => {
  const term = (filter.value ?? '').toLowerCase()
  const list = term ? watched.value.filter(m => m.title.toLowerCase().includes(term)) : [...watched.value]

  if (sortBy.value === 'rating') {
    // Unrated movies (rating undefined → 0) end up last
    list.sort((a, b) => (b.rating ?? 0) - (a.rating ?? 0))
  }
  else if (sortBy.value === 'title') {
    list.sort((a, b) => a.title.localeCompare(b.title))
  }
  // 'recent' keeps the server order (newest first)

  return list
})

// ── Selection ──────────────────────────────────────────
const { selecting, selected, toggle: toggleSelected, selectAll, clear, stop: stopSelecting } = useSelection()
const confirmRemove = ref(false)

// Drop ids hidden by the filter, so a mass remove only affects what you can see
watch(filtered, (list) => {
  const visible = new Set(list.map(m => m.id))
  selected.value = selected.value.filter(id => visible.has(id))
})

async function removeSelected() {
  await bulkUpdate({ remove: selected.value })
  stopSelecting()
}
</script>

<template>
  <v-container>
    <div class="d-flex align-center ga-3 mt-4 mb-6">
      <h1 class="page-title text-h4">My watched movies</h1>
      <v-chip color="primary" variant="tonal" size="small">{{ watched.length }}</v-chip>
      <v-spacer />
      <v-btn
        v-if="watched.length && !selecting"
        variant="tonal"
        prepend-icon="mdi-checkbox-multiple-outline"
        @click="selecting = true"
      >
        Select
      </v-btn>
    </div>

    <v-progress-linear v-if="status === 'pending'" indeterminate />

    <template v-else-if="watched.length">
      <v-row>
        <v-col cols="12" sm="7" md="8">
          <v-text-field
            v-model="filter"
            label="Filter my movies"
            prepend-inner-icon="mdi-filter-variant"
            clearable
          />
        </v-col>
        <v-col cols="12" sm="5" md="4">
          <v-select v-model="sortBy" :items="sortOptions" label="Sort by" prepend-inner-icon="mdi-sort" />
        </v-col>
      </v-row>

      <SelectionBar
        v-if="selecting"
        :count="selected.length"
        :total="filtered.length"
        @select-all="selectAll(filtered.map(m => m.id))"
        @clear="clear"
        @close="stopSelecting"
      >
        <v-btn color="error" variant="flat" prepend-icon="mdi-delete-outline" :disabled="!selected.length" @click="confirmRemove = true">
          Remove ({{ selected.length }})
        </v-btn>
      </SelectionBar>

      <MovieGrid
        :movies="filtered"
        :watched-by-id="watchedById"
        :selectable="selecting"
        :selected="selected"
        @select="toggleSelected"
        @toggle="m => unmarkWatched(m.id)"
        @review="saveReview"
      />
    </template>

    <v-empty-state
      v-else
      icon="mdi-movie-open-outline"
      headline="No watched movies yet"
      text="Browse all movies and mark the ones you've seen."
    >
      <template #actions>
        <v-btn color="primary" to="/movies">Browse movies</v-btn>
      </template>
    </v-empty-state>

    <ConfirmDialog
      v-model="confirmRemove"
      :title="`Remove ${selected.length} movie${selected.length === 1 ? '' : 's'}?`"
      text="They will be removed from your watched list, together with their ratings and reviews."
      confirm-text="Remove"
      color="error"
      @confirm="removeSelected"
    />
  </v-container>
</template>
