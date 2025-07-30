<?php
require '../config/db.php';
include('../includes/header.php'); 

$lot_id = $_GET['lot_id'];
$lot = $conn->query("SELECT lot_name FROM parking_lots WHERE id = $lot_id")->fetch_assoc();
$slots = $conn->query("SELECT * FROM slots WHERE lot_id = $lot_id");
?>

<h4>Available Slots in <?= $lot['lot_name'] ?></h4>
<ul>
  <?php while ($row = $slots->fetch_assoc()) {
    $booked = ($row['status'] == 'booked') ? ' (Booked)' : '';
    echo "<li>Slot {$row['slot_number']} $booked";
    if ($row['status'] == 'available') {
      echo " <a href='reserve.php?slot_id={$row['id']}'>[Reserve]</a>";
    }
    echo "</li>";
  } ?>
</ul>

<?php include '../includes/footer.php'; ?>
