<script setup lang="ts">
const props = defineProps<{
  count: number // how many are selected
  total: number // how many could be selected
}>()

defineEmits<{ selectAll: [], clear: [], close: [] }>()

const allSelected = computed(() => props.total > 0 && props.count === props.total)
</script>

<template>
  <v-card class="selection-bar d-flex flex-wrap align-center ga-2 pa-2 mb-4" color="surface-variant">
    <v-btn icon="mdi-close" variant="text" size="small" title="Exit selection" @click="$emit('close')" />
    <span class="font-weight-medium mr-2">{{ count }} selected</span>

    <v-btn
      variant="text"
      size="small"
      :prepend-icon="allSelected ? 'mdi-checkbox-blank-outline' : 'mdi-checkbox-multiple-marked-outline'"
      @click="allSelected ? $emit('clear') : $emit('selectAll')"
    >
      {{ allSelected ? 'Deselect all' : `Select all (${total})` }}
    </v-btn>

    <v-spacer />

    <!-- The page decides which mass actions make sense -->
    <slot />
  </v-card>
</template>
