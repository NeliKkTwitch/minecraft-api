<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Configuration MySQL
$host = 'sql101.infinityfree.com';
$dbname = 'if0_40372762_pokenelikk';
$username = 'if0_40372762';
$password = getenv('DB_PASSWORD');

// CLÉ SECRÈTE - CHANGEZ-LA !
define('API_KEY', getenv('API_KEY'));

// Vérifier la clé API
if (!isset($_GET['key']) || $_GET['key'] !== API_KEY) {
    http_response_code(403);
    die(json_encode(['error' => 'Invalid API key']));
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed', 'details' => $e->getMessage()]));
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'test':
        echo json_encode(['success' => true, 'message' => 'API working']);
        break;

    case 'getPendingVotes':
        $uuid = $_GET['uuid'] ?? '';
        if (empty($uuid)) {
            echo json_encode([]);
            break;
        }
        $stmt = $pdo->prepare("SELECT * FROM cmw_pending_votes WHERE player_uuid = ? AND delivered = FALSE");
        $stmt->execute([$uuid]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'markVoteDelivered':
        $voteId = (int)($_GET['id'] ?? 0);
        if ($voteId > 0) {
            $stmt = $pdo->prepare("UPDATE cmw_pending_votes SET delivered = TRUE, delivered_at = NOW() WHERE id = ?");
            $stmt->execute([$voteId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        }
        break;

    case 'getPendingShop':
        $uuid = $_GET['uuid'] ?? '';
        if (empty($uuid)) {
            echo json_encode([]);
            break;
        }
        $stmt = $pdo->prepare("SELECT * FROM cmw_pending_shop WHERE player_uuid = ? AND delivered = FALSE");
        $stmt->execute([$uuid]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'markShopDelivered':
        $purchaseId = (int)($_GET['id'] ?? 0);
        if ($purchaseId > 0) {
            $stmt = $pdo->prepare("UPDATE cmw_pending_shop SET delivered = TRUE, delivered_at = NOW() WHERE id = ?");
            $stmt->execute([$purchaseId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        }
        break;

    case 'updatePlayer':
        $uuid = $_GET['uuid'] ?? '';
        $username = $_GET['username'] ?? '';
        $isOnline = (int)($_GET['online'] ?? 0);
        
        if (empty($uuid) || empty($username)) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters']);
            break;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO cmw_players (uuid, username, is_online, last_join)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                username = VALUES(username),
                is_online = VALUES(is_online),
                last_join = NOW()
        ");
        $stmt->execute([$uuid, $username, $isOnline]);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

?>
