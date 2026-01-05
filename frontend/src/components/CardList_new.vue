<template>
  <div class="card-list">
    <div class="list-header">
      <h2>Mijn Pokemon Kaarten Collectie</h2>
      
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
