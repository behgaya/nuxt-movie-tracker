<script setup lang="ts">
const input = ref('')
const q = ref('')
const page = ref(1)

const { data, status } = await useFetch('/api/movies', {
  query: { q, page },
})

const { watchedById, toggle, bulkUpdate, saveReview } = useWatched()

function search() {
  q.value = input.value ?? ''
  page.value = 1
}

// ── Selection ──────────────────────────────────────────
const { selecting, selected, toggle: toggleSelected, selectAll, clear, stop: stopSelecting } = useSelection()
const confirmRemove = ref(false)

const results = computed(() => data.value?.results ?? [])
const selectedMovies = computed(() => results.value.filter(m => selected.value.includes(m.id)))
const toAdd = computed(() => selectedMovies.value.filter(m => !watchedById.value.has(m.id)))
const toRemove = computed(() => selectedMovies.value.filter(m => watchedById.value.has(m.id)))

// Selection only covers the movies on screen, so reset it when the results change
watch([q, page], clear)

async function addSelected() {
  await bulkUpdate({ add: toAdd.value })
  stopSelecting()
}

async function removeSelected() {
  await bulkUpdate({ remove: toRemove.value.map(m => m.id) })
  stopSelecting()
}
</script>

<template>
  <v-container>
    <div class="d-flex align-end mt-4 mb-6">
      <div>
        <h1 class="page-title text-h4">Discover</h1>
        <p class="text-medium-emphasis">{{ q ? `Results for “${q}”` : 'Popular right now' }}</p>
      </div>
      <v-spacer />
      <v-btn v-if="!selecting" variant="tonal" prepend-icon="mdi-checkbox-multiple-outline" @click="selecting = true">
        Select
      </v-btn>
    </div>

    <v-text-field
      v-model="input"
      label="Search movies"
      prepend-inner-icon="mdi-magnify"
      clearable
      @keyup.enter="search"
      @click:clear="input = ''; search()"
    />

    <SelectionBar
      v-if="selecting"
      :count="selected.length"
      :total="results.length"
      @select-all="selectAll(results.map(m => m.id))"
      @clear="clear"
      @close="stopSelecting"
    >
      <v-btn color="primary" variant="flat" prepend-icon="mdi-eye-plus" :disabled="!toAdd.length" @click="addSelected">
        Mark seen ({{ toAdd.length }})
      </v-btn>
      <v-btn color="error" variant="tonal" prepend-icon="mdi-delete-outline" :disabled="!toRemove.length" @click="confirmRemove = true">
        Remove ({{ toRemove.length }})
      </v-btn>
    </SelectionBar>

    <v-progress-linear v-if="status === 'pending'" indeterminate />

    <MovieGrid
      :movies="results"
      :watched-by-id="watchedById"
      :selectable="selecting"
      :selected="selected"
      @select="toggleSelected"
      @toggle="toggle"
      @review="saveReview"
    />

    <v-pagination
      v-model="page"
      :length="Math.min(data?.total_pages ?? 1, 500)"
      active-color="primary"
      rounded="circle"
      class="mt-6"
    />

    <ConfirmDialog
      v-model="confirmRemove"
      :title="`Remove ${toRemove.length} movie${toRemove.length === 1 ? '' : 's'}?`"
      text="They will be removed from your watched list, together with their ratings and reviews."
      confirm-text="Remove"
      color="error"
      @confirm="removeSelected"
    />
  </v-container>
</template>
