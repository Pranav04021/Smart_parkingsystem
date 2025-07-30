<?php
require '../config/db.php';

// Handle status update
if (isset($_POST['update_status'])) {
    $reservation_id = $_POST['reservation_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE reservations SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $reservation_id);
    
    if ($stmt->execute()) {
        $success_message = "Reservation status updated successfully!";
    } else {
        $error_message = "Error updating reservation status: " . $conn->error;
    }
    $stmt->close();
}

// Handle reservation deletion
if (isset($_GET['delete'])) {
    $reservation_id = $_GET['delete'];
    
    $stmt = $conn->prepare("DELETE FROM reservations WHERE id = ?");
    $stmt->bind_param("i", $reservation_id);
    
    if ($stmt->execute()) {
        $success_message = "Reservation deleted successfully!";
    } else {
        $error_message = "Error deleting reservation: " . $conn->error;
    }
    $stmt->close();
}

// Handle update operation
if (isset($_POST['update_reservation'])) {
    $id = intval($_POST['edit_id']);
    $status = $_POST['status'];
    $slot_id = $_POST['slot_id'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $stmt = $conn->prepare("UPDATE reservations SET status=?, slot_id=?, start_time=?, end_time=? WHERE id=?");
    $stmt->bind_param("sissi", $status, $slot_id, $start_time, $end_time, $id);
    if ($stmt->execute()) {
        $success_message = "Reservation updated successfully!";
    } else {
        $error_message = "Error updating reservation: " . $conn->error;
    }
    $stmt->close();
}
// Handle edit request (fetch reservation data)
$edit_reservation = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM reservations WHERE id = $edit_id");
    if ($res && $res->num_rows > 0) {
        $edit_reservation = $res->fetch_assoc();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
$search_filter = $_GET['search'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($status_filter)) {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(r.created_at) = ?";
    $params[] = $date_filter;
    $param_types .= 's';
}

if (!empty($search_filter)) {
    $where_conditions[] = "(u.full_name LIKE ? OR s.slot_number LIKE ? OR pl.lot_name LIKE ?)";
    $search_param = "%$search_filter%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get reservations with enhanced information - Updated for existing schema
$query = "SELECT r.*, u.full_name as user_name, u.email, u.phone,
                 s.slot_number, s.slot_type,
                 pl.lot_name, pl.location, pl.price_per_hour
          FROM reservations r
          JOIN users u ON r.user_id = u.id
          JOIN slots s ON r.slot_id = s.id
          JOIN parking_lots pl ON s.lot_id = pl.id
          $where_clause
          ORDER BY r.id DESC, r.start_time DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($query);
}

// Get statistics - Updated for existing schema
$stats_query = $conn->query("
    SELECT 
        COUNT(*) as total_reservations,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_reservations,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_reservations,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_reservations,
        COALESCE(SUM(CASE WHEN status = 'confirmed' THEN total_cost ELSE 0 END), 0) as total_revenue
    FROM reservations
");
$stats = $stats_query->fetch_assoc();

// Get current page for navigation
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reservations - Admin Dashboard</title>
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

        /* Statistics cards */
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
            font-size: 1.5rem;
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
            font-size: 1.25rem;
        }

        .stat-icon.total { background-color: #dbeafe; color: #2563eb; }
        .stat-icon.confirmed { background-color: #d1fae5; color: #059669; }
        .stat-icon.cancelled { background-color: #fee2e2; color: #dc2626; }
        .stat-icon.revenue { background-color: #fef3c7; color: #d97706; }

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

        /* Filters section */
        .filters-container {
            background-color: #ffffff;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            margin-bottom: 1.5rem;
        }

        .filters-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .filter-input, .filter-select {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .filter-input:focus, .filter-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        /* Buttons */
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.875rem;
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
        }

        .btn-secondary {
            background-color: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #4b5563;
        }

        .btn-danger {
            background-color: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }

        .btn-warning {
            background-color: #f59e0b;
            color: white;
        }

        .btn-warning:hover {
            background-color: #d97706;
        }

        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
        }

        /* Table container */
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
            background-color: #f9fafb;
        }

        .table-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        /* Table styles */
        .table-wrapper {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .table td {
            color: #6b7280;
        }

        .table tbody tr:hover {
            background-color: #f9fafb;
        }

        /* User info cell */
        .user-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .user-name {
            font-weight: 600;
            color: #1f2937;
        }

        .user-contact {
            font-size: 0.75rem;
            color: #6b7280;
        }

        /* Slot info cell */
        .slot-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .slot-number {
            font-weight: 600;
            color: #1f2937;
        }

        .slot-details {
            font-size: 0.75rem;
            color: #6b7280;
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

        .status-confirmed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-completed {
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

        .type-electric {
            background-color: #34d399;
            color: #065f46;
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
            min-width: 100px;
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* Date and time formatting */
        .date-time {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .date {
            font-weight: 500;
            color: #1f2937;
        }

        .time {
            font-size: 0.875rem;
            color: #6b7280;
        }

        /* Cost display */
        .cost-display {
            font-weight: 600;
            color: #059669;
            font-size: 1rem;
        }

        /* Empty state */
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

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background-color: #f9fafb;
        }

        .pagination-btn {
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            background-color: white;
            color: #374151;
            text-decoration: none;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }

        .pagination-btn:hover {
            background-color: #f3f4f6;
        }

        .pagination-btn.active {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
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

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .table-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .table-actions {
                justify-content: center;
            }

            .action-buttons {
                flex-direction: column;
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
            height: 8px;
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
                        <a href="manage_slots.php" class="nav-link">
                            <i class="fas fa-th-large nav-icon"></i>
                            <span class="nav-text">Manage Slots</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="view_reservations.php" class="nav-link active">
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
                <h2 class="page-title">All Reservations</h2>
                <p class="page-subtitle">Monitor and manage all parking reservations</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <h3>Total Reservations</h3>
                            <p><?php echo $stats['total_reservations'] ?? 0; ?></p>
                        </div>
                        <div class="stat-icon total">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <h3>Confirmed</h3>
                            <p><?php echo $stats['confirmed_reservations'] ?? 0; ?></p>
                        </div>
                        <div class="stat-icon confirmed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <h3>Cancelled</h3>
                            <p><?php echo $stats['cancelled_reservations'] ?? 0; ?></p>
                        </div>
                        <div class="stat-icon cancelled">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <h3>Total Revenue</h3>
                            <p>$<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></p>
                        </div>
                        <div class="stat-icon revenue">
                            <i class="fas fa-dollar-sign"></i>
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

            <!-- Filters -->
            <div class="filters-container">
                <h3 class="filters-title">
                    <i class="fas fa-filter"></i>
                    Filter Reservations
                </h3>
                
                <form method="get" id="filtersForm">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label class="filter-label" for="status">Status</label>
                            <select id="status" name="status" class="filter-select">
                                <option value="">All Statuses</option>
                                <option value="confirmed" <?php echo ($status_filter == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="cancelled" <?php echo ($status_filter == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="completed" <?php echo ($status_filter == 'completed') ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label class="filter-label" for="date">Created Date</label>
                            <input type="date" id="date" name="date" class="filter-input" value="<?php echo htmlspecialchars($date_filter); ?>">
                        </div>

                        <div class="filter-group">
                            <label class="filter-label" for="search">Search</label>
                            <input type="text" id="search" name="search" class="filter-input" 
                                   placeholder="User name, slot, or lot..." value="<?php echo htmlspecialchars($search_filter); ?>">
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">&nbsp;</label>
                            <div class="filter-buttons">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="?" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Show edit form above reservations table -->
            <?php if ($edit_reservation): ?>
                <div class="form-container">
                    <h3 class="form-title"><i class="fas fa-edit"></i> Edit Reservation</h3>
                    <form method="post">
                        <input type="hidden" name="edit_id" value="<?php echo $edit_reservation['id']; ?>">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-select">
                                <option value="confirmed" <?php if ($edit_reservation['status'] == 'confirmed') echo 'selected'; ?>>Confirmed</option>
                                <option value="cancelled" <?php if ($edit_reservation['status'] == 'cancelled') echo 'selected'; ?>>Cancelled</option>
                                <option value="completed" <?php if ($edit_reservation['status'] == 'completed') echo 'selected'; ?>>Completed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Slot ID</label>
                            <input type="number" name="slot_id" class="form-input" value="<?php echo htmlspecialchars($edit_reservation['slot_id']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Start Time</label>
                            <input type="datetime-local" name="start_time" class="form-input" value="<?php echo date('Y-m-d\TH:i', strtotime($edit_reservation['start_time'])); ?>">
                        </div>
                        <div class="form-group">
                            <label>End Time</label>
                            <input type="datetime-local" name="end_time" class="form-input" value="<?php echo date('Y-m-d\TH:i', strtotime($edit_reservation['end_time'])); ?>">
                        </div>
                        <button type="submit" name="update_reservation" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Reservation
                        </button>
                        <a href="view_reservations.php" class="btn btn-danger">Cancel</a>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Reservations Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">
                        <i class="fas fa-list"></i>
                        Reservations List
                    </h3>
                    <div class="table-actions">
                        <button onclick="exportData()" class="btn btn-secondary">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <button onclick="refreshData()" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>

                <?php if ($res && $res->num_rows > 0): ?>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User Information</th>
                                    <th>Slot Details</th>
                                    <th>Booking Date</th>
                                    <th>Time Slot</th>
                                    <th>Vehicle</th>
                                    <th>Cost</th>
                                    <th>Status</th>
                                    <th>Quick Update</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($r = $res->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo $r['id']; ?></strong>
                                        </td>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-name"><?php echo htmlspecialchars($r['user_name']); ?></div>
                                                <div class="user-contact">
                                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($r['email']); ?><br>
                                                    <?php if (!empty($r['phone'])): ?>
                                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($r['phone']); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="slot-info">
                                                <div class="slot-number">Slot <?php echo htmlspecialchars($r['slot_number']); ?></div>
                                                <div class="slot-details">
                                                    <strong><?php echo htmlspecialchars($r['lot_name']); ?></strong><br>
                                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($r['location']); ?><br>
                                                    <span class="type-badge <?php echo ($r['slot_type'] == 'vip' || $r['slot_type'] == 'disabled' || $r['slot_type'] == 'electric') ? 'type-' . $r['slot_type'] : ''; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $r['slot_type'])); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="date-time">
                                                <div class="date">
                                                    <?php echo date('M d, Y', strtotime($r['created_at'])); ?>
                                                </div>
                                                <div class="time">
                                                    <?php echo date('l', strtotime($r['created_at'])); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="date-time">
                                                <div class="date">
                                                    <?php echo date('H:i', strtotime($r['start_time'])); ?> - 
                                                    <?php echo date('H:i', strtotime($r['end_time'])); ?>
                                                </div>
                                                <div class="time">
                                                    <?php 
                                                    $start = new DateTime($r['start_time']);
                                                    $end = new DateTime($r['end_time']);
                                                    $duration = $start->diff($end);
                                                    echo $duration->format('%h hours %i minutes');
                                                    ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($r['vehicle_number']); ?></strong>
                                        </td>
                                        <td>
                                            <div class="cost-display">
                                                $<?php echo number_format($r['total_cost'], 2); ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: #6b7280;">
                                                $<?php echo number_format($r['price_per_hour'], 2); ?>/hour
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $r['status']; ?>">
                                                <?php echo ucfirst($r['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post" class="status-update-form">
                                                <input type="hidden" name="reservation_id" value="<?php echo $r['id']; ?>">
                                                <select name="new_status" class="status-select" onchange="this.form.submit()">
                                                    <option value="confirmed" <?php echo ($r['status'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="cancelled" <?php echo ($r['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                    <option value="completed" <?php echo ($r['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                </select>
                                                <button type="submit" name="update_status" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-sync"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="viewDetails(<?php echo $r['id']; ?>)" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <a href="view_reservations.php?edit=<?php echo $r['id']; ?>" class="btn btn-warning">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="?delete=<?php echo $r['id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Are you sure you want to delete this reservation?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Reservations Found</h3>
                        <p>No reservations match your current filters.</p>
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
                
                // Ctrl+F to focus on search
                if (e.ctrlKey && e.key === "f") {
                    e.preventDefault();
                    document.getElementById("search").focus();
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

        // View reservation details
        function viewDetails(id) {
            // This would typically open a modal with detailed information
            alert("View details for reservation ID: " + id + " would be implemented here");
        }

        // Edit reservation
        function editReservation(id) {
            // This would typically open an edit modal or redirect to edit page
            alert("Edit functionality for reservation ID: " + id + " would be implemented here");
        }

        // Export data
        function exportData() {
            // This would typically export the filtered data to CSV/Excel
            alert("Export functionality would be implemented here");
        }

        // Refresh data
        function refreshData() {
            window.location.reload();
        }

        // Add smooth scrolling
        document.documentElement.style.scrollBehavior = 'smooth';
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>
