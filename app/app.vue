<script setup lang="ts">
// Pages set their own title with useSeoMeta(); this wraps it, e.g. "Dune · My Movies"
useHead({
  titleTemplate: title => title ? `${title} · My Movies` : 'My Movies',
})
</script>

<template>
  <v-app>
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </v-app>
</template>
