import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/LoginView.vue'),
    meta: { requiresGuest: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../views/RegisterView.vue'),
    meta: { requiresGuest: true }
  },
  {
    path: '/',
    redirect: (to) => {
      const authStore = useAuthStore()
      return authStore.isAdmin ? '/dashboard' : '/bookings'
    }
  },
  {
    path: '/bookings',
    name: 'Bookings',
    component: () => import('../views/HomeView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/bookings/add',
    name: 'AddBooking',
    component: () => import('../views/BookingFormView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/bookings/edit/:id',
    name: 'EditBooking',
    component: () => import('../views/BookingFormView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('../views/DashboardView.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'Login' })
  } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
    next({ name: 'Home' })
  } else if (to.meta.requiresAdmin && !authStore.isAdmin) {
    next({ name: 'Bookings' })
  } else {
    next()
  }
})

export default router
