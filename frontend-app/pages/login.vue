<template>
  <div class="login-container">
    <div class="login-card">
      <h1>Admin Login</h1>
      <p>Log in to view webhook payments</p>
      
      <form @submit.prevent="handleLogin">
        <div class="input-group">
          <label>Email</label>
          <input v-model="email" type="email" placeholder="admin@admin.com" required />
        </div>
        
        <div class="input-group">
          <label>Password</label>
          <input v-model="password" type="password" placeholder="secret" required />
        </div>
        
        <p v-if="error" class="error">{{ error }}</p>
        
        <button type="submit" :disabled="loading">
          {{ loading ? 'Logging in...' : 'Sign In' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const email = ref('admin@admin.com')
const password = ref('secret')
const loading = ref(false)
const error = ref('')
const config = useRuntimeConfig()

const handleLogin = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const res = await $fetch(`${config.public.apiBase}/login`, {
      method: 'POST',
      body: { email: email.value, password: password.value },
      headers: { 'Accept': 'application/json' }
    })
    
    // Store token in Nuxt cookie
    const tokenCookie = useCookie('auth_token', { maxAge: 60 * 60 * 24 * 7 })
    tokenCookie.value = res.token
    
    // Navigate to dashboard
    navigateTo('/')
  } catch (err) {
    error.value = 'Invalid login credentials. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
}
.login-card {
  background: white;
  padding: 2.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  width: 100%;
  max-width: 400px;
}
h1 {
  margin: 0;
  font-size: 1.5rem;
  color: #1e293b;
}
p {
  color: #64748b;
  font-size: 0.875rem;
  margin-bottom: 2rem;
}
.input-group {
  margin-bottom: 1.5rem;
  text-align: left;
}
label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #334155;
  margin-bottom: 0.5rem;
}
input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 1rem;
  box-sizing: border-box;
}
input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
button {
  width: 100%;
  padding: 0.75rem;
  background-color: var(--primary);
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}
button:hover {
  background-color: #2563eb;
}
button:disabled {
  background-color: #93c5fd;
  cursor: not-allowed;
}
.error {
  color: #ef4444;
  margin-bottom: 1rem;
  font-size: 0.875rem;
}
</style>
