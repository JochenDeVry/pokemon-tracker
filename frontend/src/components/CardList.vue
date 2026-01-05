<template>
  <div class="card-list">
    <!-- Achievements Section -->
    <Achievements 
      v-if="viewingUser && viewingUser.achievements" 
      :achievements="viewingUser.achievements"
      :stats="viewingUser.stats || {}"
    />
    
    <div class="list-header">
      <div class="header-title">
        <h2 v-if="isOwnCollection">Mijn Pokemon Kaarten Collectie</h2>
        <h2 v-else-if="viewingUser">
          Collectie van {{ viewingUser.display_name || viewingUser.username }}
          <span class="user-badge">@{{ viewingUser.username }}</span>
        </h2>
        <router-link v-if="!isOwnCollection" to="/" class="btn btn-secondary btn-sm back-btn">
          ← Terug naar mijn collectie
        </router-link>
      </div>
      
      <div class="search-box">
        <input 
          type="text" 
          v-model="searchQuery" 
          @input="applyFilters"
          placeholder="Zoek op naam, serienummer, set, rarity..."
          class="search-input"
        />
      </div>

      <div class="filters-section">
        <div class="filter-group">
          <label>Sorteer op:</label>
          <select v-model="sortBy" @change="applyFilters" class="filter-select">
            <option value="created_at_desc">Nieuwste eerst</option>
            <option value="created_at_asc">Oudste eerst</option>
            <option value="name_asc">Naam (A-Z)</option>
            <option value="name_desc">Naam (Z-A)</option>
            <option value="set_asc">Set (A-Z)</option>
            <option value="set_desc">Set (Z-A)</option>
            <option value="price_desc">Prijs (Hoog-Laag)</option>
            <option value="price_asc">Prijs (Laag-Hoog)</option>
            <option value="rarity_asc">Rarity (A-Z)</option>
            <option value="quantity_desc">Aantal (Hoog-Laag)</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Set:</label>
          <select v-model="filterSet" @change="applyFilters" class="filter-select">
            <option value="">Alle sets</option>
            <option v-for="set in availableSets" :key="set" :value="set">{{ set }}</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Rarity:</label>
          <select v-model="filterRarity" @change="applyFilters" class="filter-select">
            <option value="">Alle rarities</option>
            <option v-for="rarity in availableRarities" :key="rarity" :value="rarity">{{ rarity }}</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Conditie:</label>
          <select v-model="filterCondition" @change="applyFilters" class="filter-select">
            <option value="">Alle condities</option>
            <option v-for="condition in availableConditions" :key="condition" :value="condition">{{ condition }}</option>
          </select>
        </div>

        <button @click="resetFilters" class="btn btn-secondary btn-sm">
          Reset Filters
        </button>
      </div>

      <div class="results-info">
        Toon {{ paginatedCards.length }} van {{ filteredCards.length }} kaarten
        <span v-if="filteredCards.length !== allCards.length">(gefilterd uit {{ allCards.length }} totaal)</span>
      </div>
    </div>

    <div v-if="loading" class="loading">
      Kaarten laden...
    </div>

    <div v-else-if="error" class="alert alert-error">
      {{ error }}
    </div>

    <div v-else-if="allCards.length === 0" class="alert alert-info">
      Nog geen kaarten in je collectie. Voeg je eerste kaart toe!
    </div>

    <div v-else-if="filteredCards.length === 0" class="alert alert-info">
      Geen kaarten gevonden met deze filters. Probeer andere criteria.
    </div>

    <div v-else class="cards-grid">
      <div 
        v-for="card in paginatedCards" 
        :key="card.id" 
        class="card-item"
        :class="{ 'has-link': card.cardmarket_url }"
        @click="openCardmarket(card, $event)"
      >
        <div class="card-image" v-if="card.image_url">
          <img 
            :src="getProxiedImageUrl(card.image_url)" 
            :alt="card.card_name"
            @error="handleImageError"
          />
        </div>
        <div class="card-image-placeholder" v-else>
          <span>🎴</span>
        </div>
        
        <div class="card-content">
          <h3>{{ card.card_name }}</h3>
          <p class="card-serial">{{ card.serial_number }}</p>
          
          <div class="card-details">
            <span v-if="card.set_name" class="badge">{{ card.set_name }}</span>
            <span v-if="card.card_number" class="badge">{{ card.card_number }}</span>
            <span v-if="card.rarity" class="badge badge-rarity">{{ card.rarity }}</span>
          </div>

          <div class="card-info">
            <div v-if="card.quantity" class="info-item">
              <strong>Aantal:</strong> {{ card.quantity }}
            </div>
            <div v-if="card.condition_card" class="info-item">
              <strong>Conditie:</strong> {{ card.condition_card }}
            </div>
          </div>

          <div class="card-prices">
            <div v-if="card.purchase_price" class="price-item">
              <span class="price-label">Aankoop:</span>
              <span class="price-value">€{{ parseFloat(card.purchase_price).toFixed(2) }}</span>
            </div>
            <div v-if="card.current_price" class="price-item current">
              <span class="price-label">Huidige prijs:</span>
              <span class="price-value">€{{ parseFloat(card.current_price).toFixed(2) }}</span>
            </div>
            <div v-if="card.last_price_update" class="price-update">
              Laatste update: {{ formatDate(card.last_price_update) }}
            </div>
          </div>

          <div class="card-actions" v-if="isOwnCollection">
            <button @click="updatePrice(card)" class="btn btn-success btn-sm" :disabled="!card.cardmarket_url || updatingPrice === card.id">
              {{ updatingPrice === card.id ? '...' : 'Prijs Updaten' }}
            </button>
            <router-link :to="`/edit/${card.id}`" class="btn btn-secondary btn-sm">
              Bewerken
            </router-link>
            <button @click="deleteCard(card)" class="btn btn-danger btn-sm">
              Verwijderen
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="pagination-section" v-if="filteredCards.length > 0">
      <div class="pagination-controls">
        <div class="per-page">
          <label>Kaarten per pagina:</label>
          <select v-model.number="itemsPerPage" @change="currentPage = 1" class="filter-select">
            <option :value="12">12</option>
            <option :value="24">24</option>
            <option :value="48">48</option>
            <option :value="96">96</option>
            <option :value="filteredCards.length">Alles ({{ filteredCards.length }})</option>
          </select>
        </div>

        <div class="page-buttons">
          <button 
            @click="currentPage = 1" 
            :disabled="currentPage === 1"
            class="btn btn-secondary btn-sm"
          >
            Eerste
          </button>
          <button 
            @click="currentPage--" 
            :disabled="currentPage === 1"
            class="btn btn-secondary btn-sm"
          >
            Vorige
          </button>
          
          <span class="page-info">
            Pagina {{ currentPage }} van {{ totalPages }}
          </span>
          
          <button 
            @click="currentPage++" 
            :disabled="currentPage === totalPages"
            class="btn btn-secondary btn-sm"
          >
            Volgende
          </button>
          <button 
            @click="currentPage = totalPages" 
            :disabled="currentPage === totalPages"
            class="btn btn-secondary btn-sm"
          >
            Laatste
          </button>
        </div>

        <div class="page-jump">
          <label>Ga naar pagina:</label>
          <input 
            type="number" 
            v-model.number="pageJump" 
            @keyup.enter="jumpToPage"
            :min="1" 
            :max="totalPages"
            class="page-input"
          />
          <button @click="jumpToPage" class="btn btn-secondary btn-sm">Go</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../services/api.js'
import Achievements from './Achievements.vue'

export default {
  name: 'CardList',
  components: {
    Achievements
  },
  props: ['userId'],
  data() {
    return {
      allCards: [],
      filteredCards: [],
      paginatedCards: [],
      searchQuery: '',
      loading: false,
      error: null,
      updatingPrice: null,
      viewingUser: null,
      isOwnCollection: true,
      
      sortBy: 'created_at_desc',
      filterSet: '',
      filterRarity: '',
      filterCondition: '',
      
      currentPage: 1,
      itemsPerPage: 24,
      pageJump: 1
    }
  },
  computed: {
    totalQuantity() {
      return this.allCards.reduce((sum, card) => sum + parseInt(card.quantity || 0), 0)
    },
    totalValue() {
      return this.allCards.reduce((sum, card) => {
        const price = parseFloat(card.current_price || card.purchase_price || 0)
        const quantity = parseInt(card.quantity || 1)
        return sum + (price * quantity)
      }, 0)
    },
    uniqueSets() {
      return new Set(this.allCards.filter(c => c.set_name).map(c => c.set_name)).size
    },
    availableSets() {
      return [...new Set(this.allCards.filter(c => c.set_name).map(c => c.set_name))].sort()
    },
    availableRarities() {
      return [...new Set(this.allCards.filter(c => c.rarity).map(c => c.rarity))].sort()
    },
    availableConditions() {
      return [...new Set(this.allCards.filter(c => c.condition_card).map(c => c.condition_card))].sort()
    },
    totalPages() {
      return Math.ceil(this.filteredCards.length / this.itemsPerPage)
    }
  },
  mounted() {
    this.loadCards()
  },
  watch: {
    userId() {
      this.loadCards()
    }
  },
  methods: {
    getProxiedImageUrl(imageUrl) {
      if (!imageUrl) return ''
      if (imageUrl.includes('cardmarket.com') || imageUrl.includes('s3.cardmarket')) {
        return `/api/proxy-image?url=${encodeURIComponent(imageUrl)}`
      }
      return imageUrl
    },
    async loadCards() {
      this.loading = true
      this.error = null
      
      try {
        // If userId prop is provided, load that user's cards
        const targetUserId = this.userId
        
        if (targetUserId) {
          // Load user profile first
          const userResponse = await api.getUserProfile(targetUserId)
          if (userResponse.data.success) {
            this.viewingUser = userResponse.data.data
            this.isOwnCollection = false
          }
        } else {
          // Load current user's profile for achievements
          const currentUserResponse = await api.getCurrentUser()
          if (currentUserResponse.data.success) {
            const currentUserId = currentUserResponse.data.user.id
            const userResponse = await api.getUserProfile(currentUserId)
            if (userResponse.data.success) {
              this.viewingUser = userResponse.data.data
              this.isOwnCollection = true
            }
          }
        }
        
        const response = await api.getCards(targetUserId)
        if (response.data.success) {
          this.allCards = response.data.data
          this.applyFilters()
        }
      } catch (err) {
        if (err.response?.status === 403) {
          this.error = 'Deze collectie is privé en niet beschikbaar'
        } else if (err.response?.status === 401) {
          this.error = 'Je moet ingelogd zijn om dit te bekijken'
          this.$router.push('/login')
        } else {
          this.error = 'Kon kaarten niet laden: ' + (err.response?.data?.message || err.message)
        }
      } finally {
        this.loading = false
      }
    },
    applyFilters() {
      let filtered = [...this.allCards]

      if (this.searchQuery.trim()) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(card => 
          (card.card_name && card.card_name.toLowerCase().includes(query)) ||
          (card.serial_number && card.serial_number.toLowerCase().includes(query)) ||
          (card.set_name && card.set_name.toLowerCase().includes(query)) ||
          (card.card_number && card.card_number.toLowerCase().includes(query)) ||
          (card.rarity && card.rarity.toLowerCase().includes(query)) ||
          (card.notes && card.notes.toLowerCase().includes(query))
        )
      }

      if (this.filterSet) {
        filtered = filtered.filter(card => card.set_name === this.filterSet)
      }

      if (this.filterRarity) {
        filtered = filtered.filter(card => card.rarity === this.filterRarity)
      }

      if (this.filterCondition) {
        filtered = filtered.filter(card => card.condition_card === this.filterCondition)
      }

      filtered = this.sortCards(filtered)

      this.filteredCards = filtered
      this.currentPage = 1
      this.updatePagination()
    },
    sortCards(cards) {
      const sorted = [...cards]
      
      switch(this.sortBy) {
        case 'name_asc':
          return sorted.sort((a, b) => (a.card_name || '').localeCompare(b.card_name || ''))
        case 'name_desc':
          return sorted.sort((a, b) => (b.card_name || '').localeCompare(a.card_name || ''))
        case 'set_asc':
          return sorted.sort((a, b) => (a.set_name || '').localeCompare(b.set_name || ''))
        case 'set_desc':
          return sorted.sort((a, b) => (b.set_name || '').localeCompare(a.set_name || ''))
        case 'price_asc':
          return sorted.sort((a, b) => {
            const priceA = parseFloat(a.current_price || a.purchase_price || 0)
            const priceB = parseFloat(b.current_price || b.purchase_price || 0)
            return priceA - priceB
          })
        case 'price_desc':
          return sorted.sort((a, b) => {
            const priceA = parseFloat(a.current_price || a.purchase_price || 0)
            const priceB = parseFloat(b.current_price || b.purchase_price || 0)
            return priceB - priceA
          })
        case 'rarity_asc':
          return sorted.sort((a, b) => (a.rarity || '').localeCompare(b.rarity || ''))
        case 'quantity_desc':
          return sorted.sort((a, b) => parseInt(b.quantity || 0) - parseInt(a.quantity || 0))
        case 'created_at_asc':
          return sorted.sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
        case 'created_at_desc':
        default:
          return sorted.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
      }
    },
    updatePagination() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      this.paginatedCards = this.filteredCards.slice(start, end)
    },
    resetFilters() {
      this.searchQuery = ''
      this.filterSet = ''
      this.filterRarity = ''
      this.filterCondition = ''
      this.sortBy = 'created_at_desc'
      this.applyFilters()
    },
    jumpToPage() {
      if (this.pageJump >= 1 && this.pageJump <= this.totalPages) {
        this.currentPage = this.pageJump
        this.updatePagination()
      }
    },
    async updatePrice(card) {
      if (!card.cardmarket_url) {
        alert('Geen Cardmarket URL beschikbaar voor deze kaart')
        return
      }

      this.updatingPrice = card.id

      try {
        const response = await api.updatePrice(card.id)
        if (response.data.success) {
          const index = this.allCards.findIndex(c => c.id === card.id)
          if (index !== -1) {
            this.allCards[index].current_price = response.data.price
            this.allCards[index].last_price_update = new Date().toISOString()
          }
          this.applyFilters()
          alert(`Prijs bijgewerkt: €${response.data.price.toFixed(2)}`)
        }
      } catch (err) {
        alert('Kon prijs niet updaten: ' + (err.response?.data?.message || err.message))
      } finally {
        this.updatingPrice = null
      }
    },
    async deleteCard(card) {
      if (!confirm(`Weet je zeker dat je "${card.card_name}" wilt verwijderen?`)) {
        return
      }

      try {
        const response = await api.deleteCard(card.id)
        if (response.data.success) {
          this.allCards = this.allCards.filter(c => c.id !== card.id)
          this.applyFilters()
        }
      } catch (err) {
        alert('Kon kaart niet verwijderen: ' + (err.response?.data?.message || err.message))
      }
    },
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleString('nl-NL', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    handleImageError(event) {
      const img = event.target
      const parent = img.parentElement
      if (parent) {
        parent.style.display = 'none'
        const placeholder = parent.nextElementSibling
        if (placeholder && placeholder.classList.contains('card-image-placeholder')) {
          placeholder.style.display = 'flex'
        }
      }
    },
    openCardmarket(card, event) {
      if (!card.cardmarket_url) {
        return
      }
      
      if (event.target.closest('button') || event.target.closest('a')) {
        return
      }
      
      window.open(card.cardmarket_url, '_blank')
    }
  },
  watch: {
    currentPage() {
      this.updatePagination()
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }
  }
}
</script>

<style scoped>
.card-list {
  max-width: 1400px;
  margin: 0 auto;
}

.list-header {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.header-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.header-title h2 {
  margin: 0;
  color: #667eea;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.user-badge {
  font-size: 0.8rem;
  background: #e3f2fd;
  color: #1976d2;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-weight: 500;
}

.back-btn {
  margin-left: auto;
}

.list-header h2 {
  margin-bottom: 1rem;
  color: #667eea;
}

.search-box {
  margin-top: 1rem;
}

.search-input {
  width: 100%;
  padding: 1rem;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.3s;
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
}

.filters-section {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: flex-end;
  margin-top: 1rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex: 1;
  min-width: 150px;
}

.filter-group label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #333;
}

.filter-select {
  padding: 0.5rem;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 0.9rem;
  background: white;
  cursor: pointer;
  transition: border-color 0.3s;
}

.filter-select:focus {
  outline: none;
  border-color: #667eea;
}

.results-info {
  margin-top: 1rem;
  padding: 0.75rem;
  background: #e3f2fd;
  border-radius: 8px;
  text-align: center;
  font-weight: 500;
  color: #1976d2;
}

.results-info span {
  color: #666;
  font-size: 0.9rem;
}

.pagination-section {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  margin: 2rem 0;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.pagination-controls {
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
  align-items: center;
  justify-content: space-between;
}

.per-page {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.per-page label {
  font-weight: 600;
  color: #333;
}

.page-buttons {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.page-info {
  padding: 0 1rem;
  font-weight: 600;
  color: #667eea;
}

.page-jump {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.page-jump label {
  font-weight: 600;
  color: #333;
}

.page-input {
  width: 80px;
  padding: 0.5rem;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  text-align: center;
  font-size: 0.9rem;
}

.page-input:focus {
  outline: none;
  border-color: #667eea;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.card-item {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s, box-shadow 0.3s;
}

.card-item.has-link {
  cursor: pointer;
}

.card-item:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

.card-item.has-link:hover {
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.card-image {
  width: 100%;
  height: 250px;
  overflow: hidden;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.card-image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  background: white;
}

.card-image-placeholder {
  width: 100%;
  height: 250px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 5rem;
}

.card-content {
  padding: 1.5rem;
}

.card-content h3 {
  margin-bottom: 0.5rem;
  color: #333;
  font-size: 1.3rem;
}

.card-serial {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  font-family: monospace;
}

.card-details {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.badge {
  background: #e0e0e0;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.85rem;
  color: #333;
}

.badge-rarity {
  background: #ffd700;
  color: #333;
  font-weight: 600;
}

.card-info {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
  margin-bottom: 1rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.info-item {
  font-size: 0.9rem;
}

.card-prices {
  background: #f0f8ff;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.price-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.price-item.current {
  font-weight: 600;
  color: #28a745;
  font-size: 1.1rem;
}

.price-label {
  color: #666;
}

.price-value {
  font-weight: 600;
}

.price-update {
  font-size: 0.8rem;
  color: #666;
  margin-top: 0.5rem;
  font-style: italic;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.9rem;
  flex: 1;
  min-width: 100px;
}

@media (max-width: 768px) {
  .cards-grid {
    grid-template-columns: 1fr;
  }

  .filters-section {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-group {
    min-width: 100%;
  }

  .pagination-controls {
    flex-direction: column;
    gap: 1rem;
  }

  .page-buttons {
    flex-wrap: wrap;
    justify-content: center;
  }

  .card-actions {
    flex-direction: column;
  }

  .btn-sm {
    width: 100%;
  }
}
</style>
