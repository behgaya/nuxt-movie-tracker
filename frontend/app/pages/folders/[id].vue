<script setup lang="ts">
// A non-numeric id (e.g. /folders/abc) fails validation and shows the 404 page without calling the API
definePageMeta({
  validate: route => /^\d+$/.test(String(route.params.id)),
})

const route = useRoute()
const id = Number(route.params.id)

// Public folders can be opened logged out too (see auth.global.ts), so the API decides who may see it
const { data: folder, error, refresh } = await useFetch<Folder>(`/api/folders/${id}`)

if (error.value || !folder.value) {
  throw createError({
    statusCode: error.value?.statusCode ?? 404,
    statusMessage: error.value?.data?.message ?? 'Folder not found',
    fatal: true,
  })
}

useSeoMeta({ title: () => folder.value?.name })

// Logged-out visitors get the movies without the watched / want buttons,
// and without asking the API for lists they have none of
const { loggedIn } = useAuth()
const watched = loggedIn.value ? useWatched() : null
const want = loggedIn.value ? useWantToWatch() : null
const folders = loggedIn.value ? useFolders() : null

// ── Owner actions (only the owner sees the buttons, and only the owner is logged in then) ──
const editing = ref(false)
const confirmDelete = ref(false)
const toast = useToast()

async function save(input: FolderInput) {
  await folders?.updateFolder(id, input)
  await refresh()
}

async function removeMovie(movieId: number) {
  await folders?.removeFromFolder(id, movieId)
  await refresh()
}

async function remove() {
  await folders?.deleteFolder(id)
  await navigateTo('/folders')
}

async function copyLink() {
  await navigator.clipboard.writeText(`${location.origin}/folders/${id}`)
  toast.show('Link copied')
}

// ── Selection: move to another folder, or remove ───────
const { selecting, selected, toggle: toggleSelected, selectAll, clear, stop: stopSelecting } = useSelection()
const selectedMovies = computed(() => folder.value?.movies.filter(m => selected.value.includes(m.id)) ?? [])
const confirmRemove = ref(false)

// The picker adds them to the other folder first; only then do they leave this one
async function afterMove() {
  await folders?.bulkUpdateFolder(id, { remove: selected.value })
  stopSelecting()
  await refresh()
}

async function removeSelected() {
  await folders?.bulkUpdateFolder(id, { remove: selected.value })
  stopSelecting()
  await refresh()
}
</script>

<template>
  <v-container v-if="folder">
    <div class="d-flex flex-wrap align-center ga-3 mt-4 mb-6">
      <div class="mr-auto">
        <h1 class="page-title text-h4">{{ folder.name }}</h1>
        <p class="text-medium-emphasis d-flex align-center ga-1">
          <v-icon :icon="folder.isPublic ? 'mdi-earth' : 'mdi-lock-outline'" size="16" />
          {{ folder.isPublic ? 'Public' : 'Private' }} folder
          <template v-if="!folder.isOwner">by {{ folder.owner }}</template>
          · {{ folder.movies.length }} movie{{ folder.movies.length === 1 ? '' : 's' }}
        </p>
      </div>
      <template v-if="folder.isOwner && !selecting">
        <v-btn v-if="folder.movies.length" variant="tonal" prepend-icon="mdi-checkbox-multiple-outline" @click="selecting = true">Select</v-btn>
        <v-btn v-if="folder.isPublic" variant="tonal" prepend-icon="mdi-link-variant" @click="copyLink">Copy link</v-btn>
        <v-btn variant="tonal" prepend-icon="mdi-pencil-outline" @click="editing = true">Edit</v-btn>
        <v-btn variant="tonal" color="error" icon="mdi-delete-outline" title="Delete folder" size="small" @click="confirmDelete = true" />
      </template>
    </div>

    <SelectionBar
      v-if="selecting"
      :count="selected.length"
      :total="folder.movies.length"
      @select-all="selectAll(folder.movies.map(m => m.id))"
      @clear="clear"
      @close="stopSelecting"
    >
      <FolderPickerMenu label="Move to folder" :movies="selectedMovies" :except-folder="id" :after="afterMove" />
      <v-btn color="error" variant="tonal" prepend-icon="mdi-folder-remove-outline" :disabled="!selected.length" @click="confirmRemove = true">
        Remove ({{ selected.length }})
      </v-btn>
    </SelectionBar>

    <MovieGrid
      v-if="folder.movies.length"
      :movies="folder.movies"
      :watched-by-id="watched?.watchedById.value ?? new Map()"
      :wanted-ids="want?.wantedIds.value"
      :readonly="!loggedIn"
      :selectable="selecting"
      :selected="selected"
      @select="toggleSelected"
      @toggle="m => watched?.toggle(m)"
      @want="m => want?.toggleWanted(m)"
      @review="(movieId, input) => watched?.saveReview(movieId, input)"
    >
      <template v-if="folder.isOwner && !selecting" #note="{ movie }">
        <v-btn variant="text" size="x-small" color="secondary" prepend-icon="mdi-folder-remove-outline" class="ml-n2 mt-1" @click="removeMovie(movie.id)">
          Remove from folder
        </v-btn>
      </template>
    </MovieGrid>

    <v-empty-state
      v-else
      icon="mdi-folder-open-outline"
      headline="This folder is empty"
      :text="folder.isOwner ? 'Open any movie and use “Add to folder”.' : 'Nothing has been added yet.'"
    >
      <template v-if="folder.isOwner" #actions>
        <v-btn color="primary" to="/movies">Browse movies</v-btn>
      </template>
    </v-empty-state>

    <template v-if="folder.isOwner">
      <FolderDialog v-model="editing" title="Edit folder" :folder="folder" :save="save" />
      <ConfirmDialog
        v-model="confirmDelete"
        :title="`Delete “${folder.name}”?`"
        text="The folder goes away. The movies stay in your watched and want-to-watch lists."
        confirm-text="Delete"
        color="error"
        @confirm="remove"
      />
      <ConfirmDialog
        v-model="confirmRemove"
        :title="`Remove ${selected.length} movie${selected.length === 1 ? '' : 's'} from “${folder.name}”?`"
        text="They stay in your watched and want-to-watch lists, and in your other folders."
        confirm-text="Remove"
        color="error"
        @confirm="removeSelected"
      />
    </template>
  </v-container>
</template>
