<template>
  <div class="card-form">
    <div class="form-container">
      <h2>{{ isEdit ? 'Kaart Bewerken' : 'Nieuwe Kaart Toevoegen' }}</h2>

      <div v-if="message.text" :class="['alert', message.type === 'error' ? 'alert-error' : 'alert-success']">
        {{ message.text }}
      </div>

      <!-- Cardmarket Scraper Section -->
      <div class="scraper-section" v-if="!isEdit">
        <h3>📋 Quick Fill Opties</h3>
        
        <div class="quick-options">
          <div class="option-card">
            <h4>Option 1: Cardmarket Import (Beta)</h4>
            <p class="help-text">
              Probeer automatisch gegevens op te halen. Werkt niet altijd door anti-scraping.
            </p>
            <div class="form-group">
              <input 
                type="url" 
                v-model="cardmarketUrl" 
                placeholder="https://www.cardmarket.com/en/Pokemon/Products/..."
                class="cardmarket-input"
              />
              <button @click="scrapeCardmarket" class="btn btn-primary" :disabled="scraping || !cardmarketUrl">
                {{ scraping ? 'Ophalen...' : '🔍 Gegevens Proberen' }}
              </button>
            </div>
          </div>
          
          <div class="option-card">
            <h4>Option 2: Handmatig Invoeren</h4>
            <p class="help-text">
              Vul onderstaand formulier handmatig in. Je kunt wel de Cardmarket URL meegeven voor toekomstige prijs updates.
            </p>
          </div>
        </div>
        
        <div class="divider">
          <span>Kaart Gegevens</span>
        </div>
      </div>

      <!-- Manual Form -->
      <form @submit.prevent="handleSubmit">
        <div class="form-row">
          <div class="form-group">
            <label>Serienummer *</label>
            <input 
              type="text" 
              v-model="formData.serial_number" 
              required 
              placeholder="bijv. SWSH01-001"
            />
          </div>

          <div class="form-group">
            <label>Kaartnaam *</label>
            <input 
              type="text" 
              v-model="formData.card_name" 
              required 
              placeholder="bijv. Pikachu"
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Set</label>
            <input 
              type="text" 
              v-model="formData.set_name" 
              placeholder="bijv. Sword & Shield Base Set"
            />
          </div>

          <div class="form-group">
            <label>Kaartnummer</label>
            <input 
              type="text" 
              v-model="formData.card_number" 
              placeholder="bijv. 001/202"
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Rarity</label>
            <select v-model="formData.rarity">
              <option value="">Selecteer rarity...</option>
              <option value="Common">Common</option>
              <option value="Uncommon">Uncommon</option>
              <option value="Rare">Rare</option>
              <option value="Holo Rare">Holo Rare</option>
              <option value="Ultra Rare">Ultra Rare</option>
              <option value="Secret Rare">Secret Rare</option>
              <option value="Promo">Promo</option>
            </select>
          </div>

          <div class="form-group">
            <label>Conditie</label>
            <select v-model="formData.condition_card">
              <option value="Mint">Mint</option>
              <option value="Near Mint">Near Mint</option>
              <option value="Excellent">Excellent</option>
              <option value="Good">Good</option>
              <option value="Light Played">Light Played</option>
              <option value="Played">Played</option>
              <option value="Poor">Poor</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Aantal</label>
            <input 
              type="number" 
              v-model.number="formData.quantity" 
              min="1"
            />
          </div>

          <div class="form-group">
            <label>Aankoopprijs (€)</label>
            <input 
              type="number" 
              v-model.number="formData.purchase_price" 
              step="0.01"
              placeholder="0.00"
            />
          </div>
        </div>

        <div class="form-group">
          <label>Cardmarket URL</label>
          <input 
            type="url" 
            v-model="formData.cardmarket_url" 
            placeholder="https://www.cardmarket.com/..."
          />
          <small class="help-text">Gebruikt om prijzen automatisch bij te werken</small>
        </div>

        <div class="form-group">
          <label>Afbeelding URL</label>
          <input 
            type="url" 
            v-model="formData.image_url" 
            placeholder="https://..."
          />
        </div>

        <div class="form-group">
          <label>Notities</label>
          <textarea 
            v-model="formData.notes" 
            placeholder="Extra informatie over deze kaart..."
          ></textarea>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary" :disabled="submitting">
            {{ submitting ? 'Opslaan...' : (isEdit ? '💾 Wijzigingen Opslaan' : '➕ Kaart Toevoegen') }}
          </button>
          <router-link to="/" class="btn btn-secondary">
            ❌ Annuleren
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import api from '../services/api.js'

export default {
  name: 'CardForm',
  props: ['id'],
  data() {
    return {
      formData: {
        serial_number: '',
        card_name: '',
        set_name: '',
        card_number: '',
        rarity: '',
        condition_card: 'Near Mint',
        quantity: 1,
        purchase_price: null,
        current_price: null,
        cardmarket_url: '',
        image_url: '',
        notes: ''
      },
      cardmarketUrl: '',
      scraping: false,
      submitting: false,
      message: {
        text: '',
        type: ''
      }
    }
  },
  computed: {
    isEdit() {
      return !!this.id
    }
  },
  mounted() {
    if (this.isEdit) {
      this.loadCard()
    }
  },
  methods: {
    async loadCard() {
      try {
        const response = await api.getCard(this.id)
        if (response.data.success) {
          this.formData = { ...response.data.data }
        }
      } catch (err) {
        this.showMessage('Kon kaart niet laden: ' + (err.response?.data?.message || err.message), 'error')
      }
    },
    async scrapeCardmarket() {
      if (!this.cardmarketUrl) {
        this.showMessage('Voer een Cardmarket URL in', 'error')
        return
      }

      this.scraping = true
      this.message = { text: '', type: '' }

      try {
        const response = await api.scrapeCardmarket(this.cardmarketUrl)
        if (response.data.success) {
          const scraped = response.data.data
          
          // Fill in the form with scraped data
          this.formData.card_name = scraped.card_name || this.formData.card_name
          this.formData.set_name = scraped.set_name || this.formData.set_name
          this.formData.card_number = scraped.card_number || this.formData.card_number
          this.formData.rarity = scraped.rarity || this.formData.rarity
          this.formData.current_price = scraped.current_price || this.formData.current_price
          this.formData.image_url = scraped.image_url || this.formData.image_url
          this.formData.cardmarket_url = scraped.cardmarket_url || this.cardmarketUrl

          this.showMessage('✅ Gegevens opgehaald! Controleer en vul aan waar nodig.', 'success')
        } else {
          this.showMessage('⚠️ Kon geen gegevens ophalen. Cardmarket URL is wel opgeslagen voor prijsupdates.', 'error')
          // Sla tenminste de URL op
          this.formData.cardmarket_url = this.cardmarketUrl
        }
      } catch (err) {
        const errorMsg = err.response?.data?.message || err.message
        this.showMessage('❌ ' + errorMsg, 'error')
        // Sla tenminste de URL op als die er is
        if (this.cardmarketUrl) {
          this.formData.cardmarket_url = this.cardmarketUrl
        }
      } finally {
        this.scraping = false
      }
    },
    async handleSubmit() {
      this.submitting = true
      this.message = { text: '', type: '' }

      try {
        let response
        if (this.isEdit) {
          response = await api.updateCard(this.id, this.formData)
        } else {
          response = await api.createCard(this.formData)
        }

        if (response.data.success) {
          this.showMessage(response.data.message || 'Kaart opgeslagen!', 'success')
          
          // Redirect to home after 1.5 seconds
          setTimeout(() => {
            this.$router.push('/')
          }, 1500)
        }
      } catch (err) {
        this.showMessage(err.response?.data?.message || err.message, 'error')
      } finally {
        this.submitting = false
      }
    },
    showMessage(text, type) {
      this.message = { text, type }
      
      // Auto-hide success messages after 5 seconds
      if (type === 'success') {
        setTimeout(() => {
          this.message = { text: '', type: '' }
        }, 5000)
      }
    }
  }
}
</script>

<style scoped>
.card-form {
  max-width: 800px;
  margin: 0 auto;
}

.form-container {
  background: white;
  padding: 2.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.form-container h2 {
  margin-bottom: 1.5rem;
  color: #667eea;
  font-size: 2rem;
}

.scraper-section {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 2rem;
}

.scraper-section h3 {
  margin-bottom: 1rem;
  color: #333;
  font-size: 1.3rem;
}

.quick-options {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.option-card {
  background: white;
  padding: 1rem;
  border-radius: 8px;
  border: 2px solid #e0e0e0;
}

.option-card h4 {
  margin-bottom: 0.5rem;
  color: #667eea;
  font-size: 1rem;
}

.help-text {
  color: #666;
  font-size: 0.85rem;
  margin-bottom: 1rem;
  line-height: 1.4;
}

.cardmarket-input {
  margin-bottom: 0.5rem;
}

.form-group input.cardmarket-input {
  margin-bottom: 0.5rem;
}

.divider {
  text-align: center;
  margin: 1.5rem 0;
  position: relative;
}

.divider::before,
.divider::after {
  content: '';
  position: absolute;
  top: 50%;
  width: 45%;
  height: 1px;
  background: #e0e0e0;
}

.divider::before {
  left: 0;
}

.divider::after {
  right: 0;
}

.divider span {
  background: white;
  padding: 0 1rem;
  color: #666;
  font-weight: 600;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.form-actions .btn {
  flex: 1;
}

small.help-text {
  display: block;
  margin-top: 0.25rem;
  color: #666;
  font-size: 0.85rem;
}

@media (max-width: 768px) {
  .form-container {
    padding: 1.5rem;
  }

  .quick-options {
    grid-template-columns: 1fr;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column;
  }
}
</style>
