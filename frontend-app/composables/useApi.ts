export const useApi = () => {
  const config = useRuntimeConfig()
  const token = useCookie('auth_token')

  const fetchWithAuth = $fetch.create({
    baseURL: config.public.apiBase as string,
    onRequest({ request, options }) {
      if (token.value) {
        options.headers = {
          ...options.headers,
          Authorization: `Bearer ${token.value}`,
          Accept: 'application/json'
        }
      }
    }
  })

  return fetchWithAuth
}
