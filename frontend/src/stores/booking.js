import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../composables/api'
import echo from '../composables/echo'
import { useAuthStore } from './auth'

export const useBookingStore = defineStore('booking', () => {
  const bookings = ref([])
  const conflicts = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const selectedBooking = ref(null)
  let isListening = false

  // Setup real-time listeners
  function setupRealtimeListeners() {
    if (isListening) return
    
    console.log('Setting up real-time listeners...')
    
    echo.channel('bookings')
      .subscribed(() => {
        console.log('Successfully subscribed to bookings channel')
      })
      .listen('.booking.created', (event) => {
        console.log('Booking created event received:', event)
        bookings.value.push(event.booking)
        // Refresh to update conflicts
        fetchBookings()
      })
      .listen('.booking.updated', (event) => {
        console.log('Booking updated event received:', event)
        const index = bookings.value.findIndex(b => b.id === event.booking.id)
        if (index !== -1) {
          bookings.value[index] = event.booking
        }
        // Refresh to update conflicts
        fetchBookings()
      })
      .listen('.booking.deleted', (event) => {
        console.log('Booking deleted event received:', event)
        bookings.value = bookings.value.filter(b => b.id !== event.id)
        // Refresh to update conflicts
        fetchBookings()
      })
      .error((error) => {
        console.error('Channel error:', error)
      })
    
    isListening = true
  }

  // Cleanup listeners
  function cleanupRealtimeListeners() {
    if (isListening) {
      echo.leave('bookings')
      isListening = false
    }
  }

  // Fetch all bookings
  async function fetchBookings() {
    loading.value = true
    error.value = null

    try {
      const authStore = useAuthStore()
      const endpoint = authStore.isAdmin ? '/admin/bookings' : '/bookings'
      const response = await api.get(endpoint)
      bookings.value = response.data.bookings
      conflicts.value = response.data.conflicts
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch bookings'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Create a new booking
  async function createBooking(bookingData) {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/bookings', bookingData)
      bookings.value.push(response.data.booking)
      await fetchBookings() // Refresh to get updated conflicts
      return response.data
    } catch (err) {
      if (err.response?.data?.errors) {
        const errors = err.response.data.errors
        const errorMessages = Object.values(errors).flat()
        error.value = errorMessages.join('\n')
      } else {
        error.value = err.response?.data?.message || 'Failed to create booking'
      }
      throw err
    } finally {
      loading.value = false
    }
  }

  // Update an existing booking
  async function updateBooking(id, bookingData) {
    loading.value = true
    error.value = null

    try {
      const response = await api.put(`/bookings/${id}`, bookingData)
      const index = bookings.value.findIndex(b => b.id === id)
      if (index !== -1) {
        bookings.value[index] = response.data.booking
      }
      await fetchBookings() // Refresh to get updated conflicts
      return response.data
    } catch (err) {
      if (err.response?.data?.errors) {
        const errors = err.response.data.errors
        const errorMessages = Object.values(errors).flat()
        error.value = errorMessages.join('\n')
      } else {
        error.value = err.response?.data?.message || 'Failed to update booking'
      }
      throw err
    } finally {
      loading.value = false
    }
  }

  // Delete a booking
  async function deleteBooking(id) {
    loading.value = true
    error.value = null

    try {
      await api.delete(`/bookings/${id}`)
      bookings.value = bookings.value.filter(b => b.id !== id)
      await fetchBookings() // Refresh to get updated conflicts
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to delete booking'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Set selected booking for editing
  function selectBooking(booking) {
    selectedBooking.value = booking
  }

  function clearSelection() {
    selectedBooking.value = null
  }

  function clearError() {
    error.value = null
  }

  return {
    bookings,
    conflicts,
    loading,
    error,
    selectedBooking,
    fetchBookings,
    createBooking,
    updateBooking,
    deleteBooking,
    selectBooking,
    clearSelection,
    clearError,
    setupRealtimeListeners,
    cleanupRealtimeListeners
  }
})
