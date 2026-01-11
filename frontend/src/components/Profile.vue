<template>
  <div class="profile-container">
    <h1>👤 Mijn Profiel</h1>

    <div v-if="message" :class="['message', messageType]">
      {{ message }}
    </div>

    <div class="profile-form">
      <form @submit.prevent="saveProfile">
        <div class="form-group">
          <label for="username">Gebruikersnaam *</label>
          <input
            type="text"
            id="username"
            v-model="profile.username"
            required
            disabled
            class="disabled-input"
          />
          <small>Gebruikersnaam kan niet worden gewijzigd</small>
        </div>

        <div class="form-group">
          <label for="display_name">Weergavenaam *</label>
          <input
            type="text"
            id="display_name"
            v-model="profile.display_name"
            required
            placeholder="Je volledige naam"
          />
        </div>

        <div class="form-group">
          <label for="email">E-mailadres *</label>
          <input
            type="email"
            id="email"
            v-model="profile.email"
            required
            placeholder="je@email.com"
          />
        </div>

        <div class="form-group checkbox-group">
          <label>
            <input
              type="checkbox"
              v-model="profile.is_public"
            />
            Maak mijn verzameling publiek zichtbaar
          </label>
          <small>Wanneer privé, kunnen alleen vrienden je verzameling bekijken</small>
        </div>

        <div class="button-group">
          <button type="submit" class="btn btn-primary" :disabled="loading">
            {{ loading ? 'Opslaan...' : 'Profiel Opslaan' }}
          </button>
        </div>
      </form>

      <hr />

      <h2>🔒 Wachtwoord Wijzigen</h2>
      <form @submit.prevent="changePassword">
        <div class="form-group">
          <label for="current_password">Huidig Wachtwoord *</label>
          <input
            type="password"
            id="current_password"
            v-model="password.current"
            placeholder="Je huidige wachtwoord"
          />
        </div>

        <div class="form-group">
          <label for="new_password">Nieuw Wachtwoord *</label>
          <input
            type="password"
            id="new_password"
            v-model="password.new"
            placeholder="Minimaal 6 tekens"
          />
        </div>

        <div class="form-group">
          <label for="confirm_password">Bevestig Nieuw Wachtwoord *</label>
          <input
            type="password"
            id="confirm_password"
            v-model="password.confirm"
            placeholder="Herhaal nieuw wachtwoord"
          />
        </div>

        <div class="button-group">
          <button type="submit" class="btn btn-secondary" :disabled="loadingPassword">
            {{ loadingPassword ? 'Wijzigen...' : 'Wachtwoord Wijzigen' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import api from '../services/api'

export default {
  name: 'Profile',
  data() {
    return {
      profile: {
        username: '',
        display_name: '',
        email: '',
        is_public: false
      },
      password: {
        current: '',
        new: '',
        confirm: ''
      },
      loading: false,
      loadingPassword: false,
      message: '',
      messageType: 'success'
    }
  },
  
  async mounted() {
    await this.loadProfile()
  },

  methods: {
    async loadProfile() {
      try {
        const response = await api.getCurrentUser()
        const user = response.data.user
        this.profile = {
          username: user.username,
          display_name: user.display_name || user.username,
          email: user.email,
          is_public: user.is_public === 1
        }
      } catch (error) {
        console.error('Error loading profile:', error)
        this.showMessage('Kon profiel niet laden', 'error')
      }
    },

    async saveProfile() {
      this.loading = true
      this.message = ''

      try {
        await api.updateProfile({
          display_name: this.profile.display_name,
          email: this.profile.email,
          is_public: this.profile.is_public ? 1 : 0
        })
        this.showMessage('Profiel succesvol bijgewerkt!', 'success')
      } catch (error) {
        console.error('Error updating profile:', error)
        this.showMessage(error.response?.data?.message || 'Kon profiel niet bijwerken', 'error')
      } finally {
        this.loading = false
      }
    },

    async changePassword() {
      if (!this.password.current || !this.password.new || !this.password.confirm) {
        this.showMessage('Vul alle wachtwoordvelden in', 'error')
        return
      }

      if (this.password.new.length < 6) {
        this.showMessage('Nieuw wachtwoord moet minimaal 6 tekens zijn', 'error')
        return
      }

      if (this.password.new !== this.password.confirm) {
        this.showMessage('Nieuwe wachtwoorden komen niet overeen', 'error')
        return
      }

      this.loadingPassword = true
      this.message = ''

      try {
        await api.changePassword({
          current_password: this.password.current,
          new_password: this.password.new
        })
        this.showMessage('Wachtwoord succesvol gewijzigd!', 'success')
        this.password = { current: '', new: '', confirm: '' }
      } catch (error) {
        console.error('Error changing password:', error)
        this.showMessage(error.response?.data?.message || 'Kon wachtwoord niet wijzigen', 'error')
      } finally {
        this.loadingPassword = false
      }
    },

    showMessage(text, type) {
      this.message = text
      this.messageType = type
      setTimeout(() => {
        this.message = ''
      }, 5000)
    }
  }
}
</script>

<style scoped>
.profile-container {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px;
}

h1 {
  color: #2c3e50;
  margin-bottom: 30px;
}

h2 {
  color: #2c3e50;
  margin-top: 30px;
  margin-bottom: 20px;
  font-size: 1.5rem;
}

.profile-form {
  background: white;
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 600;
  color: #2c3e50;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"] {
  width: 100%;
  padding: 10px;
  border: 2px solid #e0e0e0;
  border-radius: 4px;
  font-size: 1rem;
  transition: border-color 0.3s;
}

.form-group input:focus {
  outline: none;
  border-color: #3498db;
}

.disabled-input {
  background-color: #f5f5f5;
  cursor: not-allowed;
}

.form-group small {
  display: block;
  margin-top: 5px;
  color: #666;
  font-size: 0.875rem;
}

.checkbox-group label {
  display: flex;
  align-items: center;
  font-weight: 400;
  cursor: pointer;
}

.checkbox-group input[type="checkbox"] {
  width: auto;
  margin-right: 10px;
  cursor: pointer;
}

.button-group {
  margin-top: 25px;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-primary {
  background-color: #3498db;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2980b9;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.btn-secondary {
  background-color: #95a5a6;
  color: white;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #7f8c8d;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.message {
  padding: 12px 20px;
  border-radius: 4px;
  margin-bottom: 20px;
  font-weight: 500;
}

.message.success {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.message.error {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

hr {
  margin: 40px 0;
  border: none;
  border-top: 2px solid #e0e0e0;
}
</style>
