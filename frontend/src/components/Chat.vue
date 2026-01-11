<template>
  <div class="chat-container">
    <div class="chat-sidebar">
      <h2>💬 Berichten</h2>
      
      <div v-if="loadingConversations" class="loading">Laden...</div>
      
      <div v-else-if="conversations.length === 0" class="no-conversations">
        <p>Nog geen gesprekken.</p>
        <router-link to="/friends">Ga naar vrienden</router-link>
      </div>
      
      <div v-else class="conversations-list">
        <div
          v-for="conv in conversations"
          :key="conv.friend_id"
          :class="['conversation-item', { active: selectedFriend && selectedFriend.id === conv.friend_id }]"
          @click="selectConversation(conv)"
        >
          <div class="avatar">{{ (conv.friend_display_name || conv.friend_username).charAt(0).toUpperCase() }}</div>
          <div class="conversation-info">
            <div class="friend-name">{{ conv.friend_display_name || conv.friend_username }}</div>
            <div class="last-message">{{ truncate(conv.last_message, 30) }}</div>
          </div>
          <div v-if="conv.unread_count > 0" class="unread-badge">{{ conv.unread_count }}</div>
        </div>
      </div>
    </div>

    <div class="chat-main">
      <div v-if="!selectedFriend" class="no-selection">
        <p>👈 Selecteer een gesprek om te beginnen</p>
      </div>

      <div v-else class="chat-window">
        <div class="chat-header">
          <div class="friend-info">
            <div class="avatar">{{ (selectedFriend.display_name || selectedFriend.username).charAt(0).toUpperCase() }}</div>
            <div>
              <div class="friend-name">{{ selectedFriend.display_name || selectedFriend.username }}</div>
              <div class="friend-username">@{{ selectedFriend.username }}</div>
            </div>
          </div>
          <router-link :to="`/user/${selectedFriend.id}`" class="view-profile-btn">
            👁️ Collectie
          </router-link>
        </div>

        <div class="messages-container" ref="messagesContainer">
          <div v-if="loadingMessages" class="loading">Berichten laden...</div>
          
          <div v-else class="messages-list">
            <div
              v-for="msg in messages"
              :key="msg.id"
              :class="['message', msg.sender_id === currentUserId ? 'sent' : 'received']"
            >
              <div class="message-content">{{ msg.message }}</div>
              <div class="message-time">{{ formatTime(msg.created_at) }}</div>
            </div>
          </div>
        </div>

        <div class="message-input-container">
          <form @submit.prevent="sendMessage">
            <input
              v-model="newMessage"
              type="text"
              placeholder="Type een bericht..."
              :disabled="sending"
            />
            <button type="submit" :disabled="!newMessage.trim() || sending" class="send-btn">
              {{ sending ? '⏳' : '📤' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../services/api'

export default {
  name: 'Chat',
  data() {
    return {
      conversations: [],
      selectedFriend: null,
      messages: [],
      newMessage: '',
      currentUserId: null,
      loadingConversations: true,
      loadingMessages: false,
      sending: false,
      pollInterval: null
    }
  },

  async mounted() {
    await this.loadCurrentUser()
    await this.loadConversations()
    
    // Auto-select if friendId in route
    if (this.$route.query.friendId) {
      const friendId = parseInt(this.$route.query.friendId)
      const conv = this.conversations.find(c => c.friend_id === friendId)
      if (conv) {
        await this.selectConversation(conv)
      }
    }
  },

  beforeUnmount() {
    this.stopPolling()
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

    async loadConversations() {
      this.loadingConversations = true
      try {
        const response = await api.getConversations()
        this.conversations = response.data.data || []
      } catch (error) {
        console.error('Error loading conversations:', error)
      } finally {
        this.loadingConversations = false
      }
    },

    async selectConversation(conv) {
      this.selectedFriend = {
        id: conv.friend_id,
        username: conv.friend_username,
        display_name: conv.friend_display_name
      }
      
      await this.loadMessages()
      this.startPolling()
      
      // Mark as read
      const convIndex = this.conversations.findIndex(c => c.friend_id === conv.friend_id)
      if (convIndex !== -1) {
        this.conversations[convIndex].unread_count = 0
      }
    },

    async loadMessages() {
      if (!this.selectedFriend) return
      
      this.loadingMessages = true
      try {
        const response = await api.getConversation(this.selectedFriend.id)
        this.messages = response.data.data || []
        this.$nextTick(() => {
          this.scrollToBottom()
        })
      } catch (error) {
        console.error('Error loading messages:', error)
      } finally {
        this.loadingMessages = false
      }
    },

    async sendMessage() {
      if (!this.newMessage.trim() || !this.selectedFriend) return
      
      this.sending = true
      try {
        await api.sendMessage(this.selectedFriend.id, this.newMessage)
        this.newMessage = ''
        await this.loadMessages()
        await this.loadConversations()
      } catch (error) {
        console.error('Error sending message:', error)
        alert('Kon bericht niet verzenden')
      } finally {
        this.sending = false
      }
    },

    startPolling() {
      this.stopPolling()
      this.pollInterval = setInterval(async () => {
        if (!this.selectedFriend || this.messages.length === 0) return
        
        const lastMessage = this.messages[this.messages.length - 1]
        try {
          const response = await api.pollMessages(this.selectedFriend.id, lastMessage.created_at)
          const newMessages = response.data.data || []
          if (newMessages.length > 0) {
            this.messages.push(...newMessages)
            this.$nextTick(() => {
              this.scrollToBottom()
            })
            await this.loadConversations()
          }
        } catch (error) {
          console.error('Error polling messages:', error)
        }
      }, 3000) // Poll every 3 seconds
    },

    stopPolling() {
      if (this.pollInterval) {
        clearInterval(this.pollInterval)
        this.pollInterval = null
      }
    },

    scrollToBottom() {
      const container = this.$refs.messagesContainer
      if (container) {
        container.scrollTop = container.scrollHeight
      }
    },

    formatTime(timestamp) {
      const date = new Date(timestamp)
      const now = new Date()
      const diff = now - date
      
      if (diff < 60000) return 'Nu'
      if (diff < 3600000) return `${Math.floor(diff / 60000)}m geleden`
      if (diff < 86400000) return date.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' })
      return date.toLocaleDateString('nl-NL', { day: 'numeric', month: 'short' })
    },

    truncate(text, length) {
      if (!text) return ''
      return text.length > length ? text.substring(0, length) + '...' : text
    }
  }
}
</script>

<style scoped>
.chat-container {
  display: flex;
  height: calc(100vh - 200px);
  max-height: 700px;
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.chat-sidebar {
  width: 320px;
  border-right: 2px solid #e0e0e0;
  display: flex;
  flex-direction: column;
}

.chat-sidebar h2 {
  padding: 20px;
  margin: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 1.3rem;
}

.conversations-list {
  flex: 1;
  overflow-y: auto;
}

.conversation-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 15px 20px;
  cursor: pointer;
  border-bottom: 1px solid #f0f0f0;
  transition: all 0.2s;
}

.conversation-item:hover {
  background: #f8f9fa;
}

.conversation-item.active {
  background: #e3f2fd;
  border-left: 4px solid #3498db;
}

.avatar {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  font-weight: 700;
  flex-shrink: 0;
}

.conversation-info {
  flex: 1;
  min-width: 0;
}

.friend-name {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 4px;
}

.last-message {
  font-size: 0.85rem;
  color: #666;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.unread-badge {
  background: #e74c3c;
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 700;
  min-width: 20px;
  text-align: center;
}

.chat-main {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.no-selection, .no-conversations {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #666;
  gap: 15px;
}

.chat-window {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.chat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  background: #f8f9fa;
  border-bottom: 2px solid #e0e0e0;
}

.friend-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.friend-username {
  font-size: 0.85rem;
  color: #666;
}

.view-profile-btn {
  padding: 8px 16px;
  background: #3498db;
  color: white;
  text-decoration: none;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.9rem;
  transition: all 0.3s;
}

.view-profile-btn:hover {
  background: #2980b9;
}

.messages-container {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  background: #fafafa;
}

.messages-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.message {
  display: flex;
  flex-direction: column;
  max-width: 70%;
  animation: fadeIn 0.3s;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.message.sent {
  align-self: flex-end;
}

.message.received {
  align-self: flex-start;
}

.message-content {
  padding: 12px 16px;
  border-radius: 18px;
  word-wrap: break-word;
}

.message.sent .message-content {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-bottom-right-radius: 4px;
}

.message.received .message-content {
  background: white;
  color: #2c3e50;
  border-bottom-left-radius: 4px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.message-time {
  font-size: 0.7rem;
  color: #999;
  margin-top: 4px;
  padding: 0 8px;
}

.message.sent .message-time {
  text-align: right;
}

.message-input-container {
  padding: 15px 20px;
  background: white;
  border-top: 2px solid #e0e0e0;
}

.message-input-container form {
  display: flex;
  gap: 10px;
}

.message-input-container input {
  flex: 1;
  padding: 12px 16px;
  border: 2px solid #e0e0e0;
  border-radius: 24px;
  font-size: 1rem;
  transition: border-color 0.3s;
}

.message-input-container input:focus {
  outline: none;
  border-color: #3498db;
}

.send-btn {
  padding: 12px 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 24px;
  cursor: pointer;
  font-size: 1.2rem;
  transition: all 0.3s;
}

.send-btn:hover:not(:disabled) {
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.send-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.loading {
  text-align: center;
  padding: 20px;
  color: #666;
}
</style>
