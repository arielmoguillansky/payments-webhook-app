import { navigateTo, useCookie } from 'nuxt/app'
import { defineStore } from 'pinia'
import { api } from '../utils/api'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: useCookie('auth_token').value || null,
        user: null,
        error: null,
        loading: false
    }),
    actions: {
        async login(email:string, password:string) {
            this.loading = true
            this.error = null
            try {
                const res = await api.post('/login', { email, password }) as { token: string; user: any }
                const cookie = useCookie('auth_token', { maxAge: 60 * 60 * 24 * 7 })
                cookie.value = res.token
                this.token = res.token
                this.user = res.user
                return true
            } catch (e: any) {
                this.error = e.response?._data?.message || 'Login failed'
                return false
            } finally {
                this.loading = false
            }
        },
        logout() {
            this.token = null
            this.user = null
            const cookie = useCookie('auth_token')
            cookie.value = null
            navigateTo('/login')
        }
    }
})
