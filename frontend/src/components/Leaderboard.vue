<template>
  <div class="leaderboard-container">
    <h1>🏆 Leaderboard</h1>

    <div class="tabs">
      <button 
        :class="['tab', { active: activeTab === 'friends' }]"
        @click="activeTab = 'friends'"
      >
        👥 Vrienden
      </button>
      <button 
        :class="['tab', { active: activeTab === 'global' }]"
        @click="activeTab = 'global'"
      >
        🌍 Globaal
      </button>
    </div>

    <div v-if="loading" class="loading">
      Leaderboard laden...
    </div>

    <div v-else class="leaderboard-content">
      <!-- Friends Leaderboard -->
      <div v-if="activeTab === 'friends'" class="leaderboard-section">
        <div v-if="friendsLeaderboard.length === 0" class="no-data">
          <p>Je hebt nog geen vrienden om te vergelijken.</p>
          <router-link to="/friends" class="btn btn-primary">Vrienden Toevoegen</router-link>
        </div>
        
        <div v-else class="leaderboard-list">
          <div 
            v-for="(user, index) in friendsLeaderboard" 
            :key="user.id"
            :class="['leaderboard-card', { 'is-current-user': user.id === currentUserId }]"
          >
            <div class="rank">
              <span v-if="index === 0" class="medal gold">🥇</span>
              <span v-else-if="index === 1" class="medal silver">🥈</span>
              <span v-else-if="index === 2" class="medal bronze">🥉</span>
              <span v-else class="rank-number">#{{ index + 1 }}</span>
            </div>
            
            <div class="user-info">
              <div class="user-name">
                {{ user.display_name || user.username }}
                <span v-if="user.id === currentUserId" class="you-badge">Jij</span>
              </div>
              <div class="user-stats">
                {{ user.total_cards }} kaarten • {{ user.unique_sets }} sets
              </div>
            </div>
            
            <div class="user-value">
              €{{ formatPrice(user.total_value) }}
            </div>
            
            <router-link 
              :to="`/user/${user.id}`" 
              class="view-button"
              v-if="user.id !== currentUserId"
            >
              👁️ Bekijk
            </router-link>
          </div>
        </div>
      </div>

      <!-- Global Leaderboard -->
      <div v-if="activeTab === 'global'" class="leaderboard-section">
        <div v-if="globalLeaderboard.length === 0" class="no-data">
          <p>Geen gebruikers gevonden.</p>
        </div>
        
        <div v-else class="leaderboard-list">
          <div 
            v-for="(user, index) in globalLeaderboard" 
            :key="user.id"
            :class="['leaderboard-card', { 'is-current-user': user.id === currentUserId }]"
          >
            <div class="rank">
              <span v-if="index === 0" class="medal gold">🥇</span>
              <span v-else-if="index === 1" class="medal silver">🥈</span>
              <span v-else-if="index === 2" class="medal bronze">🥉</span>
              <span v-else class="rank-number">#{{ index + 1 }}</span>
            </div>
            
            <div class="user-info">
              <div class="user-name">
                {{ user.display_name || user.username }}
                <span v-if="user.id === currentUserId" class="you-badge">Jij</span>
              </div>
              <div class="user-stats">
                {{ user.total_cards }} kaarten • {{ user.unique_sets }} sets
              </div>
            </div>
            
            <div class="user-value">
              €{{ formatPrice(user.total_value) }}
            </div>
            
            <router-link 
              :to="`/user/${user.id}`" 
              class="view-button"
              v-if="user.id !== currentUserId && (user.is_public || user.is_friend)"
            >
              👁️ Bekijk
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../services/api'

export default {
  name: 'Leaderboard',
  data() {
    return {
      activeTab: 'friends',
      friendsLeaderboard: [],
      globalLeaderboard: [],
      currentUserId: null,
      loading: true
    }
  },
  
  async mounted() {
    await this.loadCurrentUser()
    await this.loadLeaderboards()
  },

  methods: {
    async loadCurrentUser() {
      try {
        const response = await api.getCurrentUser()
        this.currentUserId = response.data.user.id
      } catch (error) {
        console.error('Error loading current user:', error)
      }
    },

    async loadLeaderboards() {
      this.loading = true
      try {
        const [friendsRes, globalRes] = await Promise.all([
          api.getFriendsLeaderboard(),
          api.getGlobalLeaderboard()
        ])
        
        this.friendsLeaderboard = friendsRes.data.data || []
        this.globalLeaderboard = globalRes.data.data || []
      } catch (error) {
        console.error('Error loading leaderboards:', error)
      } finally {
        this.loading = false
      }
    },

    formatPrice(price) {
      if (!price || price === 0) return '0.00'
      return parseFloat(price).toFixed(2)
    }
  }
}
</script>

<style scoped>
.leaderboard-container {
  max-width: 900px;
  margin: 0 auto;
  padding: 20px;
}

h1 {
  color: #2c3e50;
  margin-bottom: 20px;
  text-align: center;
}

.tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 30px;
  justify-content: center;
}

.tab {
  padding: 12px 30px;
  border: 2px solid #e0e0e0;
  background: white;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.tab:hover {
  border-color: #3498db;
  transform: translateY(-2px);
}

.tab.active {
  background: #3498db;
  color: white;
  border-color: #3498db;
}

.loading {
  text-align: center;
  padding: 60px 20px;
  color: #666;
  font-size: 1.1rem;
}

.leaderboard-content {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  overflow: hidden;
}

.leaderboard-list {
  padding: 20px;
}

.leaderboard-card {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 20px;
  margin-bottom: 15px;
  background: #f8f9fa;
  border-radius: 8px;
  transition: all 0.3s;
  border: 2px solid transparent;
}

.leaderboard-card:hover {
  transform: translateX(5px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.leaderboard-card.is-current-user {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-color: #667eea;
}

.leaderboard-card.is-current-user .user-stats {
  color: rgba(255,255,255,0.9);
}

.rank {
  min-width: 60px;
  text-align: center;
}

.medal {
  font-size: 2rem;
}

.rank-number {
  font-size: 1.5rem;
  font-weight: 700;
  color: #666;
}

.is-current-user .rank-number {
  color: white;
}

.user-info {
  flex: 1;
}

.user-name {
  font-size: 1.2rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 5px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.is-current-user .user-name {
  color: white;
}

.you-badge {
  background: rgba(255,255,255,0.3);
  padding: 2px 10px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
}

.user-stats {
  color: #666;
  font-size: 0.9rem;
}

.user-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #27ae60;
  min-width: 120px;
  text-align: right;
}

.is-current-user .user-value {
  color: #fff;
}

.view-button {
  padding: 8px 16px;
  background: #3498db;
  color: white;
  text-decoration: none;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.9rem;
  transition: all 0.3s;
  white-space: nowrap;
}

.view-button:hover {
  background: #2980b9;
  transform: translateY(-2px);
}

.is-current-user .view-button {
  background: rgba(255,255,255,0.3);
}

.no-data {
  text-align: center;
  padding: 60px 20px;
  color: #666;
}

.no-data p {
  margin-bottom: 20px;
  font-size: 1.1rem;
}

.btn {
  display: inline-block;
  padding: 12px 24px;
  background: #3498db;
  color: white;
  text-decoration: none;
  border-radius: 6px;
  font-weight: 600;
  transition: all 0.3s;
}

.btn:hover {
  background: #2980b9;
  transform: translateY(-2px);
}
</style>
