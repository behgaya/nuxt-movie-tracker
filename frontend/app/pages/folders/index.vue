<script setup lang="ts">
useSeoMeta({ title: 'Folders' })

const { folders, status, createFolder } = useFolders()
const creating = ref(false)
</script>

<template>
  <v-container>
    <div class="d-flex align-center ga-3 mt-4 mb-6">
      <h1 class="page-title text-h4">Folders</h1>
      <v-chip color="primary" variant="tonal" size="small">{{ folders.length }}</v-chip>
      <v-spacer />
      <v-btn color="primary" variant="flat" prepend-icon="mdi-folder-plus-outline" @click="creating = true">New folder</v-btn>
    </div>

    <v-progress-linear v-if="status === 'pending'" indeterminate />

    <div v-else-if="folders.length" class="movie-grid">
      <v-card v-for="folder in folders" :key="folder.id" :to="`/folders/${folder.id}`" class="movie-card">
        <!-- Cover: up to four posters in a 2×2 grid -->
        <div class="folder-cover">
          <v-img v-for="poster in folder.posters" :key="poster" :src="tmdbImage(poster, 'w185')" cover />
          <div v-if="!folder.posters.length" class="folder-cover-empty d-flex align-center justify-center">
            <v-icon icon="mdi-folder-outline" size="48" color="secondary" />
          </div>
        </div>
        <div class="pa-3">
          <div class="movie-title text-body-1">{{ folder.name }}</div>
          <div class="text-caption text-medium-emphasis d-flex align-center ga-1">
            <v-icon :icon="folder.isPublic ? 'mdi-earth' : 'mdi-lock-outline'" size="14" />
            {{ folder.isPublic ? 'Public' : 'Private' }} · {{ folder.movieIds.length }} movie{{ folder.movieIds.length === 1 ? '' : 's' }}
          </div>
        </div>
      </v-card>
    </div>

    <v-empty-state
      v-else
      icon="mdi-folder-multiple-outline"
      headline="No folders yet"
      text="Make folders like “Favorites” or “Horror night”, then add movies from any movie's page."
    >
      <template #actions>
        <v-btn color="primary" @click="creating = true">New folder</v-btn>
      </template>
    </v-empty-state>

    <FolderDialog v-model="creating" title="New folder" :save="createFolder" />
  </v-container>
</template>

<style scoped>
.folder-cover {
  display: grid;
  grid-template-columns: 1fr 1fr;
  aspect-ratio: 4 / 3;
  background: rgb(var(--v-theme-surface-variant));
  overflow: hidden;
}

/* With fewer than four posters, the empty cells keep the background colour */
.folder-cover-empty {
  grid-column: 1 / -1;
  grid-row: 1 / 3;
}
</style>
