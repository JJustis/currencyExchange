<?php
// cancel.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Cancelled</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f0f2f5;
        }
        .cancel-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .cancel-icon {
            color: #dc3545;
            font-size: 48px;
            margin-bottom: 20px;
        }
        h1 {
            color: #dc3545;
            margin-bottom: 20px;
        }
        .details {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #0070ba;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .button:hover {
            background: #005ea6;
        }
    </style>
</head>
<body>
    <div class="cancel-container">
        <div class="cancel-icon">×</div>
        <h1>Purchase Cancelled</h1>
        <p>Your purchase has been cancelled. No charges have been made.</p>
        <div class="details">
            <p>You can try your purchase again at any time.</p>
            <p>If you experienced any issues, please contact support.</p>
        </div>
        <a href="javascript:window.close();" class="button">Close Window</a>
    </div>
</body>
</html>