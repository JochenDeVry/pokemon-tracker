<?php
// Enable sessions
session_start();

header("Access-Control-Allow-Origin: http://poketracker.be");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/models/Card.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Achievement.php';
require_once __DIR__ . '/models/Friendship.php';
require_once __DIR__ . '/models/Message.php';
require_once __DIR__ . '/services/CardmarketScraper.php';
require_once __DIR__ . '/middleware/Auth.php';

$card = new Card();
$user = new User();
$friendship = new Friendship();
$message = new Message();
$scraper = new CardmarketScraper();

Auth::init();

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace('/api', '', $path);
$path_parts = explode('/', trim($path, '/'));

// Get JSON input for POST/PUT requests
$input = json_decode(file_get_contents('php://input'), true);

try {
    // Route handling
    switch($method) {
        case 'GET':
            // Auth routes
            if ($path_parts[0] === 'auth' && $path_parts[1] === 'user') {
                // GET /api/auth/user - Get current user
                if (Auth::check()) {
                    http_response_code(200);
                    echo json_encode(['success' => true, 'user' => Auth::user()]);
                } else {
                    http_response_code(401);
                    echo json_encode(['success' => false, 'message' => 'Niet ingelogd']);
                }
                
            } elseif ($path_parts[0] === 'users' && isset($path_parts[1]) && is_numeric($path_parts[1])) {
                // GET /api/users/{id} - Get user profile with stats
                Auth::require();
                $userId = $path_parts[1];
                
                if (!Auth::canViewUser($userId)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Deze collectie is privé']);
                    break;
                }
                
                $userData = $user->getById($userId);
                if ($userData) {
                    unset($userData['password_hash']);
                    $userData['stats'] = $user->getStats($userId);
                    $userData['achievements'] = Achievement::getUserAchievements($userData['stats']);
                    http_response_code(200);
                    echo json_encode(['success' => true, 'data' => $userData]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Gebruiker niet gevonden']);
                }
                
            } elseif ($path_parts[0] === 'users') {
                // GET /api/users - Get all public users
                Auth::require();
                $users = $user->getAllPublic();
                http_response_code(200);
                echo json_encode(['success' => true, 'data' => $users]);
                
            } elseif ($path_parts[0] === 'leaderboard') {
                // Leaderboard endpoints
                Auth::require();
                $currentUserId = Auth::userId();
                
                if (isset($path_parts[1]) && $path_parts[1] === 'friends') {
                    // GET /api/leaderboard/friends - Friends leaderboard
                    $leaderboard = $user->getFriendsLeaderboard($currentUserId);
                    http_response_code(200);
                    echo json_encode(['success' => true, 'data' => $leaderboard]);
                    
                } elseif (isset($path_parts[1]) && $path_parts[1] === 'global') {
                    // GET /api/leaderboard/global - Global leaderboard
                    $leaderboard = $user->getGlobalLeaderboard($currentUserId);
                    http_response_code(200);
                    echo json_encode(['success' => true, 'data' => $leaderboard]);
                }
                
            } elseif ($path_parts[0] === 'friendships' || $path_parts[0] === 'friends') {
                // Friendship endpoints
                Auth::require();
                $currentUserId = Auth::userId();
                
                if (!isset($path_parts[1])) {
                    // GET /api/friends - Get all friends
                    $friends = $friendship->getFriends($currentUserId);
                    http_response_code(200);
                    echo json_encode(['success' => true, 'data' => $friends]);
                    
                } elseif ($path_parts[1] === 'requests') {
                    // GET /api/friends/requests - Get pending requests (received)
                    $requests = $friendship->getPendingRequests($currentUserId);
                    http_response_code(200);
                    echo json_encode(['success' => true, 'data' => $requests]);
                    
                } elseif ($path_parts[1] === 'sent') {
                    // GET /api/friends/sent - Get sent requests
                    $sent = $friendship->getSentRequests($currentUserId);
                    http_response_code(200);
                    echo json_encode(['success' => true, 'data' => $sent]);
                    
                } elseif ($path_parts[1] === 'status' && isset($path_parts[2])) {
                    // GET /api/friends/status/{userId} - Get friendship status
                    $targetUserId = (int)$path_parts[2];
                    $status = $friendship->getFriendshipStatus($currentUserId, $targetUserId);
                    http_response_code(200);
                    echo json_encode(['success' => true, 'data' => $status]);
                }
                
            } elseif ($path_parts[0] === 'messages') {
                // Messages endpoints
                Auth::require();
                $currentUserId = Auth::userId();
                
                if (!isset($path_parts[1])) {
                    // GET /api/messages - Get all conversations
                    $conversations = $message->getConversations($currentUserId);
                    http_response_code(200);
                    echo json_encode(['success' => true, 'data' => $conversations]);
                    
                } elseif ($path_parts[1] === 'conversation' && isset($path_parts[2])) {
                    // GET /api/messages/conversation/{friendId} - Get conversation
                    $friendId = (int)$path_parts[2];
                    
                    // Check if they are friends
                    if (!$friendship->areFriends($currentUserId, $friendId)) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Je kunt alleen berichten sturen naar vrienden']);
                        break;
                    }
                    
                    $messages = $message->getConversation($currentUserId, $friendId);
                    $message->markAsRead($currentUserId, $friendId);
                    http_response_code(200);
                    echo json_encode(['success' => true, 'data' => $messages]);
                    
                } elseif ($path_parts[1] === 'unread') {
                    // GET /api/messages/unread - Get unread count
                    $count = $message->getUnreadCount($currentUserId);
                    http_response_code(200);
                    echo json_encode(['success' => true, 'data' => ['count' => $count]]);
                    
                } elseif ($path_parts[1] === 'poll' && isset($path_parts[2]) && isset($_GET['since'])) {
                    // GET /api/messages/poll/{friendId}?since={timestamp} - Poll for new messages
                    $friendId = (int)$path_parts[2];
                    $since = $_GET['since'];
                    
                    if (!$friendship->areFriends($currentUserId, $friendId)) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Niet toegestaan']);
                        break;
                    }
                    
                    $newMessages = $message->getNewMessages($currentUserId, $friendId, $since);
                    $message->markAsRead($currentUserId, $friendId);
                    http_response_code(200);
                    echo json_encode(['success' => true, 'data' => $newMessages]);
                }
                
            } elseif (empty($path_parts[0]) || $path_parts[0] === 'cards' && !isset($path_parts[1])) {
                // GET /api/cards - Get all cards (for current user or specified user)
                Auth::require();
                $userId = $_GET['user_id'] ?? Auth::userId();
                
                if (!Auth::canViewUser($userId)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Deze collectie is privé']);
                    break;
                }
                
                $cards = $card->getAllByUser($userId);
                http_response_code(200);
                echo json_encode(['success' => true, 'data' => $cards]);
                
            } elseif ($path_parts[0] === 'cards' && isset($path_parts[1])) {
                if ($path_parts[1] === 'search' && isset($_GET['q'])) {
                    // GET /api/cards/search?q=term - Search cards
                    $results = $card->search($_GET['q']);
                    http_response_code(200);
                    echo json_encode(['success' => true, 'data' => $results]);
                    
                } elseif ($path_parts[1] === 'serial' && isset($_GET['serial'])) {
                    // GET /api/cards/serial?serial=XXX - Get by serial
                    $result = $card->getBySerial($_GET['serial']);
                    if ($result) {
                        http_response_code(200);
                        echo json_encode(['success' => true, 'data' => $result]);
                    } else {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'Card niet gevonden']);
                    }
                    
                } elseif (is_numeric($path_parts[1])) {
                    // GET /api/cards/{id} - Get single card
                    $result = $card->getById($path_parts[1]);
                    if ($result) {
                        http_response_code(200);
                        echo json_encode(['success' => true, 'data' => $result]);
                    } else {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'Card niet gevonden']);
                    }
                    
                } elseif ($path_parts[1] === 'price' && isset($path_parts[2])) {
                    // GET /api/cards/price/{id} - Update price from Cardmarket
                    $cardData = $card->getById($path_parts[2]);
                    if ($cardData && $cardData['cardmarket_url']) {
                        $price = $scraper->getPrice($cardData['cardmarket_url']);
                        if ($price !== null) {
                            $card->updatePrice($path_parts[2], $price);
                            http_response_code(200);
                            echo json_encode(['success' => true, 'price' => $price]);
                        } else {
                            http_response_code(500);
                            echo json_encode(['success' => false, 'message' => 'Kon prijs niet ophalen']);
                        }
                    } else {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'Card niet gevonden of geen Cardmarket URL']);
                    }
                }
            } elseif ($path_parts[0] === 'proxy-image' && isset($_GET['url'])) {
                // GET /api/proxy-image?url=... - Proxy external images to avoid CORS
                $imageUrl = $_GET['url'];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $imageUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Referer: https://www.cardmarket.com/',
                    'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8'
                ]);
                
                $imageData = curl_exec($ch);
                $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200 && $imageData) {
                    header('Content-Type: ' . $contentType);
                    header('Cache-Control: public, max-age=86400'); // Cache voor 1 dag
                    echo $imageData;
                } else {
                    http_response_code(404);
                    // Return a transparent 1x1 pixel PNG
                    header('Content-Type: image/png');
                    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
                }
            }
            break;

        case 'POST':
            // Auth routes
            if ($path_parts[0] === 'auth' && $path_parts[1] === 'register') {
                // POST /api/auth/register - Register new user
                if (!isset($input['username']) || !isset($input['email']) || !isset($input['password'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Username, email en wachtwoord zijn verplicht']);
                    break;
                }
                
                $result = $user->register(
                    $input['username'],
                    $input['email'],
                    $input['password'],
                    $input['display_name'] ?? null
                );
                
                if ($result['success']) {
                    Auth::login($result['user']);
                    http_response_code(201);
                    echo json_encode(['success' => true, 'user' => $result['user']]);
                } else {
                    http_response_code(400);
                    echo json_encode($result);
                }
                
            } elseif ($path_parts[0] === 'auth' && $path_parts[1] === 'login') {
                // POST /api/auth/login - Login user
                if (!isset($input['email']) || !isset($input['password'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Email en wachtwoord zijn verplicht']);
                    break;
                }
                
                $result = $user->login($input['email'], $input['password']);
                
                if ($result['success']) {
                    Auth::login($result['user']);
                    http_response_code(200);
                    echo json_encode(['success' => true, 'user' => $result['user']]);
                } else {
                    http_response_code(401);
                    echo json_encode($result);
                }
                
            } elseif ($path_parts[0] === 'auth' && $path_parts[1] === 'logout') {
                // POST /api/auth/logout - Logout user
                Auth::logout();
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Uitgelogd']);
                
            } elseif ($path_parts[0] === 'friends' && isset($path_parts[1])) {
                // Friend request endpoints
                Auth::require();
                $currentUserId = Auth::userId();
                
                if ($path_parts[1] === 'request' && isset($input['friend_id'])) {
                    // POST /api/friends/request - Send friend request
                    $friendId = (int)$input['friend_id'];
                    
                    if ($friendId === $currentUserId) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Je kunt jezelf niet als vriend toevoegen']);
                        break;
                    }
                    
                    $result = $friendship->sendRequest($currentUserId, $friendId);
                    if ($result) {
                        http_response_code(201);
                        echo json_encode(['success' => true, 'message' => 'Vriendschapsverzoek verzonden']);
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Verzoek bestaat al of kon niet worden verzonden']);
                    }
                    
                } elseif ($path_parts[1] === 'accept' && isset($input['friend_id'])) {
                    // POST /api/friends/accept - Accept friend request
                    $friendId = (int)$input['friend_id'];
                    $result = $friendship->acceptRequest($currentUserId, $friendId);
                    
                    if ($result) {
                        http_response_code(200);
                        echo json_encode(['success' => true, 'message' => 'Vriendschapsverzoek geaccepteerd']);
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Kon verzoek niet accepteren']);
                    }
                    
                } elseif ($path_parts[1] === 'decline' && isset($input['friend_id'])) {
                    // POST /api/friends/decline - Decline friend request
                    $friendId = (int)$input['friend_id'];
                    $result = $friendship->declineRequest($currentUserId, $friendId);
                    
                    if ($result) {
                        http_response_code(200);
                        echo json_encode(['success' => true, 'message' => 'Vriendschapsverzoek afgewezen']);
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Kon verzoek niet afwijzen']);
                    }
                    
                } elseif ($path_parts[1] === 'remove' && isset($input['friend_id'])) {
                    // POST /api/friends/remove - Remove friend
                    $friendId = (int)$input['friend_id'];
                    $result = $friendship->removeFriend($currentUserId, $friendId);
                    
                    if ($result) {
                        http_response_code(200);
                        echo json_encode(['success' => true, 'message' => 'Vriend verwijderd']);
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Kon vriend niet verwijderen']);
                    }
                }
                
            } elseif ($path_parts[0] === 'messages') {
                // Messages endpoints
                Auth::require();
                $currentUserId = Auth::userId();
                
                if (!isset($path_parts[1])) {
                    // POST /api/messages - Send message
                    if (!isset($input['receiver_id']) || !isset($input['message'])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Ontvanger en bericht zijn verplicht']);
                        break;
                    }
                    
                    $receiverId = (int)$input['receiver_id'];
                    $messageText = trim($input['message']);
                    
                    if (empty($messageText)) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Bericht mag niet leeg zijn']);
                        break;
                    }
                    
                    // Check if they are friends
                    if (!$friendship->areFriends($currentUserId, $receiverId)) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Je kunt alleen berichten sturen naar vrienden']);
                        break;
                    }
                    
                    $result = $message->send($currentUserId, $receiverId, $messageText);
                    if ($result) {
                        http_response_code(201);
                        echo json_encode(['success' => true, 'message' => 'Bericht verzonden']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Kon bericht niet verzenden']);
                    }
                }
                
            } elseif ($path_parts[0] === 'profile') {
                // Profile endpoints
                Auth::require();
                $currentUserId = Auth::userId();
                
                if (isset($path_parts[1]) && $path_parts[1] === 'password') {
                    // POST /api/profile/password - Change password
                    if (!isset($input['current_password']) || !isset($input['new_password'])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Huidig en nieuw wachtwoord zijn verplicht']);
                        break;
                    }
                    
                    $result = $user->changePassword($currentUserId, $input['current_password'], $input['new_password']);
                    if ($result) {
                        http_response_code(200);
                        echo json_encode(['success' => true, 'message' => 'Wachtwoord gewijzigd']);
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Huidig wachtwoord is onjuist']);
                    }
                }
                
            } elseif ($path_parts[0] === 'cards') {
                // POST /api/cards - Create new card
                Auth::require();
                
                if (!isset($input['serial_number']) || !isset($input['card_name'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Serienummer en kaartnaam zijn verplicht']);
                    break;
                }

                // Check if serial already exists for this user
                $existing = $card->getBySerial($input['serial_number'], Auth::userId());
                if ($existing) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'Kaart met dit serienummer bestaat al']);
                    break;
                }

                // Set defaults
                $cardData = [
                    'user_id' => Auth::userId(),
                    'serial_number' => $input['serial_number'],
                    'card_name' => $input['card_name'],
                    'set_name' => $input['set_name'] ?? '',
                    'card_number' => $input['card_number'] ?? '',
                    'rarity' => $input['rarity'] ?? '',
                    'condition_card' => $input['condition_card'] ?? 'Near Mint',
                    'quantity' => $input['quantity'] ?? 1,
                    'purchase_price' => $input['purchase_price'] ?? null,
                    'current_price' => $input['current_price'] ?? null,
                    'cardmarket_url' => $input['cardmarket_url'] ?? '',
                    'image_url' => $input['image_url'] ?? '',
                    'notes' => $input['notes'] ?? ''
                ];

                $id = $card->create($cardData);
                if ($id) {
                    $newCard = $card->getById($id);
                    http_response_code(201);
                    echo json_encode(['success' => true, 'data' => $newCard, 'message' => 'Kaart toegevoegd']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Kon kaart niet toevoegen']);
                }
            } elseif ($path_parts[0] === 'scrape') {
                // POST /api/scrape - Scrape Cardmarket URL
                if (!isset($input['url'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'URL is verplicht']);
                    break;
                }

                $cardInfo = $scraper->scrapeCard($input['url']);
                if ($cardInfo) {
                    http_response_code(200);
                    echo json_encode(['success' => true, 'data' => $cardInfo]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Kon kaart informatie niet ophalen van Cardmarket. Cardmarket blokkeert mogelijk scraping. Voer de gegevens handmatig in.'
                    ]);
                }
            }
            break;

        case 'PUT':
            if ($path_parts[0] === 'profile') {
                // PUT /api/profile - Update profile
                Auth::require();
                $currentUserId = Auth::userId();
                
                $result = $user->updateProfile($currentUserId, [
                    'display_name' => $input['display_name'] ?? null,
                    'email' => $input['email'] ?? null,
                    'is_public' => isset($input['is_public']) ? (int)$input['is_public'] : null
                ]);
                
                if ($result) {
                    http_response_code(200);
                    echo json_encode(['success' => true, 'message' => 'Profiel bijgewerkt']);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Kon profiel niet bijwerken']);
                }
                
            } elseif ($path_parts[0] === 'cards' && isset($path_parts[1]) && is_numeric($path_parts[1])) {
                // PUT /api/cards/{id} - Update card
                Auth::require();
                $id = $path_parts[1];
                
                $existing = $card->getById($id);
                if (!$existing) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Card niet gevonden']);
                    break;
                }
                
                // Check ownership
                if ($existing['user_id'] != Auth::userId()) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Je kunt alleen je eigen kaarten bewerken']);
                    break;
                }

                $cardData = [
                    'serial_number' => $input['serial_number'] ?? $existing['serial_number'],
                    'card_name' => $input['card_name'] ?? $existing['card_name'],
                    'set_name' => $input['set_name'] ?? $existing['set_name'],
                    'card_number' => $input['card_number'] ?? $existing['card_number'],
                    'rarity' => $input['rarity'] ?? $existing['rarity'],
                    'condition_card' => $input['condition_card'] ?? $existing['condition_card'],
                    'quantity' => $input['quantity'] ?? $existing['quantity'],
                    'purchase_price' => $input['purchase_price'] ?? $existing['purchase_price'],
                    'current_price' => $input['current_price'] ?? $existing['current_price'],
                    'cardmarket_url' => $input['cardmarket_url'] ?? $existing['cardmarket_url'],
                    'image_url' => $input['image_url'] ?? $existing['image_url'],
                    'notes' => $input['notes'] ?? $existing['notes']
                ];

                if ($card->update($id, $cardData)) {
                    $updated = $card->getById($id);
                    http_response_code(200);
                    echo json_encode(['success' => true, 'data' => $updated, 'message' => 'Kaart bijgewerkt']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Kon kaart niet bijwerken']);
                }
            }
            break;

        case 'DELETE':
            if ($path_parts[0] === 'cards' && isset($path_parts[1]) && is_numeric($path_parts[1])) {
                // DELETE /api/cards/{id} - Delete card
                Auth::require();
                $id = $path_parts[1];
                
                $existing = $card->getById($id);
                if (!$existing) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Card niet gevonden']);
                    break;
                }
                
                // Check ownership
                if ($existing['user_id'] != Auth::userId()) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Je kunt alleen je eigen kaarten verwijderen']);
                    break;
                }
                
                if ($card->delete($id)) {
                    http_response_code(200);
                    echo json_encode(['success' => true, 'message' => 'Kaart verwijderd']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Kon kaart niet verwijderen']);
                }
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
