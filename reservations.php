<?php 
$page_title = "My Reservations - Smart Parking";
include('../includes/header.php'); 
require_once('../config/db.php');

// For demo, use user_id=1
$user_id = 1;
$reservations = [];

// Improved query with better error handling and fallback for missing slot information
$query = "SELECT r.*, 
                 COALESCE(pl.lot_name, 'General Parking') as lot_name,
                 COALESCE(pl.location, 'Location not specified') as location,
                 COALESCE(s.slot_number, 'N/A') as slot_number,
                 v.make, v.model, v.license 
          FROM reservations r 
          LEFT JOIN slots s ON r.slot_id = s.id 
          LEFT JOIN parking_lots pl ON s.lot_id = pl.id 
          LEFT JOIN vehicles v ON r.vehicle_id = v.id 
          WHERE r.user_id = ? 
          ORDER BY r.start_time DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
}
function getStatusClass($status) {
    switch($status) {
        case 'active': return 'status-active';
        case 'upcoming': return 'status-upcoming';
        case 'completed': return 'status-completed';
        default: return 'status-default';
    }
}
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-clock"></i> My Reservations</h1>
        <p>Manage your parking reservations and history.</p>
    </div>
    <button class="btn btn-primary" onclick="window.location.href='lots.php'">
        <i class="fas fa-plus"></i> New Reservation
    </button>
</div>

<!-- Reservation Filters -->
<div class="reservation-filters">
    <div class="filter-tabs">
        <button class="filter-tab active" onclick="filterReservations('all')">
            All Reservations
        </button>
        <button class="filter-tab" onclick="filterReservations('active')">
            Active
        </button>
        <button class="filter-tab" onclick="filterReservations('upcoming')">
            Upcoming
        </button>
        <button class="filter-tab" onclick="filterReservations('completed')">
            Completed
        </button>
    </div>
    
    <div class="filter-controls">
        <input type="date" class="form-control" placeholder="Filter by date">
        <select class="form-control">
            <option value="">All Locations</option>
            <option value="downtown">Downtown Plaza</option>
            <option value="mall">Central Mall</option>
            <option value="business">Business District</option>
            <option value="airport">Airport Terminal</option>
        </select>
    </div>
</div>

<!-- Reservations List -->
<?php if (empty($reservations)): ?>
<div class="empty-state">
    <i class="fas fa-calendar-times"></i>
    <h3>No reservations found</h3>
    <p>You don't have any parking reservations yet. Start by booking a parking spot!</p>
    <button class="btn btn-primary" onclick="window.location.href='lots.php'">
        <i class="fas fa-plus"></i> Make a Reservation
    </button>
</div>
<?php else: ?>
<div class="reservations-list">
    <?php foreach ($reservations as $reservation): ?>
    <div class="reservation-card" data-status="<?php echo $reservation['status']; ?>">
        <div class="reservation-header">
            <div class="reservation-info">
                <h3><?php echo $reservation['lot_name']; ?></h3>
                <p><i class="fas fa-parking"></i> Spot <?php echo $reservation['slot_number']; ?></p>
            </div>
            <div class="reservation-status">
                <span class="status-badge <?php echo getStatusClass($reservation['status']); ?>">
                    <?php echo ucfirst($reservation['status']); ?>
                </span>
            </div>
        </div>
        
        <div class="reservation-details">
            <div class="detail-item">
                <i class="fas fa-calendar"></i>
                <div>
                    <label>Date</label>
                    <span><?php echo isset($reservation['start_time']) ? date('M j, Y', strtotime($reservation['start_time'])) : '-'; ?></span>
                </div>
            </div>
            
            <div class="detail-item">
                <i class="fas fa-clock"></i>
                <div>
                    <label>Time</label>
                    <span><?php echo isset($reservation['start_time']) ? date('h:i A', strtotime($reservation['start_time'])) : '-'; ?></span>
                </div>
            </div>
            
            <div class="detail-item">
                <i class="fas fa-hourglass-half"></i>
                <div>
                    <label>Duration</label>
                    <span>
                        <?php
                        if (isset($reservation['start_time'], $reservation['end_time'])) {
                            $hours = round((strtotime($reservation['end_time'])-strtotime($reservation['start_time']))/3600);
                            echo $hours > 0 ? $hours . ' hours' : '-';
                        } else {
                            echo '-';
                        }
                        ?>
                    </span>
                </div>
            </div>
            
            <div class="detail-item">
                <i class="fas fa-car"></i>
                <div>
                    <label>Vehicle</label>
                    <span><?php echo ($reservation['make'] ?? '-') . ' ' . ($reservation['model'] ?? '') . ' - ' . ($reservation['license'] ?? ''); ?></span>
                </div>
            </div>
            
            <div class="detail-item">
                <i class="fas fa-dollar-sign"></i>
                <div>
                    <label>Amount</label>
                    <span>$<?php echo isset($reservation['total_cost']) ? number_format($reservation['total_cost'], 2) : '0.00'; ?></span>
                </div>
            </div>
        </div>
        
        <div class="reservation-actions">
            <?php if ($reservation['status'] === 'active'): ?>
                <button class="btn btn-outline" onclick="extendReservation(<?php echo $reservation['id']; ?>)">
                    <i class="fas fa-clock"></i> Extend
                </button>
                <button class="btn btn-primary" onclick="getDirections(<?php echo $reservation['id']; ?>)">
                    <i class="fas fa-directions"></i> Directions
                </button>
            <?php elseif ($reservation['status'] === 'upcoming'): ?>
                <button class="btn btn-outline" onclick="modifyReservation(<?php echo $reservation['id']; ?>)">
                    <i class="fas fa-edit"></i> Modify
                </button>
                <button class="btn btn-danger" onclick="cancelReservation(<?php echo $reservation['id']; ?>)">
                    <i class="fas fa-times"></i> Cancel
                </button>
            <?php else: ?>
                <button class="btn btn-outline" onclick="viewReceipt(<?php echo $reservation['id']; ?>)">
                    <i class="fas fa-receipt"></i> Receipt
                </button>
                <button class="btn btn-primary" onclick="rebookReservation(<?php echo $reservation['id']; ?>)">
                    <i class="fas fa-redo"></i> Book Again
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Empty State (hidden by default) -->
<div class="empty-state" id="emptyState" style="display: none;">
    <i class="fas fa-calendar-times"></i>
    <h3>No reservations found</h3>
    <p>You don't have any reservations matching the selected criteria.</p>
    <button class="btn btn-primary" onclick="window.location.href='lots.php'">
        <i class="fas fa-plus"></i> Make a Reservation
    </button>
</div>

<!-- Extend Reservation Modal -->
<div class="modal" id="extendModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Extend Reservation</h3>
            <button class="modal-close" onclick="closeModal('extendModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>How much additional time do you need?</p>
            <div class="extend-options">
                <button class="extend-option" onclick="selectExtension(1)">
                    <span class="time">+1 Hour</span>
                    <span class="cost">$5.50</span>
                </button>
                <button class="extend-option" onclick="selectExtension(2)">
                    <span class="time">+2 Hours</span>
                    <span class="cost">$11.00</span>
                </button>
                <button class="extend-option" onclick="selectExtension(4)">
                    <span class="time">+4 Hours</span>
                    <span class="cost">$22.00</span>
                </button>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('extendModal')">Cancel</button>
            <button class="btn btn-primary" onclick="confirmExtension()">Confirm Extension</button>
        </div>
    </div>
</div>

<script>
// Filter reservations by status
function filterReservations(status) {
    const cards = document.querySelectorAll('.reservation-card');
    const tabs = document.querySelectorAll('.filter-tab');
    
    // Update active tab
    tabs.forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');
    
    // Show/hide cards based on status
    cards.forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show empty state if no cards are visible
    const visibleCards = document.querySelectorAll('.reservation-card[style="display: block"]');
    const emptyState = document.getElementById('emptyState');
    if (visibleCards.length === 0) {
        emptyState.style.display = 'block';
    } else {
        emptyState.style.display = 'none';
    }
}

// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Reservation action functions
function extendReservation(reservationId) {
    openModal('extendModal');
    // Store reservation ID for later use
    window.currentReservationId = reservationId;
}

function selectExtension(hours) {
    // Remove active class from all options
    document.querySelectorAll('.extend-option').forEach(option => {
        option.classList.remove('active');
    });
    // Add active class to selected option
    event.target.closest('.extend-option').classList.add('active');
    window.selectedExtensionHours = hours;
}

function confirmExtension() {
    if (!window.selectedExtensionHours) {
        alert('Please select an extension time.');
        return;
    }
    
    // Here you would typically make an AJAX call to extend the reservation
    alert('Extension request submitted. You will be charged for the additional time.');
    closeModal('extendModal');
}

function modifyReservation(reservationId) {
    alert('Modify reservation functionality would be implemented here.');
}

function cancelReservation(reservationId) {
    if (confirm('Are you sure you want to cancel this reservation?')) {
        // Here you would typically make an AJAX call to cancel the reservation
        alert('Reservation cancelled successfully.');
        location.reload();
    }
}

function viewReceipt(reservationId) {
    alert('Receipt view functionality would be implemented here.');
}

function rebookReservation(reservationId) {
    window.location.href = 'lots.php';
}

function getDirections(reservationId) {
    alert('Directions functionality would be implemented here.');
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script> 
 