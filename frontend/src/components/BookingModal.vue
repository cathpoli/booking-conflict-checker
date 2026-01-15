<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-container">
      <div class="modal-header">
        <h2>{{ isEdit ? 'Edit Booking' : 'Add New Booking' }}</h2>
        <button @click="$emit('close')" class="btn-close">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-body">
        <div class="form-group">
          <label for="date">Date</label>
          <input
            type="date"
            id="date"
            v-model="form.date"
            required
            class="form-input"
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
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label for="end_time">End Time</label>
            <input
              type="time"
              id="end_time"
              v-model="form.end_time"
              required
              class="form-input"
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

        <div class="modal-footer">
          <button type="button" @click="$emit('close')" class="btn-secondary">
            Cancel
          </button>
          <button type="submit" :disabled="bookingStore.loading" class="btn-primary">
            <span v-if="bookingStore.loading" class="spinner"></span>
            {{ bookingStore.loading ? 'Saving...' : (isEdit ? 'Update Booking' : 'Create Booking') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useBookingStore } from '../stores/booking'

const props = defineProps({
  booking: Object,
  isEdit: Boolean
})

const emit = defineEmits(['close', 'save'])
const bookingStore = useBookingStore()

const form = ref({
  date: '',
  start_time: '',
  end_time: ''
})

onMounted(() => {
  if (props.isEdit && props.booking) {
    form.value = {
      date: props.booking.date,
      start_time: props.booking.start_time,
      end_time: props.booking.end_time
    }
  }
})

watch(() => props.booking, (newBooking) => {
  if (newBooking && props.isEdit) {
    form.value = {
      date: newBooking.date,
      start_time: newBooking.start_time,
      end_time: newBooking.end_time
    }
  }
})

function handleSubmit() {
  emit('save', form.value)
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
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-container {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow: hidden;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
  font-size: 1.5rem;
  color: #1a202c;
  margin: 0;
}

.btn-close {
  padding: 0.5rem;
  background: transparent;
  border: none;
  cursor: pointer;
  border-radius: 6px;
  transition: background 0.2s ease;
}

.btn-close:hover {
  background: #f7fafc;
}

.btn-close svg {
  width: 24px;
  height: 24px;
  color: #4a5568;
}

.modal-body {
  padding: 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #2d3748;
  font-weight: 600;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-input {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 1rem;
  box-sizing: border-box;
  transition: all 0.3s ease;
  background: white;
  color: #2d3748;
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.error-message {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  color: #c53030;
  margin-bottom: 1rem;
  padding: 0.875rem 1rem;
  background: #fff5f5;
  border: 1px solid #feb2b2;
  border-radius: 10px;
  font-size: 0.875rem;
  white-space: pre-line;
}

.error-message svg {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.modal-footer {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
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

.btn-primary {
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  background: #cbd5e0;
  cursor: not-allowed;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
