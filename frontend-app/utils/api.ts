import { useCookie, useRuntimeConfig } from "nuxt/app"
import { mockRequest } from "./mock"

// Dedicated API module for making HTTP requests
export const api = {
    async request(url: string, options: any = {}) {
        const config = useRuntimeConfig()
        if (config.public.mock) return mockRequest(url, options)

        const token = useCookie('auth_token').value
        
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            ...options.headers,
            ...(token ? { 'Authorization': `Bearer ${token}` } : {})
        }
        
        return $fetch(`${config.public.apiBase}${url}`, {
            ...options,
            headers
        })
    },
    
    get(url: string, query = {}) {
        return this.request(url, { method: 'GET', query })
    },
    
    post(url: string, body = {}) {
        return this.request(url, { method: 'POST', body })
    }
}
