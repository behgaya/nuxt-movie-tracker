<script setup lang="ts">
const open = defineModel<boolean>({ required: true })

const props = defineProps<{
  movie: WatchedMovie | null
}>()

const emit = defineEmits<{ save: [input: ReviewInput] }>()

// Edit a local copy so Cancel discards changes; reset it every time the dialog opens
const rating = ref(0)
const review = ref('')

watch(open, (isOpen) => {
  if (isOpen) {
    rating.value = props.movie?.rating ?? 0
    review.value = props.movie?.review ?? ''
  }
})

function save() {
  emit('save', { rating: rating.value || null, review: review.value ?? '' })
  open.value = false
}
</script>

<template>
  <v-dialog v-model="open" max-width="520">
    <v-card v-if="movie" rounded="xl">
      <div class="d-flex align-center ga-4 pa-5 pb-2">
        <v-img
          v-if="movie.poster_path"
          :src="`https://image.tmdb.org/t/p/w154${movie.poster_path}`"
          width="56"
          :aspect-ratio="2 / 3"
          class="rounded flex-grow-0"
          cover
        />
        <div>
          <div class="text-overline text-medium-emphasis">Your review</div>
          <div class="text-h6 font-weight-bold">{{ movie.title }}</div>
        </div>
      </div>
      <v-card-text>
        <div class="text-center mb-4">
          <v-rating v-model="rating" color="primary" hover clearable size="x-large" />
        </div>
        <v-textarea
          v-model="review"
          label="Write a note (optional)"
          counter="1000"
          maxlength="1000"
          auto-grow
          rows="3"
        />
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn @click="open = false">Cancel</v-btn>
        <v-btn color="primary" variant="flat" @click="save">Save</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
