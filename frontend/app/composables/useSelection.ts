/** Selection-mode state for a movie grid: which ids are picked, plus helpers */
export function useSelection() {
  const selecting = ref(false)
  const selected = ref<number[]>([])

  function toggle(id: number) {
    selected.value = selected.value.includes(id)
      ? selected.value.filter(x => x !== id)
      : [...selected.value, id]
  }

  function selectAll(ids: number[]) {
    selected.value = [...ids]
  }

  function clear() {
    selected.value = []
  }

  function stop() {
    selecting.value = false
    selected.value = []
  }

  return { selecting, selected, toggle, selectAll, clear, stop }
}
