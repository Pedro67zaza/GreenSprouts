<?php
 include 'db_connect.php';
?>

<?php
// api.php - API Endpoint for CRUD Operations
header('Content-Type: application/json');
require_once 'db_config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch($method) {
    case 'GET':
        if ($action === 'getAll') {
            getAllUsers($pdo);
        }
        break;
    
    case 'POST':
        if ($action === 'create') {
            createUser($pdo);
        }
        break;
    
    case 'PUT':
        if ($action === 'update') {
            updateUser($pdo);
        }
        break;
    
    case 'DELETE':
        if ($action === 'delete') {
            deleteUser($pdo);
        }
        break;
}

function getAllUsers($pdo) {
    try {
        $stmt = $pdo->query("SELECT userId, username, password, email, userImage, role FROM users ORDER BY userId");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format userImage field
        foreach($users as &$user) {
            if (empty($user['userImage']) || is_null($user['userImage'])) {
                $user['userImage'] = 'NULL';
            }
        }
        
        echo json_encode(['success' => true, 'data' => $users]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function createUser($pdo) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $username = $data['username'];
        $password = password_hash($data['password'], PASSWORD_BCRYPT);
        $email = $data['email'];
        $userImage = $data['userImage'] === 'NULL' ? null : $data['userImage'];
        $role = $data['role'] ?? 'admin'; // Default to admin for new users created from admin panel
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, userImage, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $email, $userImage, $role]);
        
        echo json_encode(['success' => true, 'message' => 'User created successfully', 'userId' => $pdo->lastInsertId()]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateUser($pdo) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $userId = $data['userId'];
        $username = $data['username'];
        $email = $data['email'];
        $userImage = $data['userImage'] === 'NULL' ? null : $data['userImage'];
        $role = $data['role'];
        
        // Only update password if a new one is provided
        if (!empty($data['password']) && !str_starts_with($data['password'], '$2y$')) {
            $password = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, email = ?, userImage = ?, role = ? WHERE userId = ?");
            $stmt->execute([$username, $password, $email, $userImage, $role, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, userImage = ?, role = ? WHERE userId = ?");
            $stmt->execute([$username, $email, $userImage, $role, $userId]);
        }
        
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteUser($pdo) {
    try {
        $userId = $_GET['userId'] ?? null;
        
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE userId = ?");
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>