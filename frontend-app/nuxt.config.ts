// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  ssr: false, // SPA mode for dashboard
  compatibilityDate: '2024-04-10',
  devtools: { enabled: true },
  modules: [
    '@pinia/nuxt'
  ],
  runtimeConfig: {
    public: {
      apiBase: 'http://localhost:8000/api' // Assuming Laravel is on 8000
    }
  }
})
