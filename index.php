<?php 
$page_title = "Dashboard - Smart Parking";
include('../includes/header.php'); 

// Sample data - in a real application, this would come from a database
$stats = [
    'active_reservations' => 1,
    'total_saved' => 127.50,
    'nearby_lots' => 12,
    'monthly_trips' => 8
];

$current_reservation = [
    'lot_name' => 'Downtown Plaza',
    'spot' => 'A-24',
    'start_time' => '9:00 AM',
    'remaining_hours' => 2
];
?>

<div class="page-header">
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
    <p>Welcome back! Here's your parking overview.</p>
</div>

 

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
    </div>
    <div class="card-body">
        <div class="quick-actions">
            <a href="lots.php" class="action-card bg-blue">
                <i class="fas fa-search"></i>
                <h4>Find Parking</h4>
                <p>Search for available spots near you</p>
            </a>
            
            <a href="reservations.php" class="action-card bg-green">
                <i class="fas fa-calendar-alt"></i>
                <h4>My Bookings</h4>
                <p>View and manage your reservations</p>
            </a>
            
            <a href="payment.php" class="action-card bg-orange">
                <i class="fas fa-credit-card"></i>
                <h4>Payment</h4>
                <p>Manage payment methods and billing</p>
            </a>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-history"></i> Recent Activity</h3>
    </div>
    <div class="card-body">
        <div class="activity-list">
            <div class="activity-item">
                <div class="activity-icon bg-green">
                    <i class="fas fa-check"></i>
                </div>
                <div class="activity-content">
                    <p><strong>Reservation confirmed</strong> at Downtown Plaza</p>
                    <small>Today, 8:45 AM</small>
                </div>
            </div>
            
            <div class="activity-item">
                <div class="activity-icon bg-blue">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="activity-content">
                    <p><strong>Payment processed</strong> - $22.00</p>
                    <small>Today, 8:44 AM</small>
                </div>
            </div>
            
            <div class="activity-item">
                <div class="activity-icon bg-orange">
                    <i class="fas fa-search"></i>
                </div>
                <div class="activity-content">
                    <p><strong>Searched for parking</strong> in Downtown area</p>
                    <small>Today, 8:30 AM</small>
                </div>
            </div>
        </div>
    </div>
</div>
 