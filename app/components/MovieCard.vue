<script setup lang="ts">
defineProps<{
  movie: { id: number, title: string, poster_path: string | null, release_date?: string }
  watched: boolean
  review?: { rating?: number, review?: string }
  selectable?: boolean
  selected?: boolean
}>()

defineEmits<{ toggle: [], review: [], select: [] }>()
</script>

<template>
  <v-card class="movie-card h-100 d-flex flex-column" :class="{ 'is-selected': selected }">
    <!-- In selection mode the whole poster is a big click target -->
    <div class="poster" :class="{ 'cursor-pointer': selectable }" @click="selectable && $emit('select')">
      <v-checkbox-btn
        v-if="selectable"
        :model-value="selected"
        color="primary"
        class="select-box"
        @click.stop="$emit('select')"
      />
      <v-img
        v-if="movie.poster_path"
        :src="`https://image.tmdb.org/t/p/w500${movie.poster_path}`"
        :aspect-ratio="2 / 3"
        cover
      />
      <v-sheet v-else :aspect-ratio="2 / 3" color="surface-variant" class="d-flex align-center justify-center">
        <v-icon icon="mdi-image-off" size="48" color="secondary" />
      </v-sheet>
      <v-avatar v-if="watched" color="success" size="28" class="watched-badge">
        <v-icon icon="mdi-check" size="18" />
      </v-avatar>
    </div>

    <div class="px-3 pt-2">
      <div class="movie-title text-body-1">{{ movie.title }}</div>
      <div v-if="movie.release_date" class="text-caption text-medium-emphasis">{{ movie.release_date.slice(0, 4) }}</div>
    </div>

    <!-- Reviews are only for watched movies; clicking opens the review dialog -->
    <div v-if="watched" class="px-3 pt-1 review-trigger" role="button" tabindex="0" @click="$emit('review')" @keyup.enter="$emit('review')">
      <v-rating v-if="review?.rating" :model-value="review.rating" color="primary" density="compact" size="small" readonly />
      <span v-else class="text-caption text-primary">
        <v-icon icon="mdi-star-outline" size="small" /> Rate this movie
      </span>
      <p v-if="review?.review" class="text-caption font-italic text-medium-emphasis review-snippet">
        “{{ review.review }}”
      </p>
    </div>

    <v-spacer />

    <v-card-actions class="px-3 pb-3">
      <v-btn
        block
        size="small"
        variant="tonal"
        :color="watched ? 'success' : 'secondary'"
        :prepend-icon="watched ? 'mdi-check' : 'mdi-eye-outline'"
        @click="$emit('toggle')"
      >
        {{ watched ? 'Watched' : 'Mark seen' }}
      </v-btn>
    </v-card-actions>
  </v-card>
</template>
