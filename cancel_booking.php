<?php
include('../config/db.php');
$booking_id = $_POST['booking_id'];
$conn->query("UPDATE bookings SET status='Cancelled' WHERE id=$booking_id");
header("Location: ../user/reservations.php");
exit();
