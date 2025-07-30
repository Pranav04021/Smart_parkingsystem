<?php
require_once('../config/db.php');
header('Content-Type: application/json');
$lot_id = isset($_GET['lot_id']) ? intval($_GET['lot_id']) : 0;
$slots = [];
if ($lot_id) {
    $res = $conn->query("SELECT id, slot_number FROM slots WHERE lot_id = $lot_id AND status = 'available'");
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $slots[] = $row;
        }
    }
}
echo json_encode($slots); 