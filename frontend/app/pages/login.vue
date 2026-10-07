<script setup lang="ts">
definePageMeta({ layout: 'auth' })
useSeoMeta({ title: 'Log in' })

const { fetch: fetchSession } = useAuth()

const mode = ref<'login' | 'register'>('login')
const username = ref('')
const password = ref('')
const confirm = ref('')
const showPassword = ref(false)
const loading = ref(false)
const error = ref('')
const form = ref()

// Browser-side checks for quick feedback; the server validates again
const rules = {
  username: [(v: string) => /^[a-zA-Z0-9_]{3,30}$/.test(v ?? '') || '3–30 characters: letters, numbers or _'],
  password: [(v: string) => (v?.length ?? 0) >= 8 || 'At least 8 characters'],
  confirm: [(v: string) => v === password.value || 'Passwords do not match'],
}

// Clear the server error when switching tabs
watch(mode, () => { error.value = '' })

async function submit() {
  const { valid } = await form.value.validate()
  if (!valid) return

  loading.value = true
  error.value = ''
  try {
    await $fetch(`/api/auth/${mode.value}`, {
      method: 'POST',
      body: { username: username.value, password: password.value },
    })
    await fetchSession() // pull the new session into useAuth()
    await refreshNuxtData('watched') // load this user's list, not a previous one
    await navigateTo('/')
  }
  catch (e: any) {
    error.value = e?.data?.message ?? 'Something went wrong, try again'
  }
  finally {
    loading.value = false
  }
}
</script>

<template>
  <v-card rounded="xl" class="pa-2" color="surface">
    <v-tabs v-model="mode" grow color="primary" class="mb-2">
      <v-tab value="login">Log in</v-tab>
      <v-tab value="register">Create account</v-tab>
    </v-tabs>

    <v-card-text>
      <v-form ref="form" @submit.prevent="submit">
        <v-text-field
          v-model="username"
          label="Username"
          prepend-inner-icon="mdi-account-outline"
          autocomplete="username"
          :rules="rules.username"
          class="mb-2"
        />
        <v-text-field
          v-model="password"
          label="Password"
          prepend-inner-icon="mdi-lock-outline"
          :type="showPassword ? 'text' : 'password'"
          :append-inner-icon="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
          :autocomplete="mode === 'login' ? 'current-password' : 'new-password'"
          :rules="rules.password"
          class="mb-2"
          @click:append-inner="showPassword = !showPassword"
        />
        <v-text-field
          v-if="mode === 'register'"
          v-model="confirm"
          label="Confirm password"
          prepend-inner-icon="mdi-lock-check-outline"
          :type="showPassword ? 'text' : 'password'"
          autocomplete="new-password"
          :rules="rules.confirm"
          class="mb-2"
        />

        <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mb-4">{{ error }}</v-alert>

        <v-btn type="submit" color="primary" size="large" block :loading="loading">
          {{ mode === 'login' ? 'Log in' : 'Create account' }}
        </v-btn>
      </v-form>
    </v-card-text>
  </v-card>
</template>
