<script setup lang="ts">
const props = defineProps<{
  movie: MovieInput
  compact?: boolean // a small round icon button, for the corner of a movie card
}>()

const { folders, createFolder, addToFolder, removeFromFolder } = useFolders()

const inFolders = computed(() => folders.value.filter(f => f.movieIds.includes(props.movie.id)).length)

function toggle(folder: FolderSummary) {
  return folder.movieIds.includes(props.movie.id)
    ? removeFromFolder(folder.id, props.movie.id)
    : addToFolder(folder.id, props.movie)
}

// A folder made from this menu gets the movie straight away
const creating = ref(false)
async function createWithMovie(input: FolderInput) {
  await createFolder(input)
  const folder = folders.value.find(f => f.name === input.name)
  if (folder) await addToFolder(folder.id, props.movie)
}
</script>

<template>
  <v-menu :close-on-content-click="false" location="bottom start">
    <template #activator="{ props: activator }">
      <v-btn
        v-if="compact"
        v-bind="activator"
        :icon="inFolders ? 'mdi-folder' : 'mdi-folder-plus-outline'"
        :color="inFolders ? 'primary' : undefined"
        :title="inFolders ? `In ${inFolders} folder${inFolders === 1 ? '' : 's'}` : 'Add to folder'"
        size="small"
        density="comfortable"
        variant="text"
        class="card-corner-btn card-corner-left"
      />
      <v-btn v-else v-bind="activator" variant="tonal" :prepend-icon="inFolders ? 'mdi-folder' : 'mdi-folder-plus-outline'">
        {{ inFolders ? `In ${inFolders} folder${inFolders === 1 ? '' : 's'}` : 'Add to folder' }}
      </v-btn>
    </template>
    <v-list density="compact" min-width="240" max-height="360">
      <v-list-item
        v-for="folder in folders"
        :key="folder.id"
        :title="folder.name"
        @click="toggle(folder)"
      >
        <template #prepend>
          <v-checkbox-btn :model-value="folder.movieIds.includes(movie.id)" color="primary" density="compact" tabindex="-1" class="mr-2" />
        </template>
      </v-list-item>
      <v-divider v-if="folders.length" class="my-1" />
      <v-list-item title="New folder…" prepend-icon="mdi-plus" @click="creating = true" />
    </v-list>
  </v-menu>

  <FolderDialog v-model="creating" title="New folder" :save="createWithMovie" />
</template>
