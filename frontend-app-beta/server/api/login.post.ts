import { z } from 'zod'

const bodySchema = z.object({
  email: z.email(),
  password: z.string().min(8),
})

export default defineEventHandler(async (event) => {
  const { email, password } = await readValidatedBody(event, bodySchema.parse)
  const config = useRuntimeConfig(event)

  try {
    const response = await fetch(`${config.baseBackendUrl}/api/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ email, password }),
    })

    if (!response.ok) {
      throw new Error('Network response was not ok')
    }

    const data = await response.json()
    console.log('LOGIIINN', data)

    // const token = data.token
    // useCookie('auth-token').value = token
    await setUserSession(event, {
      user: {
        name: 'John Doe',
      },
    })

    return { message: 'Login successful' }

  } catch (error) {
    console.error('There was a problem with the fetch operation:', error)
    throw createError({
      status: 401,
      message: 'Bad credentials',
    })
  }
})
