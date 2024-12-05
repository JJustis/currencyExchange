<?php
// process-exp.php - Add this to your ipn.php file or include it

function createNewUser($username) {
    try {
        $db = new PDO('mysql:host=localhost;dbname=reservesphp', 'root', '');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if user already exists
        $check = $db->prepare('SELECT username FROM members WHERE username = ?');
        $check->execute([$username]);
        
        if ($check->rowCount() == 0) {
            // User doesn't exist, create new user
            $stmt = $db->prepare('INSERT INTO members (username, exp) VALUES (?, 0)');
            $result = $stmt->execute([$username]);
            
            if ($result) {
                logIpnMessage("Created new user: $username");
                return true;
            }
        }
        
        return false;
    } catch (PDOException $e) {
        logIpnMessage("Database error creating user: " . $e->getMessage());
        return false;
    }
}

function processExpPurchase($payment_data) {
    // Verify this is an EXP purchase
    if (strpos($payment_data['item_number'], 'EXP_') !== 0) {
        return false;
    }
    
    // Get username from custom field
    $username = $payment_data['custom'];
    
    // Get amount of EXP from item_number (EXP_1000 = 1000 EXP)
    $exp_amount = intval(substr($payment_data['item_number'], 4));
    
    // Verify payment amount matches EXP amount
    $expected_amount = match($exp_amount) {
        1000 => 0.05,
        5000 => 0.20,
        12000 => 0.45,
        default => null
    };
    
    if ($expected_amount === null || floatval($payment_data['mc_gross']) != $expected_amount) {
        logIpnMessage("Payment amount mismatch for EXP purchase: expected $expected_amount, got {$payment_data['mc_gross']}");
        return false;
    }
    
    try {
        // Connect to database
        $db = new PDO('mysql:host=localhost;dbname=reservesphp', 'root', '');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Begin transaction
        $db->beginTransaction();
        
        // Check if user exists
        $check = $db->prepare('SELECT username FROM members WHERE username = ?');
        $check->execute([$username]);
        
        if ($check->rowCount() == 0) {
            // Create new user if they don't exist
            if (!createNewUser($username)) {
                $db->rollBack();
                logIpnMessage("Failed to create new user: $username");
                return false;
            }
        }
        
        // Update user's EXP
        $stmt = $db->prepare('UPDATE members SET exp = exp + ? WHERE username = ?');
        $result = $stmt->execute([$exp_amount, $username]);
        
        if ($result) {
            $db->commit();
            logIpnMessage("EXP Purchase: $exp_amount EXP credited to $username");
            
            // Log the transaction
            $log_stmt = $db->prepare('INSERT INTO transaction_log (username, exp_amount, price, transaction_id, purchase_date) VALUES (?, ?, ?, ?, NOW())');
            $log_stmt->execute([$username, $exp_amount, $payment_data['mc_gross'], $payment_data['txn_id']]);
            
            return true;
        } else {
            $db->rollBack();
            logIpnMessage("Failed to update EXP for user: $username");
            return false;
        }
        
    } catch (PDOException $e) {
        if (isset($db)) {
            $db->rollBack();
        }
        logIpnMessage("Database error: " . $e->getMessage());
        return false;
    }
}

// Add this to your existing IPN verification block in ipn.php
if (strcmp($res, "VERIFIED") == 0 && $payment_status == 'Completed') {
    // If this is an EXP purchase, process it
    if (strpos($item_number, 'EXP_') === 0) {
        $payment_data = [
            'item_number' => $item_number,
            'custom' => $_POST['custom'],
            'mc_gross' => $payment_amount,
            'txn_id' => $txn_id
        ];
        processExpPurchase($payment_data);
    }
}
?>