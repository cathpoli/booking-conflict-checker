<template>
  <div class="bookings-container">
    <div class="bookings-header">
      <h1>My Bookings</h1>
      <button @click="router.push('/bookings/add')" class="btn-add">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Add Booking
      </button>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section">
      <div class="search-box">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"></circle>
          <path d="m21 21-4.35-4.35"></path>
        </svg>
        <input 
          type="text" 
          v-model="searchQuery" 
          placeholder="Search by date..."
          class="search-input"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="bookingStore.loading && !bookings.length" class="loading-state">
      <div class="spinner-large"></div>
      <p>Loading bookings...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="!bookings.length && !bookingStore.loading" class="empty-state">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
        <line x1="16" y1="2" x2="16" y2="6"></line>
        <line x1="8" y1="2" x2="8" y2="6"></line>
        <line x1="3" y1="10" x2="21" y2="10"></line>
      </svg>
      <h3>No bookings yet</h3>
      <p>Create your first booking to get started</p>
      <button @click="router.push('/bookings/add')" class="btn-primary">Add Your First Booking</button>
    </div>

    <!-- Bookings List -->
    <div v-else class="bookings-grid">
      <div 
        v-for="booking in filteredBookings" 
        :key="booking.id"
        class="booking-card"
        :class="getBookingClass(booking)"
      >
        <div class="booking-header">
          <div class="booking-date">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            {{ formatDate(booking.date) }}
          </div>
          <div class="booking-actions">
            <button @click="router.push(`/bookings/edit/${booking.id}`)" class="btn-icon" title="Edit">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
            <button @click="confirmDelete(booking)" class="btn-icon btn-delete" title="Delete">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
              </svg>
            </button>
          </div>
        </div>
        
        <div class="booking-time">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
          <span>{{ booking.start_time }} - {{ booking.end_time }}</span>
        </div>

        <div v-if="hasConflict(booking)" class="conflict-badge">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
          Conflict Detected
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <ConfirmDialog
      v-if="showDeleteConfirm"
      title="Delete Booking"
      message="Are you sure you want to delete this booking? This action cannot be undone."
      @confirm="handleDelete"
      @cancel="showDeleteConfirm = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useBookingStore } from '../stores/booking'
import ConfirmDialog from '../components/ConfirmDialog.vue'

const router = useRouter()
const bookingStore = useBookingStore()
const showDeleteConfirm = ref(false)
const bookingToDelete = ref(null)
const searchQuery = ref('')

onMounted(async () => {
  await bookingStore.fetchBookings()
})

const filteredBookings = computed(() => {
  if (!searchQuery.value) return bookingStore.bookings
  
  return bookingStore.bookings.filter(booking => 
    booking.date.includes(searchQuery.value)
  )
})

const bookings = computed(() => bookingStore.bookings)

function formatDate(date) {
  return new Date(date).toLocaleDateString('en-US', { 
    weekday: 'short', 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}

function getBookingClass(booking) {
  if (hasConflict(booking)) {
    return 'has-conflict'
  }
  return ''
}

function hasConflict(booking) {
  // Check if this booking has conflicts
  // This will be implemented based on the conflicts data from the store
  return false
}

function confirmDelete(booking) {
  bookingToDelete.value = booking
  showDeleteConfirm.value = true
}

async function handleDelete() {
  try {
    await bookingStore.deleteBooking(bookingToDelete.value.id)
    showDeleteConfirm.value = false
    bookingToDelete.value = null
  } catch (error) {
    console.error('Delete error:', error)
  }
}
</script>

<style scoped>
.bookings-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
  min-height: calc(100vh - 80px);
  background: #f8f9fa;
}

.bookings-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.bookings-header h1 {
  font-size: 2rem;
  color: #1a202c;
  font-weight: 700;
}

.btn-add {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-add svg {
  width: 20px;
  height: 20px;
}

.btn-add:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.filters-section {
  margin-bottom: 2rem;
}

.search-box {
  position: relative;
  max-width: 400px;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  color: #a0aec0;
}

.search-input {
  width: 100%;
  padding: 0.875rem 1rem 0.875rem 3rem;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.loading-state,
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}

.spinner-large {
  width: 48px;
  height: 48px;
  margin: 0 auto 1rem;
  border: 4px solid #e2e8f0;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.empty-state svg {
  width: 80px;
  height: 80px;
  color: #cbd5e0;
  margin: 0 auto 1rem;
}

.empty-state h3 {
  font-size: 1.5rem;
  color: #2d3748;
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: #718096;
  margin-bottom: 1.5rem;
}

.bookings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
}

.booking-card {
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.3s ease;
}

.booking-card:hover {
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.booking-card.has-conflict {
  border-color: #f56565;
  background: #fff5f5;
}

.booking-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.booking-date {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #2d3748;
  font-size: 1.1rem;
}

.booking-date svg {
  width: 20px;
  height: 20px;
  color: #667eea;
}

.booking-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  padding: 0.5rem;
  background: #f7fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-icon svg {
  width: 18px;
  height: 18px;
  color: #4a5568;
}

.btn-icon:hover {
  background: #edf2f7;
  border-color: #cbd5e0;
}

.btn-delete:hover {
  background: #fff5f5;
  border-color: #feb2b2;
}

.btn-delete:hover svg {
  color: #f56565;
}

.booking-time {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #4a5568;
  font-size: 1rem;
  margin-bottom: 0.5rem;
}

.booking-time svg {
  width: 18px;
  height: 18px;
  color: #a0aec0;
}

.conflict-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: #fed7d7;
  color: #c53030;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 600;
  margin-top: 1rem;
}

.conflict-badge svg {
  width: 16px;
  height: 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
