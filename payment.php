<?php 
// Turn off error reporting for AJAX requests to prevent HTML output
if (isset($_POST['action']) && $_POST['action'] === 'mark_paid') {
    error_reporting(0);
    ini_set('display_errors', 0);
}

require_once('../config/db.php');

// Handle AJAX payment confirmation
if (isset($_POST['action']) && $_POST['action'] === 'mark_paid' && isset($_POST['booking_id'])) {
    // Set content type for JSON response
    header('Content-Type: application/json');
    
    $booking_id = intval($_POST['booking_id']);
    
    // Start transaction to ensure data consistency
    $conn->begin_transaction();
    
    try {
        // Check if booking exists first
        $check_booking = $conn->prepare("SELECT * FROM bookings WHERE id=?");
        $check_booking->bind_param("i", $booking_id);
        $check_booking->execute();
        $check_result = $check_booking->get_result();
        
        if ($check_result->num_rows === 0) {
            throw new Exception('Booking not found with ID: ' . $booking_id);
        }
        
        // Update booking status
        $update_booking = $conn->prepare("UPDATE bookings SET status='Paid' WHERE id=?");
        $update_booking->bind_param("i", $booking_id);
        if (!$update_booking->execute()) {
            throw new Exception('Failed to update booking: ' . $update_booking->error);
        }
        
        // Get booking details
        $get_booking = $conn->prepare("SELECT * FROM bookings WHERE id=?");
        $get_booking->bind_param("i", $booking_id);
        if (!$get_booking->execute()) {
            throw new Exception('Failed to get booking: ' . $get_booking->error);
        }
        $booking_result = $get_booking->get_result();
        
        if ($booking_result->num_rows > 0) {
            $booking = $booking_result->fetch_assoc();
            
            // Debug: Log booking data
            error_log("Booking data: " . print_r($booking, true));
            
            // Calculate end time based on hours
            $start_time = date('Y-m-d H:i:s');
            $end_time = date('Y-m-d H:i:s', strtotime("+{$booking['hours']} hours"));
            
            // Create reservation (without slot_id since bookings don't have it)
            // For now, we'll use a default vehicle_id of 1 or NULL since bookings don't store vehicle_id
            $default_vehicle_id = 1; // You might want to get this from user's default vehicle or form data
            $create_reservation = $conn->prepare("INSERT INTO reservations (user_id, vehicle_id, start_time, end_time, status, total_cost, created_at) VALUES (?, ?, ?, ?, 'active', ?, NOW())");
            $create_reservation->bind_param("iissd", 
                $booking['user_id'], 
                $default_vehicle_id, 
                $start_time, 
                $end_time,
                $booking['total_amount']
            );
            
            if (!$create_reservation->execute()) {
                throw new Exception('Failed to create reservation: ' . $create_reservation->error);
            }
            
            // Note: Slot status update removed since bookings don't have slot_id
            
            // Commit transaction
            $conn->commit();
            
            echo json_encode(['success' => true, 'message' => 'Payment successful and reservation created']);
        } else {
            throw new Exception('Booking not found');
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Payment failed: ' . $e->getMessage()]);
    }
    
    exit;
}

// Include header for regular page display
$page_title = "Payment Methods - Smart Parking";
include('../includes/header.php');

// Show booking details if booking_id is present
$booking = null;
if (isset($_GET['booking_id'])) {
    $booking_id = intval($_GET['booking_id']);
    $res = $conn->query("SELECT b.*, l.lot_name, l.location FROM bookings b JOIN parking_lots l ON b.lot_id = l.id WHERE b.id = $booking_id");
    if ($res && $res->num_rows > 0) {
        $booking = $res->fetch_assoc();
    }
}

// Fetch real payment methods for user (assume user_id=1 for demo)
$user_id = 1;
$payment_methods = [];
$res = $conn->query("SELECT * FROM payment_methods WHERE user_id = $user_id");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $payment_methods[] = $row;
    }
}

// Fetch recent transactions for user (assume user_id=1 for demo)
$recent_transactions = [];
$res = $conn->query("SELECT * FROM transactions WHERE user_id = $user_id ORDER BY date DESC LIMIT 5");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $recent_transactions[] = $row;
    }
}

// Fetch wallet balance (assume a wallet_balance column in users table)
$wallet_balance = 0.00;
$res = $conn->query("SELECT wallet_balance FROM users WHERE id = $user_id");
if ($res && $res->num_rows > 0) {
    $wallet_balance = $res->fetch_assoc()['wallet_balance'];
}
?>
<?php if ($booking): ?>
<div class="card" style="margin-bottom:2rem;">
    <div class="card-header">
        <h3><i class="fas fa-parking"></i> Booking Details</h3>
    </div>
    <div class="card-body">
        <p><strong>Lot:</strong> <?php echo htmlspecialchars($booking['lot_name']); ?> (<?php echo htmlspecialchars($booking['location']); ?>)</p>
        <p><strong>Hours:</strong> <?php echo htmlspecialchars($booking['hours']); ?></p>
        <p><strong>Total:</strong> <span style="font-weight:bold; color:#1e40af;">$<?php echo number_format($booking['total_amount'], 2); ?></span></p>
    </div>
</div>

<!-- Payment Method Selection -->
<div class="card" style="margin-bottom:2rem;">
    <div class="card-header">
        <h3><i class="fas fa-money-check-alt"></i> Choose Payment Method</h3>
    </div>
    <div class="card-body">
        <div style="display:flex; gap:1rem;">
            <button class="btn btn-outline" id="payCardBtn" onclick="selectPaymentMethod('card')" type="button"><i class="fas fa-credit-card"></i> Card</button>
            <button class="btn btn-outline" id="payCashBtn" onclick="selectPaymentMethod('cash')" type="button"><i class="fas fa-money-bill-wave"></i> Cash</button>
            <button class="btn btn-outline" id="payUpiBtn" onclick="selectPaymentMethod('upi')" type="button"><i class="fas fa-mobile-alt"></i> UPI</button>
        </div>
    </div>
</div>

<!-- Card Payment Form (hidden by default) -->
<div class="card" id="cardPaymentForm" style="display:none; margin-bottom:2rem;">
    <div class="card-header">
        <h3><i class="fas fa-credit-card"></i> Enter Card Details</h3>
    </div>
    <div class="card-body">
        <form id="cardForm" onsubmit="return handleCardPayment(event)">
            <div class="form-group">
                <label>Card Number</label>
                <input type="text" class="form-control" id="cardNumber" maxlength="19" required placeholder="1234 5678 9012 3456">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="text" class="form-control" id="cardExpiry" maxlength="5" required placeholder="MM/YY">
                </div>
                <div class="form-group">
                    <label>CVV</label>
                    <input type="text" class="form-control" id="cardCVV" maxlength="4" required placeholder="123">
                </div>
            </div>
            <div class="form-group">
                <label>Cardholder Name</label>
                <input type="text" class="form-control" id="cardHolder" required placeholder="John Doe">
            </div>
            <button class="btn btn-primary" type="submit">Pay $<?php echo number_format($booking['total_amount'], 2); ?></button>
        </form>
    </div>
</div>

<!-- Simulated Payment Confirmation -->
<div class="card" id="paymentSuccess" style="display:none; margin-bottom:2rem;">
    <div class="card-header">
        <h3><i class="fas fa-check-circle" style="color:green;"></i> Payment Successful</h3>
    </div>
    <div class="card-body">
        <p>Your reservation and payment have been completed.</p>
        <a href="reservations.php" class="btn btn-primary">View My Reservations</a>
    </div>
</div>

<script>
function selectPaymentMethod(method) {
    document.getElementById('cardPaymentForm').style.display = (method === 'card') ? 'block' : 'none';
    // You can add similar logic for cash/upi if you want to show forms for them
}
function handleCardPayment(e) {
    e.preventDefault();
    var bookingId = <?php echo isset($booking['id']) ? intval($booking['id']) : 'null'; ?>;
    
    if (!bookingId) {
        alert('Invalid booking information. Please try again.');
        return false;
    }
    
    // Show loading state
    var submitBtn = e.target.querySelector('button[type="submit"]');
    var originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    submitBtn.disabled = true;
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    console.log('Response length:', xhr.responseText.length);
                    console.log('Response first 200 chars:', xhr.responseText.substring(0, 200));
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        document.getElementById('cardPaymentForm').style.display = 'none';
                        document.getElementById('paymentSuccess').style.display = 'block';
                        
                        // Show success message
                        var successDiv = document.getElementById('paymentSuccess');
                        var messageDiv = successDiv.querySelector('.card-body p');
                        if (messageDiv && response.message) {
                            messageDiv.textContent = response.message;
                        }
                    } else {
                        alert('Payment failed: ' + (response.message || 'Unknown error'));
                        // Reset button
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', xhr.responseText);
                    alert('Payment failed. Please try again. Error: ' + e.message);
                    // Reset button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            } else {
                alert('Network error. Please check your connection and try again.');
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }
    };
    xhr.send('action=mark_paid&booking_id=' + bookingId);
    return false;
}
</script>
<?php endif; ?>

<div class="page-header">
    <h1><i class="fas fa-credit-card"></i> Payment Methods</h1>
    <p>Manage your payment methods and billing information.</p>
</div>

<!-- Wallet Balance -->
<div class="card wallet-card">
    <div class="card-header">
        <h3><i class="fas fa-wallet"></i> Wallet Balance</h3>
    </div>
    <div class="card-body">
        <div class="wallet-balance">
            <div class="balance-info">
                <div class="balance-amount">
                    <span class="currency">$</span>
                    <span class="amount"><?php echo number_format($wallet_balance, 2); ?></span>
                </div>
                <p>Available balance</p>
            </div>
            <div class="wallet-actions">
                <button class="btn btn-primary" onclick="openModal('addFundsModal')">
                    <i class="fas fa-plus"></i> Add Funds
                </button>
                <button class="btn btn-outline" onclick="viewTransactionHistory()">
                    <i class="fas fa-history"></i> History
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Methods -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <h3><i class="fas fa-credit-card"></i> Saved Cards</h3>
        </div>
        <button class="btn btn-primary" onclick="openModal('addCardModal')">
            <i class="fas fa-plus"></i> Add Card
        </button>
    </div>
    <div class="card-body">
        <div class="payment-methods">
            <?php foreach ($payment_methods as $method): ?>
            <div class="payment-method">
                <div class="card-info">
                    <div class="card-icon">
                        <i class="fab fa-cc-<?php echo $method['type']; ?>"></i>
                    </div>
                    <div class="card-details">
                        <p class="card-number">•••• •••• •••• <?php echo $method['last_four']; ?></p>
                        <p class="card-expiry">Expires <?php echo $method['expiry']; ?></p>
                    </div>
                </div>
                <div class="card-actions">
                    <?php if ($method['is_default']): ?>
                        <span class="default-badge">Default</span>
                    <?php else: ?>
                        <button class="btn btn-sm btn-outline" onclick="setDefaultCard(<?php echo $method['id']; ?>)">
                            Set Default
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-outline" onclick="editCard(<?php echo $method['id']; ?>)">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteCard(<?php echo $method['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-history"></i> Recent Transactions</h3>
        <button class="btn btn-outline" onclick="viewAllTransactions()">
            View All
        </button>
    </div>
    <div class="card-body">
        <div class="transactions-list">
            <?php foreach ($recent_transactions as $transaction): ?>
            <div class="transaction-item">
                <div class="transaction-icon">
                    <i class="fas fa-<?php echo $transaction['amount'] > 0 ? 'plus' : 'minus'; ?>-circle 
                       <?php echo $transaction['amount'] > 0 ? 'text-success' : 'text-danger'; ?>"></i>
                </div>
                <div class="transaction-details">
                    <p class="transaction-description"><?php echo $transaction['description']; ?></p>
                    <p class="transaction-date"><?php echo date('M j, Y', strtotime($transaction['date'])); ?></p>
                </div>
                <div class="transaction-amount <?php echo $transaction['amount'] > 0 ? 'positive' : 'negative'; ?>">
                    <?php echo $transaction['amount'] > 0 ? '+' : ''; ?>$<?php echo number_format(abs($transaction['amount']), 2); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Billing Settings -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-cog"></i> Billing Settings</h3>
    </div>
    <div class="card-body">
        <div class="billing-settings">
            <div class="setting-item">
                <div class="setting-info">
                    <h4>Auto-reload Wallet</h4>
                    <p>Automatically add funds when balance is low</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" checked>
                    <span class="slider"></span>
                </label>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <h4>Email Receipts</h4>
                    <p>Send receipts to your email address</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" checked>
                    <span class="slider"></span>
                </label>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <h4>SMS Notifications</h4>
                    <p>Receive payment notifications via SMS</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox">
                    <span class="slider"></span>
                </label>
            </div>
        </div>
    </div>
</div>

<!-- Add Card Modal -->
<div class="modal" id="addCardModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Payment Method</h3>
            <button class="modal-close" onclick="closeModal('addCardModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="addCardForm">
                <div class="form-group">
                    <label>Card Number</label>
                    <input type="text" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="text" class="form-control" placeholder="MM/YY" maxlength="5" required>
                    </div>
                    <div class="form-group">
                        <label>CVV</label>
                        <input type="text" class="form-control" placeholder="123" maxlength="4" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Cardholder Name</label>
                    <input type="text" class="form-control" placeholder="John Doe" required>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" checked>
                        <span class="checkmark"></span>
                        Set as default payment method
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addCardModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addCard()">Add Card</button>
        </div>
    </div>
</div>

<!-- Add Funds Modal -->
<div class="modal" id="addFundsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Funds to Wallet</h3>
            <button class="modal-close" onclick="closeModal('addFundsModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="current-balance">
                <p>Current Balance: <strong>$<?php echo number_format($wallet_balance, 2); ?></strong></p>
            </div>
            <div class="amount-options">
                <button class="amount-option" onclick="selectAmount(25)">$25</button>
                <button class="amount-option" onclick="selectAmount(50)">$50</button>
                <button class="amount-option" onclick="selectAmount(100)">$100</button>
                <button class="amount-option" onclick="selectAmount(200)">$200</button>
            </div>
            <div class="custom-amount">
                <label>Custom Amount</label>
                <div class="amount-input">
                    <span class="currency">$</span>
                    <input type="number" class="form-control" placeholder="0.00" min="10" max="1000" step="0.01">
                </div>
            </div>
            <div class="payment-method-select">
                <label>Payment Method</label>
                <select class="form-control">
                    <option value="1">•••• •••• •••• 1234 (Default)</option>
                    <option value="2">•••• •••• •••• 5678</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addFundsModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addFunds()">Add Funds</button>
        </div>
    </div>
</div>
 