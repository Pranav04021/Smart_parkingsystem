<?php 
$page_title = "Browse Parking Lots - Smart Parking";
include('../includes/header.php'); 
require_once('../config/db.php');

$parking_lots = [];
$sql = "SELECT * FROM parking_lots";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $lot_id = $row['id'];
        // Count total slots for this lot
        $total_slots = $conn->query("SELECT COUNT(*) as total FROM slots WHERE lot_id = $lot_id")->fetch_assoc()['total'];
        // Count available slots for this lot
        $available_slots = $conn->query("SELECT COUNT(*) as available FROM slots WHERE lot_id = $lot_id AND status = 'available'")->fetch_assoc()['available'];
        $row['total'] = $total_slots;
        $row['available'] = $available_slots;
        $row['features'] = []; // Placeholder for now
        $parking_lots[] = $row;
    }
}
// Fetch vehicles for user (user_id=1 for demo)
$user_id = 1;
$vehicles = [];
$res = $conn->query("SELECT * FROM vehicles WHERE user_id = $user_id");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $vehicles[] = $row;
    }
}
?>

<div class="page-header">
    <h1><i class="fas fa-map-marker-alt"></i> Browse Parking Lots</h1>
    <p>Find the perfect parking spot for your needs.</p>
</div>

<!-- Search and Filters -->
<div class="search-section">
    <div class="search-bar">
        <div class="search-input-group">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search by location, name, or address..." id="searchInput">
        </div>
        <button class="btn btn-primary">Search</button>
    </div>
    
    <div class="filters">
        <select class="filter-select">
            <option value="">Sort by Distance</option>
            <option value="distance">Closest First</option>
            <option value="price">Lowest Price</option>
            <option value="rating">Highest Rated</option>
            <option value="availability">Most Available</option>
        </select>
        
        <select class="filter-select">
            <option value="">Price Range</option>
            <option value="0-5">$0 - $5/hour</option>
            <option value="5-10">$5 - $10/hour</option>
            <option value="10+">$10+/hour</option>
        </select>
        
        <select class="filter-select">
            <option value="">Features</option>
            <option value="covered">Covered</option>
            <option value="security">Security</option>
            <option value="ev">EV Charging</option>
            <option value="valet">Valet Service</option>
        </select>
    </div>
</div>

<!-- Map View Toggle -->
<div class="view-toggle">
    <button class="btn btn-outline active" onclick="toggleView('list')">
        <i class="fas fa-list"></i> List View
    </button>
    <button class="btn btn-outline" onclick="toggleView('map')">
        <i class="fas fa-map"></i> Map View
    </button>
</div>

<!-- Parking Lots Grid -->
<div class="lots-grid" id="lotsGrid">
    <?php foreach ($parking_lots as $lot): ?>
    <div class="lot-card">
        <div class="lot-header">
            <div class="lot-info">
                <h3><?php echo $lot['lot_name']; ?></h3>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo $lot['location']; ?></p>
                <p class="distance"><i class="fas fa-route"></i> <?php echo isset($lot['distance']) ? $lot['distance'] : '-'; ?></p>
            </div>
            <div class="lot-price">
                <span class="price">$<?php echo isset($lot['price_per_hour']) ? number_format($lot['price_per_hour'], 2) : '0.00'; ?></span>
                <small>/hour</small>
            </div>
        </div>
        
        <div class="lot-stats">
            <div class="availability">
                <div class="availability-bar">
                    <div class="availability-fill" style="width: <?php echo ($lot['available'] / max(1, $lot['total'])) * 100; ?>%;"></div>
                </div>
                <span><?php echo $lot['available']; ?> / <?php echo $lot['total']; ?> available</span>
            </div>
            
            <div class="rating">
                <i class="fas fa-star"></i>
                <span><?php echo isset($lot['rating']) ? $lot['rating'] : '-'; ?></span>
            </div>
        </div>
        
        <div class="lot-features">
            <?php if (!empty($lot['features'])): foreach ($lot['features'] as $feature): ?>
            <span class="feature-tag"><?php echo $feature; ?></span>
            <?php endforeach; else: ?>
            <span class="feature-tag">-</span>
            <?php endif; ?>
        </div>
        
        <div class="lot-actions">
            <button class="btn btn-outline" onclick="viewDetails(<?php echo $lot['id']; ?>)">
                <i class="fas fa-info-circle"></i> Details
            </button>
            <button class="btn btn-primary" onclick="reserveSpot(<?php echo $lot['id']; ?>)">
                <i class="fas fa-parking"></i> Reserve Now
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Map View (Hidden by default) -->
<div class="map-container" id="mapContainer" style="display: none;">
    <div class="map-placeholder">
        <i class="fas fa-map"></i>
        <h3>Interactive Map</h3>
        <p>Map integration would be implemented here using Google Maps or similar service.</p>
        <div class="map-features">
            <div class="map-feature">
                <i class="fas fa-map-marker-alt text-blue"></i>
                <span>Your Location</span>
            </div>
            <div class="map-feature">
                <i class="fas fa-parking text-green"></i>
                <span>Available Parking</span>
            </div>
            <div class="map-feature">
                <i class="fas fa-times-circle text-red"></i>
                <span>Full Lots</span>
            </div>
        </div>
    </div>
</div>

<!-- Reservation Modal -->
<div class="modal" id="reservationModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reserve Parking Spot</h3>
            <button class="modal-close" onclick="closeModal('reservationModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="reservationForm" method="post" action="../bookings/book_slot.php">
                <input type="hidden" name="lot_id" id="modalLotId">
                <input type="hidden" name="price" id="modalLotPrice">
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" class="form-control" name="date" id="reservationDate" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" class="form-control" name="start_time" id="reservationStartTime" required>
                    </div>
                    <div class="form-group">
                        <label>Duration</label>
                        <select class="form-control" name="hours" id="reservationDuration" required>
                            <option value="">Select duration</option>
                            <option value="1">1 hour</option>
                            <option value="2">2 hours</option>
                            <option value="4">4 hours</option>
                            <option value="8">8 hours</option>
                            <option value="24">All day</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Slot</label>
                    <select class="form-control" name="slot_id" id="slotSelect" required>
                        <option value="">Loading slots...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Vehicle</label>
                    <select class="form-control" name="vehicle_id" required>
                        <option value="">Select vehicle</option>
                        <?php foreach ($vehicles as $v): ?>
                        <option value="<?php echo $v['id']; ?>">
                            <?php echo htmlspecialchars($v['make'] . ' ' . $v['model'] . ' - ' . $v['license']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="reservation-summary">
                    <h4>Reservation Summary</h4>
                    <div class="summary-row">
                        <span>Duration:</span>
                        <span id="summaryDuration">-</span>
                    </div>
                    <div class="summary-row">
                        <span>Rate:</span>
                        <span id="summaryRate">-</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span id="summaryTotal">$0.00</span>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('reservationModal')" type="button">Cancel</button>
            <button class="btn btn-primary" onclick="submitReservation(event)">Confirm Reservation</button>
        </div>
    </div>
</div>

<script>
let selectedLotPrice = 0;
function reserveSpot(lotId) {
    // Find the lot price from the PHP array (rendered as JS object)
    const lot = window.lotsData.find(l => l.id == lotId);
    selectedLotPrice = lot && lot.price ? parseFloat(lot.price) : 0;
    document.getElementById('modalLotId').value = lotId;
    document.getElementById('modalLotPrice').value = selectedLotPrice;
    document.getElementById('summaryDuration').innerText = '-';
    document.getElementById('summaryRate').innerText = selectedLotPrice ? `$${selectedLotPrice.toFixed(2)}/hr` : '-';
    document.getElementById('summaryTotal').innerText = '$0.00';
    document.getElementById('reservationDuration').value = '';
    // Fetch available slots for this lot
    fetch('get_slots.php?lot_id=' + lotId)
        .then(res => res.json())
        .then(slots => {
            const slotSelect = document.getElementById('slotSelect');
            slotSelect.innerHTML = '';
            if (slots.length === 0) {
                slotSelect.innerHTML = '<option value="">No available slots</option>';
            } else {
                slotSelect.innerHTML = '<option value="">Select slot</option>';
                slots.forEach(slot => {
                    slotSelect.innerHTML += `<option value="${slot.id}">${slot.slot_number}</option>`;
                });
            }
        });
    document.getElementById('reservationModal').classList.add('active');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
document.getElementById('reservationDuration').addEventListener('change', function() {
    const duration = parseInt(this.value);
    document.getElementById('summaryDuration').innerText = duration ? `${duration} hour${duration > 1 ? 's' : ''}` : '-';
    document.getElementById('summaryRate').innerText = selectedLotPrice ? `$${selectedLotPrice.toFixed(2)}/hr` : '-';
    const total = duration && selectedLotPrice ? (duration * selectedLotPrice) : 0;
    document.getElementById('summaryTotal').innerText = `$${total.toFixed(2)}`;
});
function submitReservation(e) {
    e.preventDefault();
    document.getElementById('reservationForm').submit();
}
// Expose lots data to JS
window.lotsData = <?php echo json_encode(array_map(function($lot) {
    return [
        'id' => $lot['id'],
        'price' => isset($lot['price_per_hour']) ? $lot['price_per_hour'] : 0
    ];
}, $parking_lots)); ?>;
</script>