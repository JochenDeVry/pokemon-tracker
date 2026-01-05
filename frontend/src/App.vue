<template>
  <div id="app">
    <header v-if="currentUser">
      <div class="container">
        <h1>🎴 Pokemon Kaarten Tracker</h1>
        <nav>
          <router-link to="/" class="nav-link">Mijn Collectie</router-link>
          <router-link to="/add" class="nav-link btn-primary">+ Kaart Toevoegen</router-link>
          
          <div class="user-menu">
            <button @click="showUserDropdown = !showUserDropdown" class="user-button">
              <span>{{ currentUser.display_name || currentUser.username }}</span>
              <span class="dropdown-icon">▼</span>
            </button>
            
            <div v-if="showUserDropdown" class="dropdown-menu">
              <div class="dropdown-header">
                <strong>{{ currentUser.display_name }}</strong>
                <small>@{{ currentUser.username }}</small>
              </div>
              
              <div class="dropdown-divider"></div>
              
              <div class="dropdown-section">
                <div class="dropdown-label">Andere collecties bekijken:</div>
                <router-link 
                  v-for="user in otherUsers" 
                  :key="user.id"
                  :to="`/user/${user.id}`"
                  class="dropdown-item"
                  @click="showUserDropdown = false"
                >
                  👤 {{ user.display_name || user.username }}
                </router-link>
              </div>
              
              <div class="dropdown-divider"></div>
              
              <button @click="handleLogout" class="dropdown-item logout">
                🚪 Uitloggen
              </button>
            </div>
          </div>
        </nav>
      </div>
    </header>
    
    <main class="container">
      <router-view @login="handleLogin"></router-view>
    </main>

    <footer v-if="currentUser">
      <div class="container">
        <p>Pokemon Kaarten Tracker © 2026</p>
      </div>
    </footer>
  </div>
</template>

<script>
import api from './services/api.js'

export default {
  name: 'App',
  data() {
    return {
      currentUser: null,
      otherUsers: [],
      showUserDropdown: false
    }
  },
  async mounted() {
    await this.checkAuth()
    
    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.user-menu')) {
        this.showUserDropdown = false
      }
    })
  },
  methods: {
    async checkAuth() {
      try {
        const response = await api.getCurrentUser()
        if (response.data.success) {
          this.currentUser = response.data.user
          await this.loadUsers()
        }
      } catch (err) {
        this.currentUser = null
      }
    },
    
    async loadUsers() {
      try {
        const response = await api.getUsers()
        if (response.data.success) {
          this.otherUsers = response.data.data.filter(u => u.id !== this.currentUser.id)
        }
      } catch (err) {
        console.error('Failed to load users:', err)
      }
    },
    
    handleLogin(user) {
      this.currentUser = user
      this.loadUsers()
    },
    
    async handleLogout() {
      try {
        await api.logout()
        this.currentUser = null
        this.showUserDropdown = false
        this.$router.push('/login')
      } catch (err) {
        console.error('Logout failed:', err)
      }
    }
  }
}
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  color: #333;
}

#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

header {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  padding: 1.5rem 0;
  position: sticky;
  top: 0;
  z-index: 100;
}

header .container {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

header h1 {
  font-size: 1.8rem;
  color: #667eea;
  font-weight: 700;
}

nav {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.nav-link {
  text-decoration: none;
  color: #333;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  transition: all 0.3s;
  font-weight: 500;
}

.nav-link:hover {
  background: #f0f0f0;
}

.nav-link.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.nav-link.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.user-menu {
  position: relative;
  margin-left: 1rem;
}

.user-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: white;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  color: #333;
  transition: all 0.3s;
}

.user-button:hover {
  border-color: #667eea;
  background: #f8f9ff;
}

.dropdown-icon {
  font-size: 0.7rem;
  transition: transform 0.3s;
}

.user-button:hover .dropdown-icon {
  transform: translateY(2px);
}

.dropdown-menu {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  min-width: 280px;
  z-index: 1000;
  overflow: hidden;
  animation: dropdownFade 0.2s ease;
}

@keyframes dropdownFade {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.dropdown-header {
  padding: 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.dropdown-header strong {
  display: block;
  font-size: 1.1rem;
}

.dropdown-header small {
  opacity: 0.9;
}

.dropdown-divider {
  height: 1px;
  background: #e0e0e0;
  margin: 0.5rem 0;
}

.dropdown-section {
  padding: 0.5rem 0;
}

.dropdown-label {
  padding: 0.5rem 1rem;
  font-size: 0.85rem;
  color: #666;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.dropdown-item {
  display: block;
  width: 100%;
  padding: 0.75rem 1rem;
  border: none;
  background: none;
  text-align: left;
  cursor: pointer;
  transition: background 0.2s;
  color: #333;
  text-decoration: none;
  font-size: 0.95rem;
}

.dropdown-item:hover {
  background: #f8f9ff;
}

.dropdown-item.logout {
  color: #dc3545;
  font-weight: 600;
  margin-top: 0.5rem;
}

.dropdown-item.logout:hover {
  background: #fff5f5;
}

main {
  flex: 1;
  padding: 2rem 0;
}

footer {
  background: rgba(255, 255, 255, 0.95);
  padding: 1rem 0;
  text-align: center;
  color: #666;
  margin-top: 2rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s;
  font-weight: 500;
  text-decoration: none;
  display: inline-block;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #5a6268;
}

.btn-danger {
  background: #dc3545;
  color: white;
}

.btn-danger:hover {
  background: #c82333;
}

.btn-success {
  background: #28a745;
  color: white;
}

.btn-success:hover {
  background: #218838;
}

.card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
}

.form-group textarea {
  resize: vertical;
  min-height: 100px;
}

.alert {
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.alert-success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.alert-error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.alert-info {
  background: #d1ecf1;
  color: #0c5460;
  border: 1px solid #bee5eb;
}

.loading {
  text-align: center;
  padding: 2rem;
  color: white;
  font-size: 1.2rem;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

@media (max-width: 768px) {
  header .container {
    flex-direction: column;
    gap: 1rem;
  }

  .grid {
    grid-template-columns: 1fr;
  }
}
</style>
