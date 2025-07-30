<?php
session_start();
include('../config/db.php');

// Validate required parameters
if (!isset($_POST['lot_id']) || !isset($_POST['hours'])) {
    echo '<div class="alert alert-danger">Missing required booking information.</div>';
    echo '<a href="../user/lots.php" class="btn btn-primary">Back to Lots</a>';
    exit;
}

$user_id = 1; // Demo user
$lot_id = intval($_POST['lot_id']);
$slot_id = isset($_POST['slot_id']) ? intval($_POST['slot_id']) : null;
$vehicle_id = isset($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : null;
$hours = intval($_POST['hours']);

// Validate hours
if ($hours < 1 || $hours > 24) {
    echo '<div class="alert alert-danger">Invalid duration. Please select between 1-24 hours.</div>';
    echo '<a href="../user/lots.php" class="btn btn-primary">Back to Lots</a>';
    exit;
}

// Get lot information and pricing
$lot_query = $conn->prepare("SELECT lot_name, price_per_hour FROM parking_lots WHERE id = ?");
$lot_query->bind_param("i", $lot_id);
$lot_query->execute();
$lot_result = $lot_query->get_result();

if ($lot_result->num_rows === 0) {
    echo '<div class="alert alert-danger">Invalid parking lot selected.</div>';
    echo '<a href="../user/lots.php" class="btn btn-primary">Back to Lots</a>';
    exit;
}

$lot_info = $lot_result->fetch_assoc();
$rate = $lot_info['price_per_hour'] ?? 10; // Default rate if not set
$total = $hours * $rate;

// Check slot availability if slot_id is provided
if ($slot_id) {
    $slot_query = $conn->prepare("SELECT status FROM slots WHERE id = ? AND lot_id = ?");
    $slot_query->bind_param("ii", $slot_id, $lot_id);
    $slot_query->execute();
    $slot_result = $slot_query->get_result();
    
    if ($slot_result->num_rows === 0) {
        echo '<div class="alert alert-danger">Selected slot is not available.</div>';
        echo '<a href="../user/lots.php" class="btn btn-primary">Back to Lots</a>';
        exit;
    }
    
    $slot_info = $slot_result->fetch_assoc();
    if ($slot_info['status'] !== 'available') {
        echo '<div class="alert alert-danger">Selected slot is not available for booking.</div>';
        echo '<a href="../user/lots.php" class="btn btn-primary">Back to Lots</a>';
        exit;
    }
}

// Start transaction to ensure data consistency
$conn->begin_transaction();

try {
    // Save booking (payment pending)
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, lot_id, hours, total_amount, status, created_at) VALUES (?, ?, ?, ?, 'Pending', NOW())");
    $stmt->bind_param("iidd", $user_id, $lot_id, $hours, $total);
    $stmt->execute();
    $booking_id = $stmt->insert_id;
    
    if ($booking_id) {
        // Commit transaction
        $conn->commit();
        
        // Redirect to payment page
        header("Location: ../user/payment.php?booking_id=$booking_id");
        exit;
    } else {
        throw new Exception('Failed to create booking');
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo '<div class="alert alert-danger">Error creating booking: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<a href="../user/lots.php" class="btn btn-primary">Back to Lots</a>';
    exit;
}
?>
