<?php
require_once __DIR__ . '/../config/Database.php';

class Card {
    private $conn;
    private $table = 'cards';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Get all cards
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Get all cards for a specific user
    public function getAllByUser($userId) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Get single card by ID
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Get card by serial number for specific user
    public function getBySerial($serial, $userId = null) {
        if ($userId) {
            $query = "SELECT * FROM " . $this->table . " WHERE serial_number = :serial AND user_id = :user_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':serial', $serial);
            $stmt->bindParam(':user_id', $userId);
        } else {
            $query = "SELECT * FROM " . $this->table . " WHERE serial_number = :serial LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':serial', $serial);
        }
        $stmt->execute();
        return $stmt->fetch();
    }

    // Create new card
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, serial_number, card_name, set_name, card_number, rarity, condition_card, 
                   quantity, purchase_price, current_price, cardmarket_url, image_url, notes)
                  VALUES 
                  (:user_id, :serial_number, :card_name, :set_name, :card_number, :rarity, :condition_card,
                   :quantity, :purchase_price, :current_price, :cardmarket_url, :image_url, :notes)";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':serial_number', $data['serial_number']);
        $stmt->bindParam(':card_name', $data['card_name']);
        $stmt->bindParam(':set_name', $data['set_name']);
        $stmt->bindParam(':card_number', $data['card_number']);
        $stmt->bindParam(':rarity', $data['rarity']);
        $stmt->bindParam(':condition_card', $data['condition_card']);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':purchase_price', $data['purchase_price']);
        $stmt->bindParam(':current_price', $data['current_price']);
        $stmt->bindParam(':cardmarket_url', $data['cardmarket_url']);
        $stmt->bindParam(':image_url', $data['image_url']);
        $stmt->bindParam(':notes', $data['notes']);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Update card
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET serial_number = :serial_number,
                      card_name = :card_name,
                      set_name = :set_name,
                      card_number = :card_number,
                      rarity = :rarity,
                      condition_card = :condition_card,
                      quantity = :quantity,
                      purchase_price = :purchase_price,
                      current_price = :current_price,
                      cardmarket_url = :cardmarket_url,
                      image_url = :image_url,
                      notes = :notes
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':serial_number', $data['serial_number']);
        $stmt->bindParam(':card_name', $data['card_name']);
        $stmt->bindParam(':set_name', $data['set_name']);
        $stmt->bindParam(':card_number', $data['card_number']);
        $stmt->bindParam(':rarity', $data['rarity']);
        $stmt->bindParam(':condition_card', $data['condition_card']);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':purchase_price', $data['purchase_price']);
        $stmt->bindParam(':current_price', $data['current_price']);
        $stmt->bindParam(':cardmarket_url', $data['cardmarket_url']);
        $stmt->bindParam(':image_url', $data['image_url']);
        $stmt->bindParam(':notes', $data['notes']);

        return $stmt->execute();
    }

    // Update price
    public function updatePrice($id, $price) {
        $query = "UPDATE " . $this->table . " 
                  SET current_price = :price,
                      last_price_update = CURRENT_TIMESTAMP
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':price', $price);

        return $stmt->execute();
    }

    // Delete card
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Search cards
    public function search($searchTerm) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE card_name LIKE :search 
                  OR serial_number LIKE :search 
                  OR set_name LIKE :search 
                  ORDER BY created_at DESC";
        
        $searchParam = '%' . $searchTerm . '%';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':search', $searchParam);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
