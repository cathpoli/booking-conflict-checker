<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h2>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
          </svg>
          Conflict Details
        </h2>
        <button @click="$emit('close')" class="btn-close">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <div class="modal-body">
        <!-- Original Booking Info -->
        <div class="booking-info">
          <h3>Selected Booking</h3>
          <div class="info-card">
            <div class="info-row">
              <span class="label">User:</span>
              <span class="value">{{ booking.user_name || 'Unknown' }}</span>
            </div>
            <div class="info-row">
              <span class="label">Date:</span>
              <span class="value">{{ formatDate(booking.date) }}</span>
            </div>
            <div class="info-row">
              <span class="label">Time:</span>
              <span class="value">{{ booking.start_time }} - {{ booking.end_time }}</span>
            </div>
          </div>
        </div>

        <!-- Conflicting Bookings -->
        <div class="conflicts-section">
          <h3>Conflicts With ({{ conflictingBookings.length }})</h3>
          
          <div v-for="conflict in conflictingBookings" :key="conflict.booking.id" class="conflict-card">
            <div class="conflict-header">
              <span class="conflict-badge">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="8" x2="12" y2="12"></line>
                  <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                Booking #{{ conflict.booking.id }}
              </span>
              <span class="overlap-duration">
                Overlap: {{ conflict.overlapDuration }}
              </span>
            </div>

            <div class="conflict-details">
              <div class="detail-row">
                <span class="label">User:</span>
                <span class="value user-badge">{{ conflict.booking.user_name || 'Unknown' }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Date:</span>
                <span class="value">{{ formatDate(conflict.booking.date) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Time:</span>
                <span class="value">{{ conflict.booking.start_time }} - {{ conflict.booking.end_time }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Overlap Period:</span>
                <span class="value overlap-time">{{ conflict.overlapStart }} - {{ conflict.overlapEnd }}</span>
              </div>
            </div>
          </div>

          <div v-if="conflictingBookings.length === 0" class="no-conflicts">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <p>No conflicts found for this booking</p>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button @click="$emit('close')" class="btn-secondary">Close</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  booking: {
    type: Object,
    required: true
  },
  conflicts: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close'])

// Find all bookings that conflict with the selected booking
const conflictingBookings = computed(() => {
  const conflicts = []
  
  // Check overlapping bookings
  if (props.conflicts.overlapping) {
    props.conflicts.overlapping.forEach(overlap => {
      if (overlap.booking1.id === props.booking.id) {
        conflicts.push({
          booking: overlap.booking2,
          overlapStart: getMaxTime(overlap.booking1.start_time, overlap.booking2.start_time),
          overlapEnd: getMinTime(overlap.booking1.end_time, overlap.booking2.end_time),
          overlapDuration: calculateOverlapDuration(overlap.booking1, overlap.booking2)
        })
      } else if (overlap.booking2.id === props.booking.id) {
        conflicts.push({
          booking: overlap.booking1,
          overlapStart: getMaxTime(overlap.booking1.start_time, overlap.booking2.start_time),
          overlapEnd: getMinTime(overlap.booking1.end_time, overlap.booking2.end_time),
          overlapDuration: calculateOverlapDuration(overlap.booking1, overlap.booking2)
        })
      }
    })
  }
  
  // Check exact conflicts
  if (props.conflicts.conflicts) {
    props.conflicts.conflicts.forEach(conflict => {
      conflict.bookings.forEach(b => {
        if (b.id !== props.booking.id && b.date === props.booking.date) {
          conflicts.push({
            booking: b,
            overlapStart: b.start_time,
            overlapEnd: b.end_time,
            overlapDuration: 'Exact duplicate'
          })
        }
      })
    })
  }
  
  return conflicts
})

function formatDate(dateString) {
  const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }
  return new Date(dateString).toLocaleDateString('en-US', options)
}

function getMaxTime(time1, time2) {
  return time1 > time2 ? time1 : time2
}

function getMinTime(time1, time2) {
  return time1 < time2 ? time1 : time2
}

function calculateOverlapDuration(booking1, booking2) {
  const start = new Date(`2000-01-01 ${getMaxTime(booking1.start_time, booking2.start_time)}`)
  const end = new Date(`2000-01-01 ${getMinTime(booking1.end_time, booking2.end_time)}`)
  const diff = (end - start) / 1000 / 60 // minutes
  
  if (diff <= 0) return '0 minutes'
  
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
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 16px;
  max-width: 700px;
  width: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem 2rem;
  border-bottom: 2px solid #e2e8f0;
}

.modal-header h2 {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0;
  color: #c53030;
  font-size: 1.5rem;
}

.modal-header h2 svg {
  width: 28px;
  height: 28px;
}

.btn-close {
  padding: 0.5rem;
  background: transparent;
  border: none;
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.2s ease;
  color: #718096;
}

.btn-close:hover {
  background: #f7fafc;
  color: #2d3748;
}

.btn-close svg {
  width: 24px;
  height: 24px;
}

.modal-body {
  padding: 2rem;
  overflow-y: auto;
  flex: 1;
}

.booking-info {
  margin-bottom: 2rem;
}

.booking-info h3 {
  color: #2d3748;
  margin-bottom: 1rem;
  font-size: 1.125rem;
  font-weight: 600;
}

.info-card {
  background: #f7fafc;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.25rem;
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
}

.info-row:not(:last-child) {
  border-bottom: 1px solid #e2e8f0;
}

.label {
  font-weight: 600;
  color: #4a5568;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.value {
  color: #2d3748;
  font-weight: 500;
}

.conflicts-section h3 {
  color: #2d3748;
  margin-bottom: 1rem;
  font-size: 1.125rem;
  font-weight: 600;
}

.conflict-card {
  background: #fff5f5;
  border: 2px solid #fc8181;
  border-radius: 12px;
  padding: 1.25rem;
  margin-bottom: 1rem;
}

.conflict-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #feb2b2;
}

.conflict-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: white;
  color: #c53030;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.875rem;
}

.conflict-badge svg {
  width: 16px;
  height: 16px;
}

.overlap-duration {
  padding: 0.5rem 1rem;
  background: #c53030;
  color: white;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.875rem;
}

.conflict-details {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.user-badge {
  background: #edf2f7;
  padding: 0.375rem 0.875rem;
  border-radius: 20px;
  font-weight: 600;
  color: #2d3748;
}

.overlap-time {
  color: #c53030;
  font-weight: 700;
}

.no-conflicts {
  text-align: center;
  padding: 3rem 2rem;
  color: #718096;
}

.no-conflicts svg {
  width: 64px;
  height: 64px;
  color: #a0aec0;
  margin-bottom: 1rem;
}

.no-conflicts p {
  font-size: 1.125rem;
}

.modal-footer {
  padding: 1.5rem 2rem;
  border-top: 2px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
}

.btn-secondary {
  padding: 0.75rem 1.5rem;
  background: white;
  color: #4a5568;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary:hover {
  background: #f7fafc;
  border-color: #cbd5e0;
}

@media (max-width: 768px) {
  .modal-content {
    max-width: 100%;
    max-height: 100vh;
    border-radius: 0;
  }

  .conflict-header {
    flex-direction: column;
    gap: 0.75rem;
    align-items: flex-start;
  }

  .detail-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
}
</style>
