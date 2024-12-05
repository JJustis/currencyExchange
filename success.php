<?php
// success.php
session_start();
require_once 'gateway-config.php';

function getExpectedAmount($exp_amount) {
    switch($exp_amount) {
        case 1000: return 0.05;
        case 5000: return 0.20;
        case 12000: return 0.45;
        default: return null;
    }
}

function verifyAndCreditPurchase() {
    try {
        // Get parameters from URL
        $username = $_GET['custom'] ?? null;
        $item_number = $_GET['item_number'] ?? null;
        $amount = $_GET['amount'] ?? null;
        $token = $_GET['token'] ?? null;
        $payer_id = $_GET['PayerID'] ?? null;

        // Log incoming data for debugging
        error_log("Success.php Parameters: " . print_r($_GET, true));

        if (!$username || !$item_number || !$amount || !$token || !$payer_id) {
            throw new Exception('Missing required parameters - Username: ' . $username . 
                              ', Item: ' . $item_number . 
                              ', Amount: ' . $amount . 
                              ', Token: ' . $token . 
                              ', PayerID: ' . $payer_id);
        }

        // Connect to database
        $db = new PDO('mysql:host=localhost;dbname=reservesphp', 'root', '');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Begin transaction
        $db->beginTransaction();

        // Check/create user
        $check = $db->prepare('SELECT username FROM members WHERE username = ?');
        $check->execute([$username]);
        
        if ($check->rowCount() == 0) {
            $create = $db->prepare('INSERT INTO members (username, exp) VALUES (?, 0)');
            $create->execute([$username]);
        }

        // Extract EXP amount
        $exp_amount = intval(substr($item_number, 4));
        $expected_amount = getExpectedAmount($exp_amount);

        if ($expected_amount === null || floatval($amount) != $expected_amount) {
            throw new Exception('Invalid amount for EXP package');
        }

        // Create unique transaction ID from token
        $transaction_id = 'PP_' . $token;

        // Check if transaction already processed
        $txn_check = $db->prepare('SELECT transaction_id FROM transaction_log WHERE transaction_id = ?');
        $txn_check->execute([$transaction_id]);
        
        if ($txn_check->rowCount() > 0) {
            return [
                'success' => true,
                'message' => 'Transaction already processed',
                'exp_amount' => $exp_amount
            ];
        }

        // Update user's EXP
        $update = $db->prepare('UPDATE members SET exp = exp + ? WHERE username = ?');
        $update->execute([$exp_amount, $username]);

        // Log transaction
        $log = $db->prepare('INSERT INTO transaction_log (username, exp_amount, price, transaction_id, purchase_date, status, payment_method) VALUES (?, ?, ?, ?, NOW(), ?, ?)');
        $log->execute([
            $username,
            $exp_amount,
            $amount,
            $transaction_id,
            'completed',
            'PayPal'
        ]);

        $db->commit();

        return [
            'success' => true,
            'message' => 'EXP successfully credited',
            'exp_amount' => $exp_amount
        ];

    } catch (Exception $e) {
        error_log("Purchase Error: " . $e->getMessage());
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

$result = verifyAndCreditPurchase();
?>
<!-- Rest of your HTML remains the same -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase <?= $result['success'] ? 'Successful' : 'Error' ?></title>
    <!-- Your existing styles here -->
</head>
<body>
    <div class="result-container">
        <div class="result-icon <?= $result['success'] ? 'success' : 'error' ?>">
            <?= $result['success'] ? '✓' : '×' ?>
        </div>
        <h1><?= $result['success'] ? 'Purchase Successful!' : 'Purchase Error' ?></h1>
        
        <div class="details">
            <?php if ($result['success']): ?>
                <p>Transaction has been completed successfully.</p>
                <?php if (isset($result['exp_amount'])): ?>
                    <p><?= number_format($result['exp_amount']) ?> EXP has been credited to your account.</p>
                <?php endif; ?>
            <?php else: ?>
                <p>Error: <?= htmlspecialchars($result['message']) ?></p>
                <p>Please contact support if you believe this is an error.</p>
            <?php endif; ?>
        </div>

        <a href="javascript:window.close();" class="button">Close Window</a>
    </div>
</body>
</html>