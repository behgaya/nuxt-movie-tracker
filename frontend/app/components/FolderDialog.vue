<script setup lang="ts">
const open = defineModel<boolean>({ required: true })

const props = defineProps<{
  title: string
  folder?: FolderInput // the folder being edited; leave out to create a new one
  // Called with the form; on an error (e.g. a name already in use) the dialog stays open and shows it
  save: (input: FolderInput) => Promise<void>
}>()

// Edit a local copy so Cancel discards changes; reset it every time the dialog opens
const name = ref('')
const isPublic = ref(false)
const error = ref('')
const saving = ref(false)

watch(open, (isOpen) => {
  if (isOpen) {
    name.value = props.folder?.name ?? ''
    isPublic.value = props.folder?.isPublic ?? false
    error.value = ''
  }
})

async function submit() {
  saving.value = true
  error.value = ''
  try {
    await props.save({ name: name.value.trim(), isPublic: isPublic.value })
    open.value = false
  }
  catch (e: any) {
    error.value = e?.data?.message ?? 'Could not save the folder, try again'
  }
  finally {
    saving.value = false
  }
}
</script>

<template>
  <v-dialog v-model="open" max-width="440">
    <v-card rounded="xl" :title="title">
      <v-form @submit.prevent="submit">
        <v-card-text>
          <v-text-field
            v-model="name"
            label="Name"
            placeholder="e.g. Horror night"
            counter="50"
            maxlength="50"
            autofocus
            :error-messages="error"
          />
          <v-switch
            v-model="isPublic"
            color="primary"
            label="Public: anyone with the link can view it"
            hide-details
            inset
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="open = false">Cancel</v-btn>
          <v-btn type="submit" color="primary" variant="flat" :loading="saving" :disabled="!name.trim()">Save</v-btn>
        </v-card-actions>
      </v-form>
    </v-card>
  </v-dialog>
</template>
