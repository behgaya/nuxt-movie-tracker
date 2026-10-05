/** Each user's watched list lives under its own key: watchlist:<userId> */
export async function getWatched(userId: string) {
  return (await useStorage('data').getItem<WatchedMovie[]>(`watchlist:${userId}`)) ?? []
}

export async function setWatched(userId: string, list: WatchedMovie[]) {
  await useStorage('data').setItem(`watchlist:${userId}`, list)
}
