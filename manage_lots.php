<?php
require '../config/db.php';

// Handle update operation
if (isset($_POST['update'])) {
    $id = intval($_POST['edit_id']);
    $lot = $_POST['lot'];
    $loc = $_POST['location'];
    $price = $_POST['price'];
    $stmt = $conn->prepare("UPDATE parking_lots SET lot_name=?, location=?, price_per_hour=? WHERE id=?");
    $stmt->bind_param("ssdi", $lot, $loc, $price, $id);
    if ($stmt->execute()) {
        $success_message = "Parking lot updated successfully!";
    } else {
        $error_message = "Error updating parking lot: " . $conn->error;
    }
    $stmt->close();
}

// Handle form submission
if (isset($_POST['submit'])) {
    $lot = $_POST['lot'];
    $loc = $_POST['location'];
    $price = $_POST['price'];
    $stmt = $conn->prepare("INSERT INTO parking_lots (lot_name, location, price_per_hour) VALUES (?, ?, ?)");
    $stmt->bind_param("ssd", $lot, $loc, $price);
    if ($stmt->execute()) {
        $success_message = "Parking lot added successfully!";
    } else {
        $error_message = "Error adding parking lot: " . $conn->error;
    }
    $stmt->close();
}

// Handle delete operation
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM parking_lots WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success_message = "Parking lot deleted successfully!";
    } else {
        $error_message = "Error deleting parking lot: " . $conn->error;
    }
    $stmt->close();
}

// Edit mode
$edit_lot = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM parking_lots WHERE id = $edit_id");
    if ($res && $res->num_rows > 0) {
        $edit_lot = $res->fetch_assoc();
    }
}

// Get current page for navigation
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch all parking lots
$res = $conn->query("SELECT * FROM parking_lots ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lots - Admin Dashboard</title>
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        .form-input {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            background-color: #ffffff;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-input:hover {
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

        .btn-edit {
            background-color: #f59e0b;
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            margin-right: 0.5rem;
        }

        .btn-edit:hover {
            background-color: #d97706;
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

        .price-tag {
            background-color: #10b981;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
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

            .form-grid {
                grid-template-columns: 1fr;
            }

            .table-container {
                overflow-x: auto;
            }

            .table {
                min-width: 600px;
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
                        <a href="manage_lots.php" class="nav-link active">
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
                <h2 class="page-title">Manage Parking Lots</h2>
                <p class="page-subtitle">Add, edit, and manage parking lots with pricing</p>
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

            <!-- Add/Edit Lot Form -->
            <div class="form-container">
                <div class="form-title">
                    <i class="fas fa-plus"></i> <?php echo $edit_lot ? 'Edit Parking Lot' : 'Add Parking Lot'; ?>
                </div>
                <form method="post">
                    <?php if ($edit_lot): ?>
                        <input type="hidden" name="edit_id" value="<?php echo $edit_lot['id']; ?>">
                    <?php endif; ?>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="lot">
                                <i class="fas fa-parking"></i> Lot Name
                            </label>
                            <input 
                                type="text" 
                                id="lot"
                                name="lot" 
                                class="form-input" 
                                placeholder="Enter lot name (e.g., Main Parking)" 
                                value="<?php echo $edit_lot ? htmlspecialchars($edit_lot['lot_name']) : ''; ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="location">
                                <i class="fas fa-map-marker-alt"></i> Location
                            </label>
                            <input 
                                type="text" 
                                id="location"
                                name="location" 
                                class="form-input" 
                                placeholder="Enter location (e.g., Building A)" 
                                value="<?php echo $edit_lot ? htmlspecialchars($edit_lot['location']) : ''; ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="price">
                                <i class="fas fa-dollar-sign"></i> Price per Hour ($)
                            </label>
                            <input 
                                type="number" 
                                id="price"
                                name="price" 
                                class="form-input" 
                                placeholder="Enter price (e.g., 5.00)" 
                                step="0.01" 
                                min="0"
                                value="<?php echo $edit_lot ? htmlspecialchars($edit_lot['price_per_hour']) : ''; ?>"
                                required
                            >
                        </div>
                    </div>

                    <button type="submit" name="<?php echo $edit_lot ? 'update' : 'submit'; ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        <?php echo $edit_lot ? 'Update Lot' : 'Add Parking Lot'; ?>
                    </button>
                    <?php if ($edit_lot): ?>
                        <a href="manage_lots.php" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Parking Lots Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">
                        <i class="fas fa-list"></i>
                        Existing Parking Lots
                    </h3>
                </div>

                <?php if ($res && $res->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Lot Name</th>
                                <th>Location</th>
                                <th>Price per Hour</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $res->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['lot_name']); ?></strong>
                                    </td>
                                    <td>
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($row['location']); ?>
                                    </td>
                                    <td>
                                        <span class="price-tag">
                                            $<?php echo number_format($row['price_per_hour'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?delete=<?php echo $row['id']; ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this parking lot?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-parking"></i>
                        <h3>No Parking Lots Found</h3>
                        <p>Start by adding your first parking lot using the form above.</p>
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
            const form = document.querySelector('form'); // Select the form
            const submitBtn = form.querySelector('button[type="submit"]');

            form.addEventListener("submit", function(e) {
                // Add loading state
                submitBtn.classList.add("loading");
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo $edit_lot ? 'Updating...' : 'Adding...'; ?>';
                
                // Basic validation
                const lotName = document.getElementById("lot").value.trim();
                const location = document.getElementById("location").value.trim();
                const price = document.getElementById("price").value;

                if (!lotName || !location || !price) {
                    e.preventDefault();
                    alert("Please fill in all fields");
                    submitBtn.classList.remove("loading");
                    submitBtn.innerHTML = '<i class="fas fa-plus"></i> <?php echo $edit_lot ? 'Update Lot' : 'Add Parking Lot'; ?>';
                    return;
                }

                if (parseFloat(price) < 0) {
                    e.preventDefault();
                    alert("Price cannot be negative");
                    submitBtn.classList.remove("loading");
                    submitBtn.innerHTML = '<i class="fas fa-plus"></i> <?php echo $edit_lot ? 'Update Lot' : 'Add Parking Lot'; ?>';
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
                
                // Ctrl+N to focus on lot name input
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

        // Edit lot function (placeholder)
        function editLot(id) {
            // This would typically open a modal or redirect to an edit page
            alert("Edit functionality for lot ID: " + id + " would be implemented here");
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
