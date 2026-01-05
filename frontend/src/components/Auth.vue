<template>
  <div class="auth-page">
    <div class="auth-container">
      <h1>🎴 Pokemon Card Tracker</h1>
      
      <div class="auth-tabs">
        <button 
          :class="['tab', { active: mode === 'login' }]"
          @click="mode = 'login'"
        >
          Inloggen
        </button>
        <button 
          :class="['tab', { active: mode === 'register' }]"
          @click="mode = 'register'"
        >
          Registreren
        </button>
      </div>

      <div v-if="message.text" :class="['alert', message.type === 'error' ? 'alert-error' : 'alert-success']">
        {{ message.text }}
      </div>

      <!-- Login Form -->
      <form v-if="mode === 'login'" @submit.prevent="handleLogin" class="auth-form">
        <h2>Welkom terug!</h2>
        
        <div class="form-group">
          <label>Email of Gebruikersnaam</label>
          <input 
            type="text" 
            v-model="loginForm.email" 
            required 
            placeholder="je@email.com"
            autofocus
          />
        </div>

        <div class="form-group">
          <label>Wachtwoord</label>
          <input 
            type="password" 
            v-model="loginForm.password" 
            required 
            placeholder="••••••••"
          />
        </div>

        <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
          {{ loading ? 'Inloggen...' : '🔐 Inloggen' }}
        </button>
      </form>

      <!-- Register Form -->
      <form v-else @submit.prevent="handleRegister" class="auth-form">
        <h2>Maak een account aan</h2>
        
        <div class="form-group">
          <label>Gebruikersnaam *</label>
          <input 
            type="text" 
            v-model="registerForm.username" 
            required 
            placeholder="pokemonmaster"
            autofocus
          />
          <small>Unieke gebruikersnaam voor je profiel</small>
        </div>

        <div class="form-group">
          <label>Email *</label>
          <input 
            type="email" 
            v-model="registerForm.email" 
            required 
            placeholder="je@email.com"
          />
        </div>

        <div class="form-group">
          <label>Weergavenaam (optioneel)</label>
          <input 
            type="text" 
            v-model="registerForm.displayName" 
            placeholder="Ash Ketchum"
          />
          <small>Deze naam wordt getoond aan andere gebruikers</small>
        </div>

        <div class="form-group">
          <label>Wachtwoord *</label>
          <input 
            type="password" 
            v-model="registerForm.password" 
            required 
            minlength="6"
            placeholder="••••••••"
          />
          <small>Minimaal 6 karakters</small>
        </div>

        <div class="form-group">
          <label>Bevestig Wachtwoord *</label>
          <input 
            type="password" 
            v-model="registerForm.confirmPassword" 
            required 
            minlength="6"
            placeholder="••••••••"
          />
        </div>

        <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
          {{ loading ? 'Account aanmaken...' : '✨ Account Aanmaken' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script>
import api from '../services/api.js'

export default {
  name: 'Auth',
  data() {
    return {
      mode: 'login', // 'login' or 'register'
      loading: false,
      message: { text: '', type: '' },
      loginForm: {
        email: '',
        password: ''
      },
      registerForm: {
        username: '',
        email: '',
        displayName: '',
        password: '',
        confirmPassword: ''
      }
    }
  },
  methods: {
    async handleLogin() {
      this.loading = true
      this.message = { text: '', type: '' }

      try {
        const response = await api.login(
          this.loginForm.email,
          this.loginForm.password
        )

        if (response.data.success) {
          this.$emit('login', response.data.user)
          this.$router.push('/')
        }
      } catch (err) {
        this.showMessage(
          err.response?.data?.message || 'Inloggen mislukt. Probeer opnieuw.',
          'error'
        )
      } finally {
        this.loading = false
      }
    },

    async handleRegister() {
      this.loading = true
      this.message = { text: '', type: '' }

      // Validate password match
      if (this.registerForm.password !== this.registerForm.confirmPassword) {
        this.showMessage('Wachtwoorden komen niet overeen', 'error')
        this.loading = false
        return
      }

      try {
        const response = await api.register(
          this.registerForm.username,
          this.registerForm.email,
          this.registerForm.password,
          this.registerForm.displayName || this.registerForm.username
        )

        if (response.data.success) {
          this.$emit('login', response.data.user)
          this.$router.push('/')
        }
      } catch (err) {
        this.showMessage(
          err.response?.data?.message || 'Registratie mislukt. Probeer opnieuw.',
          'error'
        )
      } finally {
        this.loading = false
      }
    },

    showMessage(text, type = 'success') {
      this.message = { text, type }
      setTimeout(() => {
        this.message = { text: '', type: '' }
      }, 5000)
    }
  }
}
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.auth-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-width: 480px;
  width: 100%;
  padding: 3rem;
}

.auth-container h1 {
  text-align: center;
  color: #667eea;
  margin-bottom: 2rem;
  font-size: 2rem;
}

.auth-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 2rem;
  border-bottom: 2px solid #e0e0e0;
}

.tab {
  flex: 1;
  padding: 1rem;
  border: none;
  background: none;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 600;
  color: #666;
  transition: all 0.3s;
  border-bottom: 3px solid transparent;
  margin-bottom: -2px;
}

.tab:hover {
  color: #667eea;
}

.tab.active {
  color: #667eea;
  border-bottom-color: #667eea;
}

.auth-form h2 {
  color: #333;
  margin-bottom: 1.5rem;
  font-size: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #333;
  font-weight: 600;
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.3s;
}

.form-group input:focus {
  outline: none;
  border-color: #667eea;
}

.form-group small {
  display: block;
  margin-top: 0.25rem;
  color: #666;
  font-size: 0.85rem;
}

.btn-block {
  width: 100%;
  padding: 1rem;
  font-size: 1.1rem;
  margin-top: 1rem;
}

.alert {
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
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

@media (max-width: 768px) {
  .auth-container {
    padding: 2rem 1.5rem;
  }

  .auth-container h1 {
    font-size: 1.5rem;
  }
}
</style>
