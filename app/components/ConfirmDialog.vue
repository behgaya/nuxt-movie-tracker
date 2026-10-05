<script setup lang="ts">
const open = defineModel<boolean>({ required: true })

defineProps<{
  title: string
  text: string
  confirmText?: string
  color?: string
}>()

const emit = defineEmits<{ confirm: [] }>()

function confirm() {
  emit('confirm')
  open.value = false
}
</script>

<template>
  <v-dialog v-model="open" max-width="420">
    <v-card rounded="xl" :title="title" :text="text">
      <v-card-actions>
        <v-spacer />
        <v-btn @click="open = false">Cancel</v-btn>
        <v-btn :color="color ?? 'primary'" variant="flat" @click="confirm">{{ confirmText ?? 'Confirm' }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
