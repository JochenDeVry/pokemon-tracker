<template>
  <div class="achievements-section">
    <h3>🏆 Achievements</h3>
    
    <div class="stats-summary">
      <div class="stat-box">
        <div class="stat-icon">🎴</div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.total_cards || 0 }}</div>
          <div class="stat-label">Unieke Kaarten</div>
        </div>
      </div>
      
      <div class="stat-box">
        <div class="stat-icon">📦</div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.total_quantity || 0 }}</div>
          <div class="stat-label">Totaal Aantal</div>
        </div>
      </div>
      
      <div class="stat-box">
        <div class="stat-icon">💰</div>
        <div class="stat-content">
          <div class="stat-value">€{{ parseFloat(stats.total_value || 0).toFixed(2) }}</div>
          <div class="stat-label">Totale Waarde</div>
        </div>
      </div>
      
      <div class="stat-box">
        <div class="stat-icon">📚</div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.unique_sets || 0 }}</div>
          <div class="stat-label">Sets</div>
        </div>
      </div>
    </div>
    
    <div class="achievements-grid">
      <div 
        v-for="achievement in achievements" 
        :key="achievement.title"
        :class="['achievement-card', achievement.tier, { unlocked: achievement.unlocked }]"
      >
        <div class="achievement-icon">{{ achievement.icon }}</div>
        <div class="achievement-info">
          <h4>{{ achievement.title }}</h4>
          <p>{{ achievement.description }}</p>
        </div>
        <div v-if="achievement.unlocked" class="achievement-badge">✓</div>
        <div v-else class="achievement-lock">🔒</div>
      </div>
    </div>
    
    <div class="progress-summary">
      <div class="progress-text">
        {{ unlockedCount }} / {{ achievements.length }} achievements behaald
      </div>
      <div class="progress-bar">
        <div 
          class="progress-fill" 
          :style="{ width: progressPercentage + '%' }"
        ></div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Achievements',
  props: {
    achievements: {
      type: Array,
      required: true
    },
    stats: {
      type: Object,
      required: true
    }
  },
  computed: {
    unlockedCount() {
      return this.achievements.filter(a => a.unlocked).length
    },
    progressPercentage() {
      if (this.achievements.length === 0) return 0
      return Math.round((this.unlockedCount / this.achievements.length) * 100)
    }
  }
}
</script>

<style scoped>
.achievements-section {
  margin: 30px 0;
}

.achievements-section h3 {
  font-size: 24px;
  margin-bottom: 20px;
  color: #2c3e50;
}

.stats-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 30px;
}

.stat-box {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 20px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.stat-icon {
  font-size: 36px;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 28px;
  font-weight: bold;
  margin-bottom: 5px;
}

.stat-label {
  font-size: 14px;
  opacity: 0.9;
}

.achievements-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 15px;
  margin-bottom: 25px;
}

.achievement-card {
  background: white;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  padding: 15px;
  display: flex;
  align-items: center;
  gap: 12px;
  transition: all 0.3s ease;
  position: relative;
  opacity: 0.5;
}

.achievement-card.unlocked {
  opacity: 1;
  border-color: #4caf50;
  box-shadow: 0 2px 8px rgba(76, 175, 80, 0.2);
}

.achievement-card.bronze.unlocked {
  border-color: #cd7f32;
  background: linear-gradient(135deg, #fff 0%, #f5e6d3 100%);
}

.achievement-card.silver.unlocked {
  border-color: #c0c0c0;
  background: linear-gradient(135deg, #fff 0%, #e8e8e8 100%);
}

.achievement-card.gold.unlocked {
  border-color: #ffd700;
  background: linear-gradient(135deg, #fff 0%, #fff9e6 100%);
}

.achievement-card.platinum.unlocked {
  border-color: #e5e4e2;
  background: linear-gradient(135deg, #fff 0%, #f0f0f0 100%);
}

.achievement-card.legendary.unlocked {
  border-color: #ff6b35;
  background: linear-gradient(135deg, #fff 0%, #ffe8e0 100%);
  animation: glow 2s ease-in-out infinite;
}

@keyframes glow {
  0%, 100% { box-shadow: 0 0 5px rgba(255, 107, 53, 0.5); }
  50% { box-shadow: 0 0 20px rgba(255, 107, 53, 0.8); }
}

.achievement-icon {
  font-size: 32px;
  min-width: 40px;
  text-align: center;
}

.achievement-info {
  flex: 1;
}

.achievement-info h4 {
  margin: 0 0 5px 0;
  font-size: 16px;
  color: #2c3e50;
}

.achievement-info p {
  margin: 0;
  font-size: 13px;
  color: #666;
}

.achievement-badge {
  font-size: 24px;
  color: #4caf50;
}

.achievement-lock {
  font-size: 20px;
  opacity: 0.3;
}

.progress-summary {
  background: white;
  padding: 20px;
  border-radius: 10px;
  border: 2px solid #e0e0e0;
}

.progress-text {
  font-size: 16px;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 10px;
  text-align: center;
}

.progress-bar {
  width: 100%;
  height: 30px;
  background: #e0e0e0;
  border-radius: 15px;
  overflow: hidden;
  position: relative;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  transition: width 0.5s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
}

@media (max-width: 768px) {
  .stats-summary {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .achievements-grid {
    grid-template-columns: 1fr;
  }
}
</style>
