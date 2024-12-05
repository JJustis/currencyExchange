<?php
// gateway-config.php

// Function to get shop configuration
function getShopConfig() {
    $configFile = 'shop_config.json';
    if (!file_exists($configFile)) {
        return [
            'paypal_email' => 'Vikerus1@gmail.com',
            'paypal_sandbox' => true,
            'paypal_currency' => 'USD',
        ];
    }
    $json = file_get_contents($configFile);
    return json_decode($json, true) ?: [];
}

$shopConfig = getShopConfig();

// PayPal configuration
define('PAYPAL_EMAIL', $shopConfig['paypal_email'] ?? '');
define('PAYPAL_SANDBOX', $shopConfig['paypal_sandbox'] ?? true);
define('PAYPAL_CURRENCY', $shopConfig['paypal_currency'] ?? 'USD');

// Set the appropriate PayPal URL based on sandbox setting
if (PAYPAL_SANDBOX) {
    define('PAYPAL_URL', 'https://www.sandbox.paypal.com/cgi-bin/webscr');
} else {
    define('PAYPAL_URL', 'https://www.paypal.com/cgi-bin/webscr');
}

// Set other PayPal URLs
define('PAYPAL_RETURN_URL', 'http://jcmc.serveminecraft.net/widgets/purchaseCurrency/success.php');
define('PAYPAL_CANCEL_URL', 'http://jcmc.serveminecraft.net/widgets/purchaseCurrency/cancel.php');
define('PAYPAL_NOTIFY_URL', 'http://jcmc.serveminecraft.net/widgets/purchaseCurrency/ipn.php');

// You may want to set these URLs dynamically based on your actual website URL
// For example:
// define('PAYPAL_RETURN_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/success.php');
// define('PAYPAL_CANCEL_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/cancel.php');
// define('PAYPAL_NOTIFY_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/ipn.php');
?>