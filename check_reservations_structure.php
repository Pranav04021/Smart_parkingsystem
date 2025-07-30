<?php
include('config/db.php');

echo "Reservations table structure:\n";
$result = $conn->query('DESCRIBE reservations');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\nBookings table structure:\n";
$result = $conn->query('DESCRIBE bookings');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
?> 