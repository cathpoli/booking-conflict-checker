import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../composables/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token') || null)
  const loading = ref(false)
  const error = ref(null)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.is_admin === true)

  // Initialize user from localStorage
  const storedUser = localStorage.getItem('user')
  if (storedUser) {
    try {
      user.value = JSON.parse(storedUser)
    } catch (e) {
      localStorage.removeItem('user')
    }
  }

  async function register(userData) {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/register', userData)
      
      user.value = response.data.user
      token.value = response.data.token
      
      localStorage.setItem('auth_token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.user))
      
      return response.data
    } catch (err) {
      // Handle Laravel validation errors
      if (err.response?.data?.errors) {
        const errors = err.response.data.errors
        const errorMessages = Object.values(errors).flat()
        error.value = errorMessages.join('\n')
      } else {
        error.value = err.response?.data?.message || 'Registration failed'
      }
      throw err
    } finally {
      loading.value = false
    }
  }

  async function login(credentials) {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/login', credentials)
      
      user.value = response.data.user
      token.value = response.data.token
      
      localStorage.setItem('auth_token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.user))
      
      return response.data
    } catch (err) {
      // Handle Laravel validation errors
      if (err.response?.data?.errors) {
        const errors = err.response.data.errors
        const errorMessages = Object.values(errors).flat()
        error.value = errorMessages.join('\n')
      } else {
        error.value = err.response?.data?.message || 'Login failed'
      }
      throw err
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    loading.value = true
    error.value = null

    try {
      await api.post('/logout')
    } catch (err) {
      console.error('Logout error:', err)
    } finally {
      user.value = null
      token.value = null
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
      loading.value = false
    }
  }

  async function fetchUser() {
    if (!token.value) return

    loading.value = true
    error.value = null

    try {
      const response = await api.get('/user')
      user.value = response.data.user
      localStorage.setItem('user', JSON.stringify(response.data.user))
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch user'
      // If token is invalid, clear auth state
      if (err.response?.status === 401) {
        user.value = null
        token.value = null
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user')
      }
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    isAdmin,
    register,
    login,
    logout,
    fetchUser
  }
})
