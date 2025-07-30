<?php 
$page_title = "Profile Settings - Smart Parking";
include('../includes/header.php'); 
require_once('../config/db.php');

// For demo, use user_id=1
$user_id = 1;
// Fetch user info
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
// Fetch vehicles
$vehicles = [];
$res = $conn->query("SELECT * FROM vehicles WHERE user_id = $user_id");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $vehicles[] = $row;
    }
}
// Calculate total reservations and total saved
$total_reservations = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE user_id = $user_id")->fetch_assoc()['total'];
$total_saved = $conn->query("SELECT SUM(total_amount) as saved FROM bookings WHERE user_id = $user_id AND status = 'Confirmed'")->fetch_assoc()['saved'] ?? 0;
$user['total_reservations'] = $total_reservations;
$user['total_saved'] = $total_saved;
?>

<div class="page-header">
    <h1><i class="fas fa-user"></i> Profile Settings</h1>
    <p>Manage your account information and preferences.</p>
</div>

<!-- Profile Overview -->
<div class="card profile-overview">
    <div class="card-body">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
                <button class="avatar-edit" onclick="changeAvatar()">
                    <i class="fas fa-camera"></i>
                </button>
            </div>
            <div class="profile-info">
                <h2><?php echo $user['name']; ?></h2>
                <p><?php echo $user['email']; ?></p>
                <div class="profile-stats">
                    <div class="stat">
                        <span class="stat-value"><?php echo $user['total_reservations']; ?></span>
                        <span class="stat-label">Total Reservations</span>
                    </div>
                     
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Personal Information -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-user-edit"></i> Personal Information</h3>
        <button class="btn btn-outline" onclick="editProfile()">
            <i class="fas fa-edit"></i> Edit
        </button>
    </div>
    <div class="card-body">
        <form id="profileForm">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" class="form-control" value="<?php echo $user['name']; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" class="form-control" value="<?php echo $user['email']; ?>" readonly>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" class="form-control" value="<?php echo $user['phone']; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" class="form-control" value="1990-05-15" readonly>
                </div>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea class="form-control" rows="2" readonly><?php echo $user['address']; ?></textarea>
            </div>
            <div class="form-actions" id="profileActions" style="display: none;">
                <button type="button" class="btn btn-outline" onclick="cancelEdit()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Vehicles -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-car"></i> My Vehicles</h3>
        <button class="btn btn-primary" onclick="openModal('addVehicleModal')">
            <i class="fas fa-plus"></i> Add Vehicle
        </button>
    </div>
    <div class="card-body">
        <div class="vehicles-list">
            <?php foreach ($vehicles as $vehicle): ?>
            <div class="vehicle-card">
                <div class="vehicle-icon">
                    <i class="fas fa-car"></i>
                </div>
                <div class="vehicle-info">
                    <h4><?php echo $vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model']; ?></h4>
                    <p>License: <?php echo $vehicle['license']; ?> • Color: <?php echo $vehicle['color']; ?></p>
                </div>
                <div class="vehicle-actions">
                    <?php if ($vehicle['is_default']): ?>
                        <span class="default-badge">Default</span>
                    <?php else: ?>
                        <button class="btn btn-sm btn-outline" onclick="setDefaultVehicle(<?php echo $vehicle['id']; ?>)">
                            Set Default
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-outline" onclick="editVehicle(<?php echo $vehicle['id']; ?>)">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteVehicle(<?php echo $vehicle['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Preferences -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-cog"></i> Preferences</h3>
    </div>
    <div class="card-body">
        <div class="preferences-list">
            <div class="preference-item">
                <div class="preference-info">
                    <h4>Email Notifications</h4>
                    <p>Receive booking confirmations and reminders</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" checked>
                    <span class="slider"></span>
                </label>
            </div>
            
            <div class="preference-item">
                <div class="preference-info">
                    <h4>SMS Notifications</h4>
                    <p>Get text messages for important updates</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox">
                    <span class="slider"></span>
                </label>
            </div>
            
            <div class="preference-item">
                <div class="preference-info">
                    <h4>Push Notifications</h4>
                    <p>Receive mobile app notifications</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" checked>
                    <span class="slider"></span>
                </label>
            </div>
            
            <div class="preference-item">
                <div class="preference-info">
                    <h4>Marketing Emails</h4>
                    <p>Receive promotional offers and updates</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox">
                    <span class="slider"></span>
                </label>
            </div>
            
            <div class="preference-item">
                <div class="preference-info">
                    <h4>Auto-extend Reservations</h4>
                    <p>Automatically extend when running late</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" checked>
                    <span class="slider"></span>
                </label>
            </div>
        </div>
    </div>
</div>

<!-- Security -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-shield-alt"></i> Security</h3>
    </div>
    <div class="card-body">
        <div class="security-options">
            <div class="security-item">
                <div class="security-info">
                    <h4>Change Password</h4>
                    <p>Update your account password</p>
                </div>
                <button class="btn btn-outline" onclick="openModal('changePasswordModal')">
                    Change Password
                </button>
            </div>
            
            <div class="security-item">
                <div class="security-info">
                    <h4>Two-Factor Authentication</h4>
                    <p>Add an extra layer of security to your account</p>
                </div>
                <button class="btn btn-primary" onclick="setup2FA()">
                    Enable 2FA
                </button>
            </div>
            
            <div class="security-item">
                <div class="security-info">
                    <h4>Login History</h4>
                    <p>View recent login activity</p>
                </div>
                <button class="btn btn-outline" onclick="viewLoginHistory()">
                    View History
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Vehicle Modal -->
<div class="modal" id="addVehicleModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Vehicle</h3>
            <button class="modal-close" onclick="closeModal('addVehicleModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="addVehicleForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Make</label>
                        <input type="text" class="form-control" placeholder="Honda" required>
                    </div>
                    <div class="form-group">
                        <label>Model</label>
                        <input type="text" class="form-control" placeholder="Civic" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Year</label>
                        <input type="number" class="form-control" placeholder="2020" min="1900" max="2025" required>
                    </div>
                    <div class="form-group">
                        <label>Color</label>
                        <input type="text" class="form-control" placeholder="Blue" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>License Plate</label>
                    <input type="text" class="form-control" placeholder="ABC123" required>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox">
                        <span class="checkmark"></span>
                        Set as default vehicle
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addVehicleModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addVehicle()">Add Vehicle</button>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal" id="changePasswordModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Change Password</h3>
            <button class="modal-close" onclick="closeModal('changePasswordModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="changePasswordForm">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" class="form-control" required>
                </div>
                <div class="password-requirements">
                    <p>Password must contain:</p>
                    <ul>
                        <li>At least 8 characters</li>
                        <li>One uppercase letter</li>
                        <li>One lowercase letter</li>
                        <li>One number</li>
                        <li>One special character</li>
                    </ul>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('changePasswordModal')">Cancel</button>
            <button class="btn btn-primary" onclick="changePassword()">Change Password</button>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>