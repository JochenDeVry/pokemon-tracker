import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import CardList from './components/CardList.vue'
import CardForm from './components/CardForm.vue'
import Auth from './components/Auth.vue'
import Friends from './components/Friends.vue'
import Profile from './components/Profile.vue'
import Leaderboard from './components/Leaderboard.vue'
import Chat from './components/Chat.vue'
import api from './services/api.js'

const routes = [
  { path: '/login', component: Auth, meta: { public: true } },
  { path: '/', component: CardList, meta: { requiresAuth: true } },
  { path: '/add', component: CardForm, meta: { requiresAuth: true } },
  { path: '/edit/:id', component: CardForm, props: true, meta: { requiresAuth: true } },
  { path: '/friends', component: Friends, meta: { requiresAuth: true } },
  { path: '/profile', component: Profile, meta: { requiresAuth: true } },
  { path: '/leaderboard', component: Leaderboard, meta: { requiresAuth: true } },
  { path: '/chat', component: Chat, meta: { requiresAuth: true } },
  { path: '/user/:userId', component: CardList, props: true, meta: { requiresAuth: true } }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard for authentication
router.beforeEach(async (to, from, next) => {
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const isPublic = to.matched.some(record => record.meta.public)
  
  if (!requiresAuth && !isPublic) {
    next()
    return
  }
  
  try {
    const response = await api.getCurrentUser()
    const isAuthenticated = response.data.success
    
    if (requiresAuth && !isAuthenticated) {
      next('/login')
    } else if (isPublic && isAuthenticated && to.path === '/login') {
      next('/')
    } else {
      next()
    }
  } catch (err) {
    if (requiresAuth) {
      next('/login')
    } else {
      next()
    }
  }
})

const app = createApp(App)
app.use(router)
app.mount('#app')
