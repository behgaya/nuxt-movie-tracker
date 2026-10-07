/** Full URL of a TMDB image (poster, backdrop, profile) at the given width, e.g. tmdbImage(path, 'w500') */
export function tmdbImage(path: string, size: 'w154' | 'w185' | 'w500' | 'w1280') {
  return `https://image.tmdb.org/t/p/${size}${path}`
}
