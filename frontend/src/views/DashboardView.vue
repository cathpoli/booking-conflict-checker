<template>
  <div class="dashboard-container">
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
      <div class="dashboard-header">
        <h1>Admin Dashboard</h1>
        <router-link to="/bookings" class="btn-secondary">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
          View All Bookings
        </router-link>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading dashboard...</p>
      </div>

      <div v-else class="dashboard-grid">
        <!-- Summary Cards -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon total">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
            </div>
            <div class="stat-info">
              <h3>Total Bookings</h3>
              <p class="stat-number">{{ summary.total_bookings || 0 }}</p>
            </div>
          </div>

          <div class="stat-card conflict">
            <div class="stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
              </svg>
            </div>
            <div class="stat-info">
              <h3>Overlapping Bookings</h3>
              <p class="stat-number">{{ summary.overlapping_count || 0 }}</p>
            </div>
          </div>

          <router-link to="/bookings" class="stat-card gaps clickable">
            <div class="stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
            </div>
            <div class="stat-info">
              <h3>View All User Bookings</h3>
              <p class="stat-link">Click to view →</p>
            </div>
          </router-link>
        </div>

        <!-- Overlapping Bookings Section -->
        <div v-if="report.overlapping && report.overlapping.length > 0" class="report-section">
          <h2>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="15" y1="9" x2="9" y2="15"></line>
              <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            Overlapping Bookings ({{ report.overlapping.length }})
          </h2>
          <div class="conflicts-list">
            <div v-for="(overlap, index) in report.overlapping" :key="index" class="conflict-item overlap">
              <div class="conflict-badge">Overlap #{{ index + 1 }}</div>
              <div class="conflict-details">
                <div class="booking-pair">
                  <div class="booking-info">
                    <span class="booking-label">Booking #{{ overlap.booking1.id }}</span>
                    <span class="user-badge">{{ overlap.booking1.user_name }}</span>
                    <span>{{ formatDate(overlap.booking1.date) }}</span>
                    <span class="time">{{ overlap.booking1.start_time }} - {{ overlap.booking1.end_time }}</span>
                  </div>
                  <div class="overlap-indicator">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                  </div>
                  <div class="booking-info">
                    <span class="booking-label">Booking #{{ overlap.booking2.id }}</span>
                    <span class="user-badge">{{ overlap.booking2.user_name }}</span>
                    <span>{{ formatDate(overlap.booking2.date) }}</span>
                    <span class="time">{{ overlap.booking2.start_time }} - {{ overlap.booking2.end_time }}</span>
                  </div>
                </div>
                <div class="overlap-summary">
                  <span class="overlap-time">Overlap: {{ overlap.overlap_start }} - {{ overlap.overlap_end }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Exact Conflicts Section -->
        <div v-if="report.conflicts && report.conflicts.length > 0" class="report-section">
          <h2>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            Exact Conflicts ({{ report.conflicts.length }})
          </h2>
          <div class="conflicts-list">
            <div v-for="(conflict, index) in report.conflicts" :key="index" class="conflict-item exact">
              <div class="conflict-badge exact">Exact Conflict #{{ index + 1 }}</div>
              <div class="conflict-details">
                <div class="conflict-time">
                  {{ formatDate(conflict.date) }} • {{ conflict.start_time }} - {{ conflict.end_time }}
                </div>
                <div class="conflicting-bookings">
                  <div v-for="booking in conflict.bookings" :key="booking.id" class="booking-chip">
                    <span class="chip-label">Booking #{{ booking.id }}</span>
                    <span class="chip-user">{{ booking.user_name }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Gaps Section -->
        <div v-if="report.gaps && report.gaps.length > 0" class="report-section">
          <h2>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            Time Gaps ({{ report.gaps.length }})
          </h2>
          <div class="gaps-list">
            <div v-for="(gap, index) in report.gaps" :key="index" class="gap-item">
              <div class="gap-info">
                <span class="gap-date">{{ formatDate(gap.date) }}</span>
                <span class="gap-time">{{ gap.gap_start }} - {{ gap.gap_end }}</span>
                <span class="gap-duration">{{ gap.gap_minutes }} minutes</span>
              </div>
            </div>
          </div>
        </div>

        <!-- No Issues -->
        <div v-if="!loading && summary.overlapping_count === 0 && summary.exact_conflicts_count === 0" class="no-issues">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
          <h3>No Conflicts Detected!</h3>
          <p>All bookings are scheduled without any overlaps or conflicts.</p>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import '../assets/bookings.css'
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../composables/api'

const router = useRouter()
const authStore = useAuthStore()
const loading = ref(false)
const summary = ref({})
const report = ref({})

onMounted(async () => {
  await fetchReport()
})

async function fetchReport() {
  loading.value = true
  try {
    const response = await api.get('/admin/bookings/conflicts/report')
    summary.value = response.data.summary
    report.value = {
      overlapping: response.data.overlapping,
      conflicts: response.data.conflicts,
      gaps: response.data.gaps
    }
  } catch (error) {
    console.error('Failed to fetch report:', error)
  } finally {
    loading.value = false
  }
}

function formatDate(dateString) {
  const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }
  return new Date(dateString).toLocaleDateString('en-US', options)
}

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.dashboard-container {
  min-height: 100vh;
  background: #f8f9fa;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.dashboard-header h1 {
  color: #1a202c;
  font-size: 2rem;
  margin: 0;
}

.dashboard-grid {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  text-decoration: none;
  color: inherit;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.stat-card.clickable {
  cursor: pointer;
}

.stat-card.clickable:hover {
  box-shadow: 0 6px 16px rgba(72, 187, 120, 0.3);
}

.stat-card.clickable:active {
  transform: translateY(0);
}

.stat-icon {
  width: 64px;
  height: 64px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-icon.total {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card.conflict .stat-icon {
  background: linear-gradient(135deg, #fc8181 0%, #e53e3e 100%);
}

.stat-card.exact .stat-icon {
  background: linear-gradient(135deg, #f6ad55 0%, #dd6b20 100%);
}

.stat-card.gaps .stat-icon {
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
}

.stat-icon svg {
  width: 32px;
  height: 32px;
  color: white;
}

.stat-info h3 {
  margin: 0 0 0.5rem 0;
  color: #718096;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0;
}

.stat-link {
  font-size: 0.95rem;
  font-weight: 600;
  color: #38a169;
  margin: 0;
}

/* Report Sections */
.report-section {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.report-section h2 {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #1a202c;
  margin: 0 0 1.5rem 0;
  font-size: 1.25rem;
}

.report-section h2 svg {
  width: 24px;
  height: 24px;
  color: #c53030;
}

/* Conflict Items */
.conflicts-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.conflict-item {
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  background: #f7fafc;
}

.conflict-item.overlap {
  border-color: #fc8181;
  background: #fff5f5;
}

.conflict-item.exact {
  border-color: #f6ad55;
  background: #fffaf0;
}

.conflict-badge {
  display: inline-block;
  padding: 0.5rem 1rem;
  background: #c53030;
  color: white;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.conflict-badge.exact {
  background: #dd6b20;
}

.booking-pair {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 1.5rem;
  align-items: center;
  margin-bottom: 1rem;
}

.booking-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.booking-label {
  font-weight: 700;
  color: #2d3748;
  font-size: 1rem;
}

.user-badge {
  display: inline-block;
  padding: 0.375rem 0.875rem;
  background: #edf2f7;
  border-radius: 20px;
  font-weight: 600;
  color: #2d3748;
  font-size: 0.875rem;
  width: fit-content;
}

.time {
  color: #4a5568;
  font-weight: 600;
  font-family: 'Courier New', monospace;
}

.overlap-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
}

.overlap-indicator svg {
  width: 32px;
  height: 32px;
  color: #c53030;
}

.overlap-summary {
  text-align: center;
  padding-top: 1rem;
  border-top: 2px solid #feb2b2;
}

.overlap-time {
  display: inline-block;
  padding: 0.5rem 1.25rem;
  background: white;
  color: #c53030;
  border-radius: 8px;
  font-weight: 700;
  font-family: 'Courier New', monospace;
}

.conflict-time {
  font-size: 1.125rem;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 1rem;
}

.conflicting-bookings {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.booking-chip {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding: 0.75rem 1rem;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
}

.chip-label {
  font-weight: 700;
  color: #2d3748;
  font-size: 0.875rem;
}

.chip-user {
  color: #718096;
  font-size: 0.875rem;
}

/* Gaps */
.gaps-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

.gap-item {
  background: #f0fff4;
  border: 2px solid #9ae6b4;
  border-radius: 12px;
  padding: 1.25rem;
}

.gap-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.gap-date {
  font-weight: 700;
  color: #22543d;
}

.gap-time {
  font-family: 'Courier New', monospace;
  color: #2f855a;
  font-weight: 600;
}

.gap-duration {
  color: #38a169;
  font-size: 0.875rem;
  font-weight: 600;
}

/* No Issues */
.no-issues {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.no-issues svg {
  width: 80px;
  height: 80px;
  color: #48bb78;
  margin-bottom: 1.5rem;
}

.no-issues h3 {
  color: #1a202c;
  font-size: 1.75rem;
  margin-bottom: 0.5rem;
}

.no-issues p {
  color: #718096;
  font-size: 1.125rem;
}

@media (max-width: 768px) {
  .booking-pair {
    grid-template-columns: 1fr;
  }

  .overlap-indicator {
    transform: rotate(90deg);
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
