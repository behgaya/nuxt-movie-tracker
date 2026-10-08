export function useWantToWatch() {
  const { data, status } = useFetch<WantedMovie[]>('/api/want', {
    key: 'want',
    default: () => [],
  })

  const wantedIds = computed(() => new Set(data.value.map(m => m.id)))

  async function addWanted(movie: MovieInput) {
    const { id, title, poster_path, release_date } = movie
    data.value = await $fetch<WantedMovie[]>('/api/want', {
      method: 'POST',
      body: { id, title, poster_path, release_date },
    })
  }

  async function removeWanted(id: number) {
    data.value = await $fetch<WantedMovie[]>(`/api/want/${id}`, { method: 'DELETE' })
  }

  function toggleWanted(movie: MovieInput) {
    return wantedIds.value.has(movie.id) ? removeWanted(movie.id) : addWanted(movie)
  }

  return { wanted: data, status, wantedIds, addWanted, removeWanted, toggleWanted }
}
