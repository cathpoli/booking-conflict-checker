<template>
  <div class="booking-form-container">
    <nav class="navbar">
      <div class="navbar-content">
        <h2>Booking Conflict Checker</h2>
        <div class="user-section">
          <span>Welcome, {{ authStore.user && authStore.user.name }}</span>
          <button @click="handleLogout" class="btn-logout">Logout</button>
        </div>
      </div>
    </nav>

    <main class="main-content">
      <div class="form-header">
        <button @click="goBack" class="btn-back">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
          </svg>
          Back
        </button>
        <h1 v-if="isEdit">Edit Booking</h1>
        <h1 v-else>Add New Booking</h1>
      </div>

      <div class="form-card">
        <form @submit.prevent="handleSubmit">
          <div class="form-group">
            <label for="date">Date</label>
            <input
              type="date"
              id="date"
              v-model="form.date"
              required
              class="form-input date-input"
            />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="start_time">Start Time</label>
              <input
                type="time"
                id="start_time"
                v-model="form.start_time"
                required
                class="form-input time-input"
              />
            </div>

            <div class="form-group">
              <label for="end_time">End Time</label>
              <input
                type="time"
                id="end_time"
                v-model="form.end_time"
                required
                class="form-input time-input"
              />
            </div>
          </div>

          <div v-if="bookingStore.error" class="error-message">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            {{ bookingStore.error }}
          </div>

          <div class="form-actions">
            <button type="button" @click="goBack" class="btn-secondary">
              Cancel
            </button>
            <button type="submit" :disabled="bookingStore.loading" class="btn-primary">
              <span v-if="bookingStore.loading" class="spinner"></span>
              <span v-if="bookingStore.loading">Saving...</span>
              <span v-else-if="isEdit">Update Booking</span>
              <span v-else>Create Booking</span>
            </button>
          </div>
        </form>
      </div>
    </main>
  </div>
</template>

<script setup>
import '../assets/bookings.css'
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useBookingStore } from '../stores/booking'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const route = useRoute()
const bookingStore = useBookingStore()
const authStore = useAuthStore()

const isEdit = computed(() => route.name === 'EditBooking')

const form = ref({
  date: '',
  start_time: '',
  end_time: ''
})

onMounted(async () => {
  bookingStore.clearError()
  
  if (isEdit.value) {
    const bookingId = route.params.id
    // Fetch bookings if not already loaded
    if (!bookingStore.bookings.length) {
      await bookingStore.fetchBookings()
    }
    
    const booking = bookingStore.bookings.find(b => b.id == bookingId)
    if (booking) {
      // Strip seconds from time values (HH:MM:SS -> HH:MM)
      const startTime = booking.start_time.substring(0, 5)
      const endTime = booking.end_time.substring(0, 5)
      
      form.value = {
        date: booking.date,
        start_time: startTime,
        end_time: endTime
      }
    } else {
      router.push('/')
    }
  }
})

async function handleSubmit() {
  try {
    if (isEdit.value) {
      await bookingStore.updateBooking(route.params.id, form.value)
    } else {
      await bookingStore.createBooking(form.value)
    }
    router.push('/')
  } catch (error) {
    console.error('Save error:', error)
  }
}

function goBack() {
  bookingStore.clearError()
  router.push('/')
}

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>