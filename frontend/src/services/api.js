import axios from 'axios'

const API_URL = '/api'

// Configure axios to include credentials (cookies)
axios.defaults.withCredentials = true

export default {
  // Auth
  register(username, email, password, displayName) {
    return axios.post(`${API_URL}/auth/register`, {
      username,
      email,
      password,
      display_name: displayName
    })
  },

  login(email, password) {
    return axios.post(`${API_URL}/auth/login`, { email, password })
  },

  logout() {
    return axios.post(`${API_URL}/auth/logout`)
  },

  getCurrentUser() {
    return axios.get(`${API_URL}/auth/user`)
  },

  // Users
  getUsers() {
    return axios.get(`${API_URL}/users`)
  },

  getUserProfile(userId) {
    return axios.get(`${API_URL}/users/${userId}`)
  },

  // Cards
  getCards(userId = null) {
    const params = userId ? { user_id: userId } : {}
    return axios.get(`${API_URL}/cards`, { params })
  },

  getCard(id) {
    return axios.get(`${API_URL}/cards/${id}`)
  },

  getCardBySerial(serial) {
    return axios.get(`${API_URL}/cards/serial?serial=${serial}`)
  },

  searchCards(query) {
    return axios.get(`${API_URL}/cards/search?q=${query}`)
  },

  createCard(cardData) {
    return axios.post(`${API_URL}/cards`, cardData)
  },

  updateCard(id, cardData) {
    return axios.put(`${API_URL}/cards/${id}`, cardData)
  },

  deleteCard(id) {
    return axios.delete(`${API_URL}/cards/${id}`)
  },

  updatePrice(id) {
    return axios.get(`${API_URL}/cards/price/${id}`)
  },

  scrapeCardmarket(url) {
    return axios.post(`${API_URL}/scrape`, { url })
  },

  // Friends
  getFriends() {
    return axios.get(`${API_URL}/friends`)
  },

  getPendingRequests() {
    return axios.get(`${API_URL}/friends/requests`)
  },

  getSentRequests() {
    return axios.get(`${API_URL}/friends/sent`)
  },

  getFriendshipStatus(userId) {
    return axios.get(`${API_URL}/friends/status/${userId}`)
  },

  sendFriendRequest(friendId) {
    return axios.post(`${API_URL}/friends/request`, { friend_id: friendId })
  },

  acceptFriendRequest(friendId) {
    return axios.post(`${API_URL}/friends/accept`, { friend_id: friendId })
  },

  declineFriendRequest(friendId) {
    return axios.post(`${API_URL}/friends/decline`, { friend_id: friendId })
  },

  removeFriend(friendId) {
    return axios.post(`${API_URL}/friends/remove`, { friend_id: friendId })
  },

  // Profile
  updateProfile(data) {
    return axios.put(`${API_URL}/profile`, data)
  },

  changePassword(data) {
    return axios.post(`${API_URL}/profile/password`, data)
  },

  // Leaderboard
  getFriendsLeaderboard() {
    return axios.get(`${API_URL}/leaderboard/friends`)
  },

  getGlobalLeaderboard() {
    return axios.get(`${API_URL}/leaderboard/global`)
  },

  // Messages
  getConversations() {
    return axios.get(`${API_URL}/messages`)
  },

  getConversation(friendId) {
    return axios.get(`${API_URL}/messages/conversation/${friendId}`)
  },

  sendMessage(receiverId, message) {
    return axios.post(`${API_URL}/messages`, { receiver_id: receiverId, message })
  },

  getUnreadCount() {
    return axios.get(`${API_URL}/messages/unread`)
  },

  pollMessages(friendId, since) {
    return axios.get(`${API_URL}/messages/poll/${friendId}?since=${since}`)
  }
}
