/** A short message at the bottom of the screen, shown by the default layout. It outlives the component that sent it. */
export function useToast() {
  const message = useState('toast', () => '')

  function show(text: string) {
    message.value = text
  }

  return { message, show }
}
