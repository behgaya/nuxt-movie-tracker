<script setup lang="ts">
const props = defineProps<{
  movie: { id: number, title: string, poster_path: string | null, release_date?: string, vote_average?: number, vote_count?: number }
  watched: boolean
  // Leave undefined to hide the want-to-watch button
  wanted?: boolean
  review?: { rating?: number, review?: string }
  selectable?: boolean
  selected?: boolean
  // No review or buttons, e.g. for a logged-out visitor on a public folder
  readonly?: boolean
}>()

defineEmits<{ toggle: [], want: [], review: [], select: [] }>()

// The poster opens the detail page, except in selection mode where a click selects the card
const NuxtLink = resolveComponent('NuxtLink')

const year = computed(() => props.movie.release_date?.slice(0, 4))

// TMDB's user score (0–10). Hidden below 50 votes, where a 10.0 from a handful of people would mislead.
// Watched movies don't store it, so cards on the Watched page show no score.
const MIN_VOTES = 50
const score = computed(() => (props.movie.vote_count ?? 0) >= MIN_VOTES ? props.movie.vote_average?.toFixed(1) : undefined)

// Only unwatched movies can be wanted: watching one moves it to the watched list
const showWant = computed(() => !props.watched && props.wanted !== undefined)
</script>

<template>
  <v-card class="movie-card h-100 d-flex flex-column" :class="{ 'is-selected': selected }">
    <!-- In selection mode the whole poster is a big click target -->
    <component
      :is="selectable ? 'div' : NuxtLink"
      :to="selectable ? undefined : `/movies/${movie.id}`"
      class="poster d-block cursor-pointer"
      @click="selectable && $emit('select')"
    >
      <v-checkbox-btn
        v-if="selectable"
        :model-value="selected"
        color="primary"
        class="select-box"
        @click.stop="$emit('select')"
      />
      <v-img
        v-if="movie.poster_path"
        :src="tmdbImage(movie.poster_path, 'w500')"
        :aspect-ratio="2 / 3"
        cover
      />
      <v-sheet v-else :aspect-ratio="2 / 3" color="surface-variant" class="d-flex align-center justify-center">
        <v-icon icon="mdi-image-off" size="48" color="secondary" />
      </v-sheet>
      <v-avatar v-if="watched" color="success" size="28" class="watched-badge">
        <v-icon icon="mdi-check" size="18" />
      </v-avatar>
    </component>

    <!-- Corner buttons sit outside the poster link, so clicking them doesn't open the movie.
         Folders top-left; top-right is the bookmark, or the watched badge once it is watched. -->
    <template v-if="!readonly && !selectable">
      <AddToFolderMenu :movie="movie" compact />
      <v-btn
        v-if="showWant"
        :icon="wanted ? 'mdi-bookmark' : 'mdi-bookmark-outline'"
        :color="wanted ? 'primary' : undefined"
        :title="wanted ? 'Remove from Want to watch' : 'Want to watch'"
        :aria-pressed="wanted"
        size="small"
        density="comfortable"
        variant="text"
        class="card-corner-btn card-corner-right"
        @click="$emit('want')"
      />
    </template>

    <div class="px-3 pt-2">
      <NuxtLink :to="`/movies/${movie.id}`" class="movie-title text-body-1">{{ movie.title }}</NuxtLink>
      <div v-if="year || score" class="text-caption text-medium-emphasis d-flex align-center ga-2">
        <span v-if="year">{{ year }}</span>
        <span v-if="score" class="d-inline-flex align-center" :title="`TMDB user score, from ${movie.vote_count?.toLocaleString()} votes`">
          <v-icon icon="mdi-star" size="12" class="mr-1" />{{ score }}
        </span>
      </div>
      <!-- Optional extra line from the page, e.g. "Because you watched Dune" -->
      <slot />
    </div>

    <!-- Reviews are only for watched movies; clicking opens the review dialog -->
    <div v-if="watched && !readonly" class="px-3 pt-1 review-trigger" role="button" tabindex="0" @click="$emit('review')" @keyup.enter="$emit('review')">
      <v-rating v-if="review?.rating" :model-value="review.rating" color="primary" density="compact" size="small" readonly />
      <span v-else class="text-caption text-primary">
        <v-icon icon="mdi-star-outline" size="small" /> Rate this movie
      </span>
      <p v-if="review?.review" class="text-caption font-italic text-medium-emphasis review-snippet">
        “{{ review.review }}”
      </p>
    </div>

    <v-spacer />

    <v-card-actions v-if="!readonly" class="px-3 pb-3">
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
