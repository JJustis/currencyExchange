<?php
// create_user.php
header('Content-Type: application/json');

try {
    // Connect to database
    $db = new PDO('mysql:host=localhost;dbname=reservesphp', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get username from POST
    $username = $_POST['username'] ?? '';
    
    if (empty($username)) {
        throw new Exception('Username is required');
    }
    
    // Check if user already exists
    $check = $db->prepare('SELECT username FROM members WHERE username = ?');
    $check->execute([$username]);
    
    if ($check->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Username already exists'
        ]);
        exit;
    }
    
    // Create new user
    $stmt = $db->prepare('INSERT INTO members (username, exp) VALUES (?, 0)');
    $result = $stmt->execute([$username]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'User created successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create user'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
