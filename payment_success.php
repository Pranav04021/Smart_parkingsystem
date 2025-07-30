<?php
include('../config/db.php');
include('../includes/header.php');

$page_title = "Payment Success - Smart Parking";

// Check if required parameters are present
if (!isset($_POST['booking_id']) || !isset($_POST['method'])) {
    echo "<div class='alert alert-danger'>Invalid payment data. Please try again.</div>";
    echo "<a href='lots.php' class='btn btn-primary'>Back to Lots</a>";
    include('../includes/footer.php');
    exit;
}

$booking_id = intval($_POST['booking_id']);
$method = $_POST['method'];

// Start transaction to ensure data consistency
$conn->begin_transaction();

try {
    // Update booking status
    $update_booking = $conn->prepare("UPDATE bookings SET payment_method=?, status='Confirmed' WHERE id=?");
    $update_booking->bind_param("si", $method, $booking_id);
    $update_booking->execute();
    
    if ($update_booking->affected_rows == 0) {
        throw new Exception('Booking not found or already processed');
    }
    
    // Get booking details
    $get_booking = $conn->prepare("SELECT * FROM bookings WHERE id=?");
    $get_booking->bind_param("i", $booking_id);
    $get_booking->execute();
    $booking_result = $get_booking->get_result();
    
    if ($booking_result->num_rows > 0) {
        $booking = $booking_result->fetch_assoc();
        
        // Calculate end time based on hours
        $start_time = date('Y-m-d H:i:s');
        $end_time = date('Y-m-d H:i:s', strtotime("+{$booking['hours']} hours"));
        
        // Create reservation (without slot_id since bookings don't have it)
        $create_reservation = $conn->prepare("INSERT INTO reservations (user_id, vehicle_id, start_time, end_time, status, total_cost, created_at) VALUES (?, ?, ?, ?, 'active', ?, NOW())");
        $create_reservation->bind_param("iissd", 
            $booking['user_id'], 
            $booking['vehicle_id'], 
            $start_time, 
            $end_time,
            $booking['total_amount']
        );
        $create_reservation->execute();
        
        // Note: Slot status update removed since bookings don't have slot_id
        
        // Get lot information for display
        $get_lot = $conn->prepare("SELECT lot_name, location FROM parking_lots WHERE id=?");
        $get_lot->bind_param("i", $booking['lot_id']);
        $get_lot->execute();
        $lot_result = $get_lot->get_result();
        $lot_info = $lot_result->fetch_assoc();
        
        // Commit transaction
        $conn->commit();
        
        // Success page
        ?>
        <div class="page-header">
            <h1><i class="fas fa-check-circle" style="color: green;"></i> Payment Successful</h1>
            <p>Your parking reservation has been confirmed!</p>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-receipt"></i> Booking Confirmation</h3>
            </div>
            <div class="card-body">
                <div class="confirmation-details">
                    <div class="detail-row">
                        <span class="label">Booking ID:</span>
                        <span class="value">#<?php echo $booking_id; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Location:</span>
                        <span class="value"><?php echo htmlspecialchars($lot_info['lot_name']); ?> (<?php echo htmlspecialchars($lot_info['location']); ?>)</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Duration:</span>
                        <span class="value"><?php echo $booking['hours']; ?> hours</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Start Time:</span>
                        <span class="value"><?php echo date('M j, Y h:i A', strtotime($start_time)); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">End Time:</span>
                        <span class="value"><?php echo date('M j, Y h:i A', strtotime($end_time)); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Total Amount:</span>
                        <span class="value amount">$<?php echo number_format($booking['total_amount'], 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Payment Method:</span>
                        <span class="value"><?php echo ucfirst($method); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Status:</span>
                        <span class="value status-confirmed">Confirmed</span>
                    </div>
                </div>
                
                <div class="confirmation-actions">
                    <a href="reservations.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View My Reservations
                    </a>
                    <a href="lots.php" class="btn btn-outline">
                        <i class="fas fa-plus"></i> Book Another Spot
                    </a>
                </div>
            </div>
        </div>
        
        <style>
        .confirmation-details {
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #666;
        }
        .value {
            color: #333;
        }
        .amount {
            font-weight: bold;
            color: #1e40af;
            font-size: 1.1em;
        }
        .status-confirmed {
            color: #059669;
            font-weight: bold;
        }
        .confirmation-actions {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        </style>
        <?php
        
    } else {
        throw new Exception('Booking details not found');
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    ?>
    <div class="page-header">
        <h1><i class="fas fa-exclamation-triangle" style="color: red;"></i> Payment Error</h1>
        <p>There was an issue processing your payment.</p>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-times-circle"></i> Error Details</h3>
        </div>
        <div class="card-body">
            <p><strong>Error:</strong> <?php echo htmlspecialchars($e->getMessage()); ?></p>
            <p>Please contact support if this issue persists.</p>
            
            <div class="error-actions">
                <a href="lots.php" class="btn btn-primary">Try Again</a>
                <a href="reservations.php" class="btn btn-outline">View Reservations</a>
            </div>
        </div>
    </div>
    <?php
}

include('../includes/footer.php');
?>
