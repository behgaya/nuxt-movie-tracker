<script setup lang="ts">
// A selection-bar button: pick a folder (or make a new one) and the selected movies go into it
const props = defineProps<{
  movies: MovieInput[]
  label?: string // defaults to "Add to folder"
  exceptFolder?: number // left out of the list, e.g. the folder the movies are moved from
  // Runs after the movies are in the folder, e.g. to remove them from where they came from
  after?: (folder: FolderSummary) => Promise<unknown> | void
}>()

const { folders, createFolder, bulkUpdateFolder } = useFolders()

const choices = computed(() => folders.value.filter(f => f.id !== props.exceptFolder))
const toast = useToast()

async function putIn(folder: FolderSummary) {
  const count = props.movies.length
  await bulkUpdateFolder(folder.id, { add: props.movies })
  await props.after?.(folder)
  toast.show(`${props.label?.startsWith('Move') ? 'Moved' : 'Added'} ${count} movie${count === 1 ? '' : 's'} to “${folder.name}”`)
}

const creating = ref(false)
async function createAndPutIn(input: FolderInput) {
  await createFolder(input)
  const folder = folders.value.find(f => f.name === input.name)
  if (folder) await putIn(folder)
}
</script>

<template>
  <v-menu location="bottom end">
    <template #activator="{ props: activator }">
      <v-btn v-bind="activator" color="primary" variant="tonal" prepend-icon="mdi-folder-move-outline" :disabled="!movies.length">
        {{ label ?? 'Add to folder' }} ({{ movies.length }})
      </v-btn>
    </template>
    <v-list density="compact" min-width="220" max-height="360">
      <v-list-item v-for="folder in choices" :key="folder.id" :title="folder.name" prepend-icon="mdi-folder-outline" @click="putIn(folder)" />
      <v-divider v-if="choices.length" class="my-1" />
      <v-list-item title="New folder…" prepend-icon="mdi-plus" @click="creating = true" />
    </v-list>
  </v-menu>

  <FolderDialog v-model="creating" title="New folder" :save="createAndPutIn" />
</template>
