<?php
require '../config/db.php';
include('../includes/header.php'); 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slot_id = $_POST['slot_id'];
    $user_id = $_SESSION['user_id'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Check if slot already booked for that period
    $check = $conn->query("SELECT * FROM reservations WHERE slot_id = $slot_id AND status = 'active' 
                          AND ('$start_time' BETWEEN start_time AND end_time OR '$end_time' BETWEEN start_time AND end_time)");
    if ($check->num_rows > 0) {
        echo "<script>alert('Slot already booked for the selected time.');</script>";
    } else {
        $conn->query("INSERT INTO reservations (user_id, slot_id, start_time, end_time) 
                      VALUES ('$user_id', '$slot_id', '$start_time', '$end_time')");
        $conn->query("UPDATE slots SET status = 'booked' WHERE id = $slot_id");
        echo "<script>alert('Slot Reserved Successfully'); window.location='reservations.php';</script>";
    }
} else {
    $slot_id = $_GET['slot_id'];
}
?>

<h2>Reserve Parking Slot</h2>
<form action="../bookings/book_slot.php" method="post">
    <label>Choose Lot:</label>
    <select name="lot_id">
        <?php
        $lots = $conn->query("SELECT * FROM parking_lots");
        while ($lot = $lots->fetch_assoc()) {
            echo "<option value='{$lot['id']}'>{$lot['name']}</option>";
        }
        ?>
    </select><br><br>

    <label>Hours to Reserve:</label>
    <input type="number" name="hours" min="1" required><br><br>

    <button type="submit">Continue</button>
</form>

<?php include '../includes/footer.php'; ?>
