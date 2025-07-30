<?php
require '../config/db.php';

// Handle update operation
if (isset($_POST['update'])) {
    $id = intval($_POST['edit_id']);
    $lot = $_POST['lot'];
    $slot = $_POST['slot'];
    $status = $_POST['status'];
    $type = $_POST['type'];
    $stmt = $conn->prepare("UPDATE slots SET lot_id=?, slot_number=?, status=?, slot_type=? WHERE id=?");
    $stmt->bind_param("isssi", $lot, $slot, $status, $type, $id);
    if ($stmt->execute()) {
        $success_message = "Slot updated successfully!";
    } else {
        $error_message = "Error updating slot: " . $conn->error;
    }
    $stmt->close();
}

// Handle form submission (add new)
if (isset($_POST['submit'])) {
    $lot = $_POST['lot'];
    $slot = $_POST['slot'];
    $status = $_POST['status'] ?? 'available';
    $type = $_POST['type'] ?? 'regular';
    $stmt = $conn->prepare("INSERT INTO slots (lot_id, slot_number, status, slot_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $lot, $slot, $status, $type);
    if ($stmt->execute()) {
        $success_message = "Parking slot added successfully!";
    } else {
        $error_message = "Error adding parking slot: " . $conn->error;
    }
    $stmt->close();
}

// Handle delete operation
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM slots WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success_message = "Parking slot deleted successfully!";
    } else {
        $error_message = "Error deleting parking slot: " . $conn->error;
    }
    $stmt->close();
}

// Handle status update
if (isset($_POST['update_status'])) {
    $slot_id = $_POST['slot_id'];
    $new_status = $_POST['new_status'];
    $stmt = $conn->prepare("UPDATE slots SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $slot_id);
    if ($stmt->execute()) {
        $success_message = "Slot status updated successfully!";
    } else {
        $error_message = "Error updating slot status: " . $conn->error;
    }
    $stmt->close();
}

// Handle edit request (fetch slot data)
$edit_slot = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM slots WHERE id = $edit_id");
    if ($res && $res->num_rows > 0) {
        $edit_slot = $res->fetch_assoc();
    }
}

// Get current page for navigation
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch parking lots for dropdown
$lots = $conn->query("SELECT * FROM parking_lots ORDER BY lot_name");

// Fetch all slots with lot information
$slots = $conn->query("SELECT slots.*, parking_lots.lot_name, parking_lots.location 
                      FROM slots 
                      JOIN parking_lots ON slots.lot_id = parking_lots.id 
                      ORDER BY parking_lots.lot_name, slots.slot_number");

// Get slot statistics
$stats_query = $conn->query("SELECT 
    COUNT(*) as total_slots,
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_slots,
    SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_slots,
    SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_slots
    FROM slots");
$stats = $stats_query->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Slots - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f3f4f6;
            color: #374151;
            line-height: 1.6;
        }

        /* Dashboard container */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar styles */
        .sidebar {
            background-color: #ffffff;
            width: 256px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: width 0.3s ease-in-out;
            position: relative;
            z-index: 10;
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .sidebar-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            transition: all 0.3s ease;
        }

        .sidebar.collapsed .sidebar-title {
            font-size: 0.875rem;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            padding: 0.25rem;
            border-radius: 50%;
            cursor: pointer;
            color: #6b7280;
            transition: background-color 0.2s ease;
        }

        .sidebar-toggle:hover {
            background-color: #f3f4f6;
        }

        .sidebar.collapsed .sidebar-toggle i {
            transform: rotate(180deg);
        }

        .sidebar-nav {
            padding: 1rem;
        }

        .nav-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            color: #374151;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            position: relative;
        }

        .nav-link:hover {
            background-color: #f3f4f6;
            color: #1f2937;
        }

        .nav-link.active {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .nav-link.active::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background-color: #3b82f6;
            border-radius: 0 3px 3px 0;
        }

        .nav-icon {
            width: 20px;
            height: 20px;
            color: #6b7280;
            flex-shrink: 0;
        }

        .nav-link.active .nav-icon {
            color: #1d4ed8;
        }

        .nav-text {
            transition: opacity 0.3s ease;
        }

        .sidebar.collapsed .nav-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        /* Main content styles */
        .main-content {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
        }

        .content-header {
            margin-bottom: 1.5rem;
        }

        .page-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 1rem;
        }

        /* Stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background-color: #ffffff;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-info h3 {
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }

        .stat-info p {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1f2937;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.total { background-color: #dbeafe; color: #2563eb; }
        .stat-icon.available { background-color: #d1fae5; color: #059669; }
        .stat-icon.occupied { background-color: #fee2e2; color: #dc2626; }
        .stat-icon.maintenance { background-color: #fef3c7; color: #d97706; }

        /* Alert messages */
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        /* Form styles */
        .form-container {
            background-color: #ffffff;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            margin-bottom: 1.5rem;
        }

        .form-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-input, .form-select {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            background-color: #ffffff;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-input:hover, .form-select:hover {
            border-color: #9ca3af;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
        }

        .btn-primary {
            background-color: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
        }

        .btn-danger {
            background-color: #ef4444;
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }

        .btn-warning {
            background-color: #f59e0b;
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            margin-right: 0.5rem;
        }

        .btn-warning:hover {
            background-color: #d97706;
        }

        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
        }

        /* Table styles */
        .table-container {
            background-color: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .table td {
            color: #6b7280;
        }

        .table tbody tr:hover {
            background-color: #f9fafb;
        }

        /* Status badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-available {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-occupied {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-maintenance {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-reserved {
            background-color: #e0e7ff;
            color: #3730a3;
        }

        /* Type badges */
        .type-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            background-color: #f3f4f6;
            color: #374151;
        }

        .type-vip {
            background-color: #fbbf24;
            color: #92400e;
        }

        .type-disabled {
            background-color: #60a5fa;
            color: #1e40af;
        }

        /* Quick status update */
        .status-update-form {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-select {
            padding: 0.25rem 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.75rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #d1d5db;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
            }

            .sidebar.collapsed {
                width: 100%;
            }

            .main-content {
                padding: 1rem;
            }

            .form-grid, .stats-grid {
                grid-template-columns: 1fr;
            }

            .table-container {
                overflow-x: auto;
            }

            .table {
                min-width: 800px;
            }
        }

        /* Loading animation */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1 class="sidebar-title" id="sidebarTitle">Admin Dashboard</h1>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-chevron-left" id="toggleIcon"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">
                            <i class="fas fa-tachometer-alt nav-icon"></i>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="manage_lots.php" class="nav-link">
                            <i class="fas fa-parking nav-icon"></i>
                            <span class="nav-text">Manage Lots</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="manage_slots.php" class="nav-link active">
                            <i class="fas fa-th-large nav-icon"></i>
                            <span class="nav-text">Manage Slots</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="view_reservations.php" class="nav-link">
                            <i class="fas fa-calendar-alt nav-icon"></i>
                            <span class="nav-text">View Reservations</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h2 class="page-title">Manage Parking Slots</h2>
                <p class="page-subtitle">Configure parking slots within lots and manage their status</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <h3>Total Slots</h3>
                            <p><?php echo $stats['total_slots'] ?? 0; ?></p>
                        </div>
                        <div class="stat-icon total">
                            <i class="fas fa-th-large"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <h3>Available</h3>
                            <p><?php echo $stats['available_slots'] ?? 0; ?></p>
                        </div>
                        <div class="stat-icon available">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <h3>Occupied</h3>
                            <p><?php echo $stats['occupied_slots'] ?? 0; ?></p>
                        </div>
                        <div class="stat-icon occupied">
                            <i class="fas fa-car"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <h3>Maintenance</h3>
                            <p><?php echo $stats['maintenance_slots'] ?? 0; ?></p>
                        </div>
                        <div class="stat-icon maintenance">
                            <i class="fas fa-tools"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Add/Edit Slot Form -->
            <?php if ($edit_slot): ?>
            <div class="form-container">
                <h3 class="form-title"><i class="fas fa-edit"></i> Edit Parking Slot</h3>
                <form method="post" id="editSlotForm">
                    <input type="hidden" name="edit_id" value="<?php echo $edit_slot['id']; ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="lot">Parking Lot</label>
                            <select id="lot" name="lot" class="form-select" required>
                                <?php 
                                $lots2 = $conn->query("SELECT * FROM parking_lots ORDER BY lot_name");
                                while ($l = $lots2->fetch_assoc()) {
                                    $selected = ($l['id'] == $edit_slot['lot_id']) ? 'selected' : '';
                                    echo "<option value='{$l['id']}' $selected>{$l['lot_name']} - {$l['location']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="slot">Slot Number</label>
                            <input type="text" id="slot" name="slot" class="form-input" value="<?php echo htmlspecialchars($edit_slot['slot_number']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="available" <?php if ($edit_slot['status'] == 'available') echo 'selected'; ?>>Available</option>
                                <option value="occupied" <?php if ($edit_slot['status'] == 'occupied') echo 'selected'; ?>>Occupied</option>
                                <option value="maintenance" <?php if ($edit_slot['status'] == 'maintenance') echo 'selected'; ?>>Maintenance</option>
                                <option value="reserved" <?php if ($edit_slot['status'] == 'reserved') echo 'selected'; ?>>Reserved</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="type">Slot Type</label>
                            <select id="type" name="type" class="form-select">
                                <option value="regular" <?php if ($edit_slot['slot_type'] == 'regular') echo 'selected'; ?>>Regular</option>
                                <option value="vip" <?php if ($edit_slot['slot_type'] == 'vip') echo 'selected'; ?>>VIP</option>
                                <option value="disabled" <?php if ($edit_slot['slot_type'] == 'disabled') echo 'selected'; ?>>Disabled Access</option>
                                <option value="electric" <?php if ($edit_slot['slot_type'] == 'electric') echo 'selected'; ?>>Electric Vehicle</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="update" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Slot
                    </button>
                    <a href="manage_slots.php" class="btn btn-danger">Cancel</a>
                </form>
            </div>
            <?php else: ?>
            <!-- Existing Add New Parking Slot form here -->
            <div class="form-container">
                <h3 class="form-title">
                    <i class="fas fa-plus-circle"></i>
                    Add New Parking Slot
                </h3>
                
                <form method="post" id="slotForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="lot">
                                <i class="fas fa-parking"></i> Parking Lot
                            </label>
                            <select id="lot" name="lot" class="form-select" required>
                                <option value="">Select a parking lot</option>
                                <?php 
                                if ($lots && $lots->num_rows > 0) {
                                    while ($l = $lots->fetch_assoc()) {
                                        echo "<option value='{$l['id']}'>{$l['lot_name']} - {$l['location']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="slot">
                                <i class="fas fa-hashtag"></i> Slot Number
                            </label>
                            <input 
                                type="text" 
                                id="slot"
                                name="slot" 
                                class="form-input" 
                                placeholder="Enter slot number (e.g., A-01)" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="status">
                                <i class="fas fa-info-circle"></i> Status
                            </label>
                            <select id="status" name="status" class="form-select">
                                <option value="available">Available</option>
                                <option value="occupied">Occupied</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="reserved">Reserved</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="type">
                                <i class="fas fa-tag"></i> Slot Type
                            </label>
                            <select id="type" name="type" class="form-select">
                                <option value="regular">Regular</option>
                                <option value="vip">VIP</option>
                                <option value="disabled">Disabled Access</option>
                                <option value="electric">Electric Vehicle</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Parking Slot
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Parking Slots Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">
                        <i class="fas fa-list"></i>
                        Existing Parking Slots
                    </h3>
                </div>

                <?php if ($slots && $slots->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Slot ID</th>
                                <th>Lot Name</th>
                                <th>Location</th>
                                <th>Slot Number</th>
                                <th>Status</th>
                                <th>Type</th>
                                <th>Quick Update</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($s = $slots->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s['id']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($s['lot_name']); ?></strong>
                                    </td>
                                    <td>
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($s['location']); ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($s['slot_number']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $s['status']; ?>">
                                            <?php echo ucfirst($s['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="type-badge <?php echo ($s['slot_type'] == 'vip' || $s['slot_type'] == 'disabled') ? 'type-' . $s['slot_type'] : ''; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $s['slot_type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="post" class="status-update-form">
                                            <input type="hidden" name="slot_id" value="<?php echo $s['id']; ?>">
                                            <select name="new_status" class="status-select" onchange="this.form.submit()">
                                                <option value="available" <?php echo ($s['status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                                                <option value="occupied" <?php echo ($s['status'] == 'occupied') ? 'selected' : ''; ?>>Occupied</option>
                                                <option value="maintenance" <?php echo ($s['status'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                                <option value="reserved" <?php echo ($s['status'] == 'reserved') ? 'selected' : ''; ?>>Reserved</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-primary">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="manage_slots.php?edit=<?php echo $s['id']; ?>" class="btn btn-warning">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?delete=<?php echo $s['id']; ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this parking slot?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-th-large"></i>
                        <h3>No Parking Slots Found</h3>
                        <p>Start by adding your first parking slot using the form above.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Sidebar functionality
            const sidebar = document.getElementById("sidebar");
            const sidebarToggle = document.getElementById("sidebarToggle");
            const sidebarTitle = document.getElementById("sidebarTitle");
            const toggleIcon = document.getElementById("toggleIcon");

            // Load saved sidebar state
            const savedState = localStorage.getItem("sidebarCollapsed");
            if (savedState === "true") {
                sidebar.classList.add("collapsed");
                sidebarTitle.textContent = "Admin";
                toggleIcon.classList.remove("fa-chevron-left");
                toggleIcon.classList.add("fa-chevron-right");
            }

            // Toggle sidebar
            sidebarToggle.addEventListener("click", function() {
                sidebar.classList.toggle("collapsed");

                if (sidebar.classList.contains("collapsed")) {
                    sidebarTitle.textContent = "Admin";
                    toggleIcon.classList.remove("fa-chevron-left");
                    toggleIcon.classList.add("fa-chevron-right");
                    localStorage.setItem("sidebarCollapsed", "true");
                } else {
                    sidebarTitle.textContent = "Admin Dashboard";
                    toggleIcon.classList.remove("fa-chevron-right");
                    toggleIcon.classList.add("fa-chevron-left");
                    localStorage.setItem("sidebarCollapsed", "false");
                }
            });

            // Form validation and enhancement
            const form = document.getElementById("slotForm");
            const submitBtn = form.querySelector('button[type="submit"]');

            form.addEventListener("submit", function(e) {
                // Add loading state
                submitBtn.classList.add("loading");
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
                
                // Basic validation
                const lot = document.getElementById("lot").value;
                const slot = document.getElementById("slot").value.trim();

                if (!lot || !slot) {
                    e.preventDefault();
                    alert("Please fill in all required fields");
                    submitBtn.classList.remove("loading");
                    submitBtn.innerHTML = '<i class="fas fa-plus"></i> Add Parking Slot';
                    return;
                }
            });

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll(".alert");
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = "0";
                    alert.style.transform = "translateY(-10px)";
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }, 5000);
            });

            // Keyboard shortcuts
            document.addEventListener("keydown", function(e) {
                // Ctrl+B to toggle sidebar
                if (e.ctrlKey && e.key === "b") {
                    e.preventDefault();
                    sidebarToggle.click();
                }
                
                // Ctrl+N to focus on lot select
                if (e.ctrlKey && e.key === "n") {
                    e.preventDefault();
                    document.getElementById("lot").focus();
                }
            });

            // Responsive behavior
            function handleResize() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove("collapsed");
                    sidebarTitle.textContent = "Admin Dashboard";
                    toggleIcon.classList.remove("fa-chevron-right");
                    toggleIcon.classList.add("fa-chevron-left");
                }
            }

            window.addEventListener("resize", handleResize);
            handleResize();
        });

        // Add smooth scrolling
        document.documentElement.style.scrollBehavior = 'smooth';
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>
