<template>
  <div class="friends-container">
    <h1>👥 Vrienden</h1>

    <!-- Pending Requests -->
    <div v-if="pendingRequests.length > 0" class="requests-section">
      <h2>📬 Vriendschapsverzoeken ({{ pendingRequests.length }})</h2>
      <div class="requests-list">
        <div v-for="request in pendingRequests" :key="request.id" class="request-card">
          <div class="user-info">
            <div class="user-avatar">{{ request.display_name.charAt(0).toUpperCase() }}</div>
            <div>
              <div class="user-name">{{ request.display_name || request.username }}</div>
              <div class="user-meta">@{{ request.username }}</div>
              <div class="request-date">{{ formatDate(request.request_date) }}</div>
            </div>
          </div>
          <div class="request-actions">
            <button @click="acceptRequest(request.id)" class="btn btn-primary btn-sm">
              ✓ Accepteren
            </button>
            <button @click="declineRequest(request.id)" class="btn btn-secondary btn-sm">
              ✗ Afwijzen
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Sent Requests -->
    <div v-if="sentRequests.length > 0" class="requests-section">
      <h2>📤 Verzonden Verzoeken ({{ sentRequests.length }})</h2>
      <div class="requests-list">
        <div v-for="request in sentRequests" :key="request.id" class="request-card">
          <div class="user-info">
            <div class="user-avatar">{{ request.display_name.charAt(0).toUpperCase() }}</div>
            <div>
              <div class="user-name">{{ request.display_name || request.username }}</div>
              <div class="user-meta">@{{ request.username }}</div>
              <div class="request-date">Verzonden {{ formatDate(request.request_date) }}</div>
            </div>
          </div>
          <div class="request-status">
            <span class="status-badge pending">In afwachting...</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Search Users -->
    <div class="search-section">
      <h2>🔍 Gebruikers Zoeken</h2>
      <div class="search-box">
        <input 
          type="text" 
          v-model="searchQuery" 
          @input="filterUsers"
          placeholder="Zoek op gebruikersnaam of weergavenaam..."
          class="search-input"
        />
      </div>
      
      <div v-if="filteredUsers.length > 0" class="users-list">
        <div v-for="user in filteredUsers" :key="user.id" class="user-card">
          <div class="user-info">
            <div class="user-avatar">{{ user.display_name.charAt(0).toUpperCase() }}</div>
            <div>
              <div class="user-name">{{ user.display_name || user.username }}</div>
              <div class="user-meta">@{{ user.username }}</div>
            </div>
          </div>
          <div class="user-actions">
            <button 
              v-if="!isFriend(user.id) && !hasPendingRequest(user.id)"
              @click="sendRequest(user.id)" 
              class="btn btn-primary btn-sm"
            >
              ➕ Vriend toevoegen
            </button>
            <span v-else-if="isFriend(user.id)" class="status-badge friends">
              ✓ Vrienden
            </span>
            <span v-else-if="hasPendingRequest(user.id)" class="status-badge pending">
              Verzoek verzonden
            </span>
          </div>
        </div>
      </div>
      <div v-else-if="searchQuery" class="no-results">
        Geen gebruikers gevonden
      </div>
    </div>

    <!-- Friends List -->
    <div class="friends-section">
      <h2>✅ Mijn Vrienden ({{ friends.length }})</h2>
      
      <div v-if="friends.length === 0" class="no-friends">
        <p>Je hebt nog geen vrienden toegevoegd.</p>
        <p>Zoek hierboven naar gebruikers om vrienden toe te voegen!</p>
      </div>
      
      <div v-else class="friends-list">
        <div v-for="friend in friends" :key="friend.id" class="friend-card">
          <div class="user-info">
            <div class="user-avatar">{{ friend.display_name.charAt(0).toUpperCase() }}</div>
            <div>
              <div class="user-name">{{ friend.display_name || friend.username }}</div>
              <div class="user-meta">@{{ friend.username }}</div>
              <div class="friends-since">Vrienden sinds {{ formatDate(friend.friends_since) }}</div>
            </div>
          </div>
          <div class="friend-actions">
            <router-link :to="`/chat?friendId=${friend.id}`" class="btn btn-success btn-sm">
              💬 Chat
            </router-link>
            <router-link :to="`/user/${friend.id}`" class="btn btn-primary btn-sm">
              👁️ Collectie
            </router-link>
            <button @click="removeFriendConfirm(friend)" class="btn btn-danger btn-sm">
              🗑️ Verwijderen
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../services/api'

export default {
  name: 'Friends',
  data() {
    return {
      friends: [],
      pendingRequests: [],
      sentRequests: [],
      allUsers: [],
      filteredUsers: [],
      searchQuery: '',
      loading: false
    }
  },
  mounted() {
    this.loadData()
  },
  methods: {
    async loadData() {
      this.loading = true
      try {
        const [friendsRes, requestsRes, sentRes, usersRes] = await Promise.all([
          api.getFriends(),
          api.getPendingRequests(),
          api.getSentRequests(),
          api.getUsers()
        ])
        
        this.friends = friendsRes.data.data || []
        this.pendingRequests = requestsRes.data.data || []
        this.sentRequests = sentRes.data.data || []
        this.allUsers = usersRes.data.data || []
        
        console.log('Pending requests:', this.pendingRequests)
        console.log('Sent requests:', this.sentRequests)
        console.log('Friends:', this.friends)
        
        // Remove current user and existing friends from users list
        const currentUserResponse = await api.getCurrentUser()
        const currentUserId = currentUserResponse.data.user.id
        const friendIds = new Set(this.friends.map(f => f.id))
        const requestIds = new Set([
          ...this.pendingRequests.map(r => r.id),
          ...this.sentRequests.map(r => r.id)
        ])
        
        this.allUsers = this.allUsers.filter(u => 
          u.id !== currentUserId && !friendIds.has(u.id)
        )
        
      } catch (error) {
        console.error('Error loading friends data:', error)
        alert('Kon vriendenlijst niet laden')
      } finally {
        this.loading = false
      }
    },
    
    filterUsers() {
      if (!this.searchQuery.trim()) {
        this.filteredUsers = []
        return
      }
      
      const query = this.searchQuery.toLowerCase()
      this.filteredUsers = this.allUsers.filter(user => 
        user.username.toLowerCase().includes(query) ||
        user.display_name.toLowerCase().includes(query)
      )
    },
    
    async sendRequest(friendId) {
      try {
        await api.sendFriendRequest(friendId)
        alert('Vriendschapsverzoek verzonden!')
        this.loadData()
      } catch (error) {
        console.error('Error sending friend request:', error)
        alert(error.response?.data?.message || 'Kon vriendschapsverzoek niet verzenden')
      }
    },
    
    async acceptRequest(friendId) {
      try {
        await api.acceptFriendRequest(friendId)
        alert('Vriendschapsverzoek geaccepteerd!')
        this.loadData()
      } catch (error) {
        console.error('Error accepting request:', error)
        alert('Kon verzoek niet accepteren')
      }
    },
    
    async declineRequest(friendId) {
      if (!confirm('Weet je zeker dat je dit vriendschapsverzoek wilt afwijzen?')) {
        return
      }
      
      try {
        await api.declineFriendRequest(friendId)
        alert('Vriendschapsverzoek afgewezen')
        this.loadData()
      } catch (error) {
        console.error('Error declining request:', error)
        alert('Kon verzoek niet afwijzen')
      }
    },
    
    async removeFriendConfirm(friend) {
      if (!confirm(`Weet je zeker dat je ${friend.display_name || friend.username} als vriend wilt verwijderen?`)) {
        return
      }
      
      try {
        await api.removeFriend(friend.id)
        alert('Vriend verwijderd')
        this.loadData()
      } catch (error) {
        console.error('Error removing friend:', error)
        alert('Kon vriend niet verwijderen')
      }
    },
    
    isFriend(userId) {
      return this.friends.some(f => f.id === userId)
    },
    
    hasPendingRequest(userId) {
      return this.sentRequests.some(r => r.id === userId) ||
             this.pendingRequests.some(r => r.id === userId)
    },
    
    formatDate(dateString) {
      const date = new Date(dateString)
      const now = new Date()
      const diffMs = now - date
      const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
      
      if (diffDays === 0) return 'Vandaag'
      if (diffDays === 1) return 'Gisteren'
      if (diffDays < 7) return `${diffDays} dagen geleden`
      if (diffDays < 30) return `${Math.floor(diffDays / 7)} weken geleden`
      if (diffDays < 365) return `${Math.floor(diffDays / 30)} maanden geleden`
      return date.toLocaleDateString('nl-NL', { year: 'numeric', month: 'long', day: 'numeric' })
    }
  }
}
</script>

<style scoped>
.friends-container {
  max-width: 900px;
  margin: 0 auto;
  padding: 20px;
}

h1 {
  color: #fff;
  margin-bottom: 30px;
  text-align: center;
}

h2 {
  color: #fff;
  font-size: 1.5rem;
  margin-bottom: 15px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.requests-section,
.search-section,
.friends-section {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border-radius: 15px;
  padding: 25px;
  margin-bottom: 25px;
}

.search-box {
  margin-bottom: 20px;
}

.search-input {
  width: 100%;
  padding: 12px 20px;
  font-size: 1rem;
  border: 2px solid rgba(255, 255, 255, 0.2);
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  transition: all 0.3s ease;
}

.search-input:focus {
  outline: none;
  border-color: #6366f1;
  background: rgba(255, 255, 255, 0.15);
}

.search-input::placeholder {
  color: rgba(255, 255, 255, 0.5);
}

.requests-list,
.users-list,
.friends-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.request-card,
.user-card,
.friend-card {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 15px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: all 0.3s ease;
}

.request-card:hover,
.user-card:hover,
.friend-card:hover {
  background: rgba(255, 255, 255, 0.12);
  border-color: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
}

.user-info {
  display: flex;
  align-items: center;
  gap: 15px;
}

.user-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: bold;
  color: #fff;
}

.user-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: #fff;
  margin-bottom: 4px;
}

.user-meta {
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 2px;
}

.request-date,
.friends-since {
  font-size: 0.85rem;
  color: rgba(255, 255, 255, 0.5);
}

.request-actions,
.user-actions,
.friend-actions {
  display: flex;
  gap: 10px;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
}

.status-badge.friends {
  background: rgba(34, 197, 94, 0.2);
  color: #22c55e;
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-badge.pending {
  background: rgba(234, 179, 8, 0.2);
  color: #eab308;
  border: 1px solid rgba(234, 179, 8, 0.3);
}

.no-friends,
.no-results {
  text-align: center;
  padding: 40px 20px;
  color: rgba(255, 255, 255, 0.6);
}

.no-friends p {
  margin: 10px 0;
  font-size: 1.1rem;
}

.btn {
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
  text-decoration: none;
  display: inline-block;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 0.9rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-success {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  color: white;
}

.btn-success:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(34, 197, 94, 0.4);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.15);
}

.btn-danger {
  background: rgba(239, 68, 68, 0.2);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-danger:hover {
  background: rgba(239, 68, 68, 0.3);
}

@media (max-width: 768px) {
  .friends-container {
    padding: 15px;
  }
  
  .request-card,
  .user-card,
  .friend-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }
  
  .request-actions,
  .user-actions,
  .friend-actions {
    width: 100%;
    justify-content: flex-end;
  }
}
</style>
