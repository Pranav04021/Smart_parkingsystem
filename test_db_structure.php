<?php
include('config/db.php');

echo "<h2>Database Structure Test</h2>";

// Check if tables exist
$tables = ['users', 'parking_lots', 'slots', 'bookings', 'reservations', 'vehicles', 'payment_methods', 'transactions'];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        
        // Show table structure
        $columns = $conn->query("DESCRIBE $table");
        echo "<ul>";
        while ($column = $columns->fetch_assoc()) {
            echo "<li>{$column['Field']} - {$column['Type']} ({$column['Null']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
    }
}

// Test sample data
echo "<h3>Sample Data Check</h3>";

// Check users
$users = $conn->query("SELECT COUNT(*) as count FROM users");
if ($users) {
    $user_count = $users->fetch_assoc()['count'];
    echo "<p>Users: $user_count</p>";
} else {
    echo "<p style='color: red;'>Error checking users table</p>";
}

// Check parking lots
$lots = $conn->query("SELECT COUNT(*) as count FROM parking_lots");
if ($lots) {
    $lot_count = $lots->fetch_assoc()['count'];
    echo "<p>Parking Lots: $lot_count</p>";
} else {
    echo "<p style='color: red;'>Error checking parking_lots table</p>";
}

// Check bookings
$bookings = $conn->query("SELECT COUNT(*) as count FROM bookings");
if ($bookings) {
    $booking_count = $bookings->fetch_assoc()['count'];
    echo "<p>Bookings: $booking_count</p>";
} else {
    echo "<p style='color: red;'>Error checking bookings table</p>";
}

// Check reservations
$reservations = $conn->query("SELECT COUNT(*) as count FROM reservations");
if ($reservations) {
    $reservation_count = $reservations->fetch_assoc()['count'];
    echo "<p>Reservations: $reservation_count</p>";
} else {
    echo "<p style='color: red;'>Error checking reservations table</p>";
}

echo "<h3>Test Complete</h3>";
echo "<p><a href='user/lots.php'>Go to User Dashboard</a></p>";
?> 