// Create the widget container div and form
const widgetContainer = document.createElement('div');
widgetContainer.id = 'virtual-currency-widget';
widgetContainer.innerHTML = `
    <style>
        .currency-widget {
            max-width: 400px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-family: Arial, sans-serif;
        }
        .currency-widget h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .currency-widget .form-group {
            margin-bottom: 15px;
        }
        .currency-widget label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        .currency-widget input,
        .currency-widget select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .currency-widget .package-info {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            margin: 15px 0;
            text-align: center;
        }
        .currency-widget button {
            width: 100%;
            padding: 10px;
            background: #0070ba;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .currency-widget button:hover {
            background: #005ea6;
        }
    </style>
    <div class="currency-widget">
        <h2>Purchase Virtual Currency</h2>
        <form id="currency-purchase-form">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="exp-amount">Select Amount:</label>
                <select id="exp-amount" name="exp-amount" required>
                    <option value="1000" data-price="0.05">1,000 EXP ($0.05)</option>
                    <option value="5000" data-price="0.20">5,000 EXP ($0.20)</option>
                    <option value="12000" data-price="0.45">12,000 EXP ($0.45)</option>
                </select>
            </div>
            <div class="package-info">
                <p id="package-exp">Package: 1,000 EXP</p>
                <p id="package-price">Price: $0.05 USD</p>
            </div>
            <button type="submit">Buy Now with PayPal</button>
        </form>
    </div>
`;

// Add the widget to the page
document.body.appendChild(widgetContainer);

// Update package info when selection changes
document.getElementById('exp-amount').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const expAmount = parseInt(selectedOption.value).toLocaleString();
    const price = selectedOption.dataset.price;
    
    document.getElementById('package-exp').textContent = `Package: ${expAmount} EXP`;
    document.getElementById('package-price').textContent = `Price: $${price} USD`;
});

// Handle form submission
document.getElementById('currency-purchase-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const username = document.getElementById('username').value;
    const expSelect = document.getElementById('exp-amount');
    const selectedOption = expSelect.options[expSelect.selectedIndex];
    const expAmount = selectedOption.value;
    const price = selectedOption.dataset.price;
    
    // Create and submit PayPal form
    const paypalForm = document.createElement('form');
    paypalForm.method = 'post';
    paypalForm.action = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    
    // PayPal form fields
    const formFields = {
        cmd: '_xclick',
        business: 'Vikerus1@gmail.com',
        item_name: `${expAmount} EXP for ${username}`,
        item_number: `EXP_${expAmount}`,
        amount: price,
        currency_code: 'USD',
        return: 'https://betahut.bounceme.net/paypalipnhowto/success.php',
        cancel_return: 'https://betahut.bounceme.net/paypalipnhowto/cancel.php',
        notify_url: 'https://betahut.bounceme.net/paypalipnhowto/ipn.php',
        custom: username
    };
    
    // Add hidden fields to PayPal form
    for (const [name, value] of Object.entries(formFields)) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        paypalForm.appendChild(input);
    }
    
    // Submit the form
    document.body.appendChild(paypalForm);
    paypalForm.submit();
    document.body.removeChild(paypalForm);
});