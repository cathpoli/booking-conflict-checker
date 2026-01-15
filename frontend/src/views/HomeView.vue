<template>
  <div class="home-container">
    <nav class="navbar">
      <div class="navbar-content">
        <h2>Booking Conflict Checker</h2>
        <div class="user-section">
          <span>Welcome, {{ authStore.user?.name }}</span>
          <button @click="handleLogout" class="btn-logout">Logout</button>
        </div>
      </div>
    </nav>

    <main class="main-content">
      <div class="bookings-header">
        <h1>{{ authStore.isAdmin ? 'Booking List' : 'My Bookings' }}</h1>
        <div class="header-actions">
          <router-link v-if="authStore.isAdmin" to="/dashboard" class="btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="3" width="7" height="7"></rect>
              <rect x="14" y="3" width="7" height="7"></rect>
              <rect x="14" y="14" width="7" height="7"></rect>
              <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            Dashboard
          </router-link>
          <button v-if="!authStore.isAdmin" @click="addBooking" class="btn-add">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add Booking
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters-container">
        <button 
          :class="['filter-btn', { active: filterType === 'all' }]"
          @click="filterType = 'all'"
        >
          All Bookings
        </button>
        <button 
          :class="['filter-btn', { active: filterType === 'conflicts' }]"
          @click="filterType = 'conflicts'"
        >
          Conflicts
          <span v-if="conflictCount > 0" class="badge">{{ conflictCount }}</span>
        </button>
      </div>

      <!-- Search and Filter -->
      <div class="search-filter-container">
        <div v-if="authStore.isAdmin" class="search-box-wrapper">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input
            type="text"
            v-model="searchQuery"
            placeholder="Search by user name..."
            class="search-input"
          />
        </div>
        <div class="date-filter-wrapper">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
          <input
            type="date"
            v-model="dateFilter"
            class="date-input"
          />
          <button 
            v-if="dateFilter" 
            @click="dateFilter = ''" 
            class="clear-date-btn"
            title="Clear date filter"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
      </div>

      <div v-if="bookingStore.loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading bookings...</p>
      </div>

      <div v-else-if="!filteredBookings.length" class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
        <h3>No bookings found</h3>
        <p v-if="searchQuery || filterType !== 'all'">Try adjusting your filters or search query</p>
        <p v-else>Create your first booking to get started</p>
        <button v-if="!authStore.isAdmin" @click="addBooking" class="btn-primary">Add Booking</button>
      </div>

      <!-- Data Table -->
      <div v-else class="table-container">
        <div class="table-responsive">
          <table class="bookings-table">
            <thead>
              <tr>
                <th v-if="authStore.isAdmin">User</th>
                <th class="sortable" @click="sortBy('date')">
                  Date
                  <span class="sort-icon" v-if="sortColumn === 'date'">
                    {{ sortDirection === 'asc' ? '↑' : '↓' }}
                  </span>
                </th>
                <th class="sortable" @click="sortBy('start_time')">
                  Start Time
                  <span class="sort-icon" v-if="sortColumn === 'start_time'">
                    {{ sortDirection === 'asc' ? '↑' : '↓' }}
                  </span>
                </th>
                <th class="sortable" @click="sortBy('end_time')">
                  End Time
                  <span class="sort-icon" v-if="sortColumn === 'end_time'">
                    {{ sortDirection === 'asc' ? '↑' : '↓' }}
                  </span>
                </th>
                <th>Duration</th>
                <th>Status</th>
                <th v-if="!authStore.isAdmin">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="booking in paginatedBookings" :key="booking.id">
                <td v-if="authStore.isAdmin" data-label="User">
                  <span class="user-name">{{ booking.user_name || 'Unknown User' }}</span>
                </td>
                <td data-label="Date">{{ formatDate(booking.date) }}</td>
                <td data-label="Start Time">{{ formatTime(booking.start_time) }}</td>
                <td data-label="End Time">{{ formatTime(booking.end_time) }}</td>
                <td data-label="Duration">{{ calculateDuration(booking) }}</td>
                <td data-label="Status">
                  <span 
                    v-if="hasConflict(booking)" 
                    class="status-badge conflict"
                    :class="{ clickable: authStore.isAdmin }"
                    @click="authStore.isAdmin && showConflictInfo(booking)"
                    :title="authStore.isAdmin ? 'Click to view conflict details' : ''"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10"></circle>
                      <line x1="15" y1="9" x2="9" y2="15"></line>
                      <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    Conflict
                  </span>
                  <span v-else-if="isUpcoming(booking)" class="status-badge upcoming">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10"></circle>
                      <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Upcoming
                  </span>
                  <span v-else class="status-badge completed">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Completed
                  </span>
                </td>
                <td data-label="Actions" v-if="!authStore.isAdmin">
                  <div class="action-buttons">
                    <button @click="editBooking(booking.id)" class="btn-icon" title="Edit">
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
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="pagination" v-if="totalPages > 1">
          <button 
            class="page-btn" 
            @click="currentPage--" 
            :disabled="currentPage === 1"
          >
            Previous
          </button>
          
          <button
            v-for="page in displayedPages"
            :key="page"
            :class="['page-btn', { active: page === currentPage, ellipsis: page === '...' }]"
            @click="page !== '...' && (currentPage = page)"
            :disabled="page === '...'"
          >
            {{ page }}
          </button>

          <button 
            class="page-btn" 
            @click="currentPage++" 
            :disabled="currentPage === totalPages"
          >
            Next
          </button>
        </div>
      </div>
    </main>

    <!-- Delete Confirmation Modal -->
    <ConfirmDialog
      v-if="showDeleteConfirm"
      title="Delete Booking"
      message="Are you sure you want to delete this booking? This action cannot be undone."
      @confirm="handleDelete"
      @cancel="showDeleteConfirm = false"
    />

    <!-- Conflict Details Modal -->
    <ConflictDetailsModal
      v-if="showConflictDetails"
      :booking="selectedConflictBooking"
      :conflicts="bookingStore.conflicts"
      @close="showConflictDetails = false"
    />
  </div>
</template>

<script setup>
import '../assets/bookings.css'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useBookingStore } from '../stores/booking'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import ConflictDetailsModal from '../components/ConflictDetailsModal.vue'

const router = useRouter()
const authStore = useAuthStore()
const bookingStore = useBookingStore()
const showDeleteConfirm = ref(false)
const bookingToDelete = ref(null)
const showConflictDetails = ref(false)
const selectedConflictBooking = ref(null)
const searchQuery = ref('')
const dateFilter = ref('')
const filterType = ref('all')
const currentPage = ref(1)
const itemsPerPage = ref(10)
const sortColumn = ref('date')
const sortDirection = ref('asc')

onMounted(() => {
  bookingStore.fetchBookings()
  // Setup real-time listeners for WebSocket updates
  bookingStore.setupRealtimeListeners()
})

onUnmounted(() => {
  // Cleanup listeners when component is destroyed
  bookingStore.cleanupRealtimeListeners()
})

const conflictCount = computed(() => {
  if (!bookingStore.conflicts) return 0
  const overlappingIds = new Set()
  
  bookingStore.conflicts.overlapping?.forEach(overlap => {
    overlappingIds.add(overlap.booking1.id)
    overlappingIds.add(overlap.booking2.id)
  })
  
  bookingStore.conflicts.conflicts?.forEach(conflict => {
    conflict.bookings.forEach(b => overlappingIds.add(b.id))
  })
  
  return overlappingIds.size
})

const filteredBookings = computed(() => {
  let bookings = bookingStore.bookings

  // Apply search filter by user name
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    bookings = bookings.filter(booking => 
      booking.user_name?.toLowerCase().includes(query)
    )
  }

  // Apply date filter
  if (dateFilter.value) {
    bookings = bookings.filter(booking => 
      booking.date === dateFilter.value
    )
  }

  // Apply type filter
  if (filterType.value === 'conflicts') {
    bookings = bookings.filter(booking => hasConflict(booking))
  } else if (filterType.value === 'upcoming') {
    bookings = bookings.filter(booking => isUpcoming(booking.date))
  }

  // Apply sorting
  bookings = [...bookings].sort((a, b) => {
    let aValue = a[sortColumn.value]
    let bValue = b[sortColumn.value]
    
    if (sortColumn.value === 'date') {
      aValue = new Date(a.date + ' ' + a.start_time)
      bValue = new Date(b.date + ' ' + b.start_time)
    }
    
    if (sortDirection.value === 'asc') {
      return aValue > bValue ? 1 : -1
    } else {
      return aValue < bValue ? 1 : -1
    }
  })

  return bookings
})

const totalPages = computed(() => {
  return Math.ceil(filteredBookings.value.length / itemsPerPage.value)
})

const paginatedBookings = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredBookings.value.slice(start, end)
})

const displayedPages = computed(() => {
  const pages = []
  const total = totalPages.value
  const current = currentPage.value
  
  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) pages.push(i)
      pages.push('...')
      pages.push(total)
    } else if (current >= total - 3) {
      pages.push(1)
      pages.push('...')
      for (let i = total - 4; i <= total; i++) pages.push(i)
    } else {
      pages.push(1)
      pages.push('...')
      for (let i = current - 1; i <= current + 1; i++) pages.push(i)
      pages.push('...')
      pages.push(total)
    }
  }
  
  return pages
})

function formatDate(dateString) {
  const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }
  return new Date(dateString).toLocaleDateString('en-US', options)
}

function formatTime(timeString) {
  // Remove seconds if present (HH:MM:SS -> HH:MM)
  return timeString.substring(0, 5)
}

function calculateDuration(booking) {
  const start = new Date('2000-01-01 ' + booking.start_time)
  const end = new Date('2000-01-01 ' + booking.end_time)
  const diff = (end - start) / 1000 / 60 // minutes
  
  const hours = Math.floor(diff / 60)
  const minutes = diff % 60
  
  if (hours > 0 && minutes > 0) {
    return `${hours}h ${minutes}m`
  } else if (hours > 0) {
    return `${hours}h`
  } else {
    return `${minutes}m`
  }
}

function isUpcoming(dateString) {
  const bookingDate = new Date(dateString)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return bookingDate >= today
}

function hasConflict(booking) {
  if (!bookingStore.conflicts) return false
  
  // Check if booking is in overlapping list
  const isOverlapping = bookingStore.conflicts.overlapping?.some(overlap => 
    overlap.booking1.id === booking.id || overlap.booking2.id === booking.id
  )
  
  // Check if booking is in exact conflicts list
  const isExactConflict = bookingStore.conflicts.conflicts?.some(conflict =>
    conflict.bookings.some(b => b.id === booking.id)
  )
  
  return isOverlapping || isExactConflict
}

function sortBy(column) {
  if (sortColumn.value === column) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortColumn.value = column
    sortDirection.value = 'asc'
  }
}

function editBooking(bookingId) {
  router.push('/bookings/edit/' + bookingId)
}

function addBooking() {
  router.push('/bookings/add')
}

function confirmDelete(booking) {
  bookingToDelete.value = booking
  showDeleteConfirm.value = true
}

function showConflictInfo(booking) {
  if (!hasConflict(booking)) return
  selectedConflictBooking.value = booking
  showConflictDetails.value = true
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

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>
