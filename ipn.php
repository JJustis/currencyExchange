<?php
// ipn.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error_log.txt');

require_once 'gateway-config.php';

// Initialize Database Connection
function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new PDO('mysql:host=localhost;dbname=reservesphp', 'root', '');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            logMessage("Database connection failed: " . $e->getMessage());
            die();
        }
    }
    return $db;
}

// Logging Function
function logMessage($message) {
    $logFile = dirname(__FILE__) . '/ipn_log.txt';
    $date = date('[Y-m-d H:i:s] ');
    file_put_contents($logFile, $date . $message . "\n", FILE_APPEND);
}

// Get Expected Amount
function getExpectedAmount($exp_amount) {
    $prices = [
        1000 => 0.05,
        5000 => 0.20,
        12000 => 0.45
    ];
    return $prices[$exp_amount] ?? null;
}

// Validate PayPal IPN
function validateIPN() {
    // Read POST data
    $raw_post_data = file_get_contents('php://input');
    logMessage("Raw POST data: " . $raw_post_data);
    
    if (empty($raw_post_data)) {
        logMessage("No POST data received");
        return false;
    }

    $raw_post_array = explode('&', $raw_post_data);
    $myPost = array();
    foreach ($raw_post_array as $keyval) {
        $keyval = explode('=', $keyval, 2);
        if (count($keyval) == 2) {
            $myPost[$keyval[0]] = urldecode($keyval[1]);
        }
    }

    // Prepare validation request
    $req = 'cmd=_notify-validate';
    foreach ($myPost as $key => $value) {
        $value = urlencode($value);
        $req .= "&$key=$value";
    }

    // Send to PayPal for validation
    $paypalUrl = PAYPAL_SANDBOX ? 
        "https://ipnpb.sandbox.paypal.com/cgi-bin/webscr" : 
        "https://ipnpb.paypal.com/cgi-bin/webscr";

    $ch = curl_init($paypalUrl);
    curl_setopt_array($ch, [
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_POST => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POSTFIELDS => $req,
        CURLOPT_SSL_VERIFYPEER => 1,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FORBID_REUSE => 1,
        CURLOPT_HTTPHEADER => array('Connection: Close'),
        CURLOPT_TIMEOUT => 30
    ]);

    $res = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        logMessage("cURL Error: $error");
        return false;
    }

    return $res === "VERIFIED";
}

// Process EXP Purchase
function processExpPurchase($txn_id, $username, $item_number, $amount) {
    try {
        $db = getDB();
        
        // Check if transaction already processed
        $stmt = $db->prepare('SELECT id FROM transaction_log WHERE transaction_id = ?');
        $stmt->execute([$txn_id]);
        if ($stmt->fetchColumn()) {
            logMessage("Transaction already processed: $txn_id");
            return true;
        }

        // Begin transaction
        $db->beginTransaction();

        // Check/Create user
        $stmt = $db->prepare('SELECT username FROM members WHERE username = ?');
        $stmt->execute([$username]);
        if (!$stmt->fetch()) {
            $stmt = $db->prepare('INSERT INTO members (username, exp) VALUES (?, 0)');
            $stmt->execute([$username]);
            logMessage("Created new user: $username");
        }

        // Get EXP amount
        $exp_amount = intval(substr($item_number, 4));
        $expected_amount = getExpectedAmount($exp_amount);

        if ($expected_amount === null || abs($expected_amount - floatval($amount)) > 0.01) {
            throw new Exception("Amount mismatch: expected $expected_amount, got $amount");
        }

        // Update user's EXP
        $stmt = $db->prepare('UPDATE members SET exp = exp + ? WHERE username = ?');
        $stmt->execute([$exp_amount, $username]);

        // Log transaction
        $stmt = $db->prepare('INSERT INTO transaction_log (username, exp_amount, price, transaction_id, purchase_date, status, payment_method, ip_address) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)');
        $stmt->execute([
            $username,
            $exp_amount,
            $amount,
            $txn_id,
            'completed',
            'PayPal',
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        $db->commit();
        logMessage("Successfully processed purchase: $exp_amount EXP for $username");
        return true;

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        logMessage("Error processing purchase: " . $e->getMessage());
        return false;
    }
}

// Main IPN Processing
try {
    logMessage("--- Starting IPN Processing ---");
    
    if (!validateIPN()) {
        logMessage("IPN Validation Failed");
        die();
    }

    $payment_status = $_POST['payment_status'] ?? '';
    $receiver_email = $_POST['receiver_email'] ?? '';
    $txn_id = $_POST['txn_id'] ?? '';
    $custom = $_POST['custom'] ?? ''; // username
    $item_number = $_POST['item_number'] ?? '';
    $mc_gross = $_POST['mc_gross'] ?? '';

    logMessage("Payment Status: $payment_status");
    logMessage("Receiver Email: $receiver_email");
    logMessage("Transaction ID: $txn_id");
    logMessage("Username: $custom");
    logMessage("Item Number: $item_number");
    logMessage("Amount: $mc_gross");

    if ($payment_status !== 'Completed') {
        logMessage("Payment not completed. Status: $payment_status");
        die();
    }

    if ($receiver_email !== PAYPAL_EMAIL) {
        logMessage("Invalid receiver email: $receiver_email");
        die();
    }

    if (empty($txn_id) || empty($custom) || empty($item_number) || empty($mc_gross)) {
        logMessage("Missing required fields");
        die();
    }

    processExpPurchase($txn_id, $custom, $item_number, $mc_gross);

} catch (Exception $e) {
    logMessage("Fatal Error: " . $e->getMessage());
}

logMessage("--- IPN Processing Complete ---");
?>