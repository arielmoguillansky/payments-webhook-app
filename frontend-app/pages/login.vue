<template>
  <div class="login">
    <Backdrop :star-count="60" />

    <header class="topbar">
      <div class="brand">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M4 12a8 8 0 0 1 14.9-4M20 12a8 8 0 0 1-14.9 4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" />
          <circle cx="12" cy="12" r="2.6" fill="currentColor" />
        </svg>
        Relay
      </div>
      <span class="tag"><span class="tag-dot" /> Webhook console</span>
    </header>

    <main class="hero">
      <BlurText tag="h1" text="Every payment event, in one place" class="title" />
      <p class="subtitle reveal" style="--i: 6">
        Monitor webhook deliveries, audit each payment's history and trigger refunds — in real time.
      </p>

      <form class="card reveal" style="--i: 9" @submit.prevent="handleLogin">
        <label class="input-group">
          <span>Email</span>
          <input v-model="email" class="field" type="email" placeholder="admin@admin.com" autocomplete="email" required />
        </label>

        <label class="input-group">
          <span>Password</span>
          <input v-model="password" class="field" type="password" placeholder="••••••••" autocomplete="current-password" required />
        </label>

        <Transition name="error">
          <p v-if="authStore.error" class="error">{{ authStore.error }}</p>
        </Transition>

        <button type="submit" class="btn btn-primary submit" :disabled="authStore.loading">
          <span v-if="authStore.loading" class="spinner" />
          <template v-else>
            Sign in
            <svg class="arrow" width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true">
              <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </template>
        </button>
      </form>
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const authStore = useAuthStore()
const email = ref('admin@admin.com')
const password = ref('secret')

const handleLogin = async () => {
  const success = await authStore.login(email.value, password.value)
  if (success) {
    navigateTo('/')
  }
}
</script>

<style scoped>
.login {
  position: relative;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
.topbar {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 56px;
  padding: 0 28px;
  border-bottom: 1px solid var(--border);
}
.brand {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 600;
  letter-spacing: -0.02em;
}
.tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 8px;
  border-radius: 6px;
  background: var(--accent-soft);
  color: var(--accent);
  font-size: 12.5px;
  font-weight: 500;
}
.tag-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: currentColor;
}

.hero {
  position: relative;
  z-index: 1;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 16px 96px;
  text-align: center;
}
.title {
  margin: 0;
  max-width: 640px;
  font-size: clamp(34px, 5.4vw, 52px);
  font-weight: 500;
  line-height: 1.08;
  letter-spacing: -0.035em;
}
.subtitle {
  margin: 18px 0 40px;
  max-width: 460px;
  color: var(--text-muted);
  font-size: 16px;
  letter-spacing: -0.005em;
}

.card {
  width: 100%;
  max-width: 380px;
  padding: 24px;
  text-align: left;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.01));
  border: 1px solid var(--border);
  border-radius: var(--radius-xl);
  box-shadow: 0 30px 80px -20px rgba(0, 0, 0, 0.8), inset 0 1px 0 rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(12px);
}
.input-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 16px;
}
.input-group > span {
  font-size: 12.5px;
  font-weight: 500;
  color: var(--text-muted);
}
.input-group .field {
  height: 38px;
  font-size: 14px;
}
.submit {
  width: 100%;
  height: 38px;
  margin-top: 8px;
  font-size: 14px;
}
.arrow {
  transition: transform var(--dur) var(--ease-out);
}
.submit:hover .arrow {
  transform: translateX(3px);
}
.error {
  margin: 0 0 12px;
  padding: 8px 10px;
  border-radius: var(--radius);
  background: var(--danger-soft);
  color: var(--danger);
  font-size: 13px;
}
.error-enter-active,
.error-leave-active {
  transition: all var(--dur) var(--ease-out);
}
.error-enter-from,
.error-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
