type MovieInput = Pick<WatchedMovie, 'id' | 'title' | 'poster_path' | 'release_date'>

export function useWatched() {
  const { data, status } = useFetch<WatchedMovie[]>('/api/watched', {
    key: 'watched',
    default: () => [],
  })

  const watchedById = computed(() => new Map(data.value.map(m => [m.id, m])))

  async function markWatched(movie: MovieInput) {
    const { id, title, poster_path, release_date } = movie
    data.value = await $fetch<WatchedMovie[]>('/api/watched', {
      method: 'POST',
      body: { id, title, poster_path, release_date },
    })
  }

  async function unmarkWatched(id: number) {
    data.value = await $fetch<WatchedMovie[]>(`/api/watched/${id}`, { method: 'DELETE' })
  }

  function toggle(movie: MovieInput) {
    return watchedById.value.has(movie.id) ? unmarkWatched(movie.id) : markWatched(movie)
  }

  // Many changes in one request, so the server reads and writes the list only once
  async function bulkUpdate({ add = [], remove = [] }: { add?: MovieInput[], remove?: number[] }) {
    data.value = await $fetch<WatchedMovie[]>('/api/watched/bulk', {
      method: 'POST',
      body: {
        add: add.map(({ id, title, poster_path, release_date }) => ({ id, title, poster_path, release_date })),
        remove,
      },
    })
  }

  async function saveReview(id: number, input: ReviewInput) {
    data.value = await $fetch<WatchedMovie[]>(`/api/watched/${id}`, { method: 'PATCH', body: input })
  }

  return { watched: data, status, watchedById, markWatched, unmarkWatched, toggle, bulkUpdate, saveReview }
}
