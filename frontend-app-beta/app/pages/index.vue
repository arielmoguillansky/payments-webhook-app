<script setup lang="ts">
definePageMeta({
  middleware: ['auth']
})
const { user, clear: clearSession } = useUserSession()

async function logout () {
  await clearSession()
  await navigateTo('/login')
}
</script>
<template>
<div>
    <h1>Welcome {{ user.name || 'Guest' }}</h1>
    <button @click="logout">
      Logout
    </button>
  </div>
</template>
