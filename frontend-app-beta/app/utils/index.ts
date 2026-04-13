export const isAuthenticated = () => {
  const token = useCookie('auth-token')

  if (!token.value) {
    return navigateTo('/login')
  }
}