/** The logged-in user's folders. Every call answers with the whole updated list, by name. */
export function useFolders() {
  const { data, status } = useFetch<FolderSummary[]>('/api/folders', {
    key: 'folders',
    default: () => [],
  })

  async function createFolder(input: FolderInput) {
    data.value = await $fetch<FolderSummary[]>('/api/folders', { method: 'POST', body: input })
  }

  async function updateFolder(id: number, input: FolderInput) {
    data.value = await $fetch<FolderSummary[]>(`/api/folders/${id}`, { method: 'PUT', body: input })
  }

  async function deleteFolder(id: number) {
    data.value = await $fetch<FolderSummary[]>(`/api/folders/${id}`, { method: 'DELETE' })
  }

  async function addToFolder(id: number, movie: MovieInput) {
    const { id: movieId, title, poster_path, release_date } = movie
    data.value = await $fetch<FolderSummary[]>(`/api/folders/${id}/movies`, {
      method: 'POST',
      body: { id: movieId, title, poster_path, release_date },
    })
  }

  async function removeFromFolder(id: number, movieId: number) {
    data.value = await $fetch<FolderSummary[]>(`/api/folders/${id}/movies/${movieId}`, { method: 'DELETE' })
  }

  // Many changes in one request, e.g. from selection mode
  async function bulkUpdateFolder(id: number, { add = [], remove = [] }: { add?: MovieInput[], remove?: number[] }) {
    data.value = await $fetch<FolderSummary[]>(`/api/folders/${id}/movies/bulk`, {
      method: 'POST',
      body: {
        add: add.map(({ id, title, poster_path, release_date }) => ({ id, title, poster_path, release_date })),
        remove,
      },
    })
  }

  return { folders: data, status, createFolder, updateFolder, deleteFolder, addToFolder, removeFromFolder, bulkUpdateFolder }
}
