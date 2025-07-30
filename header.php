<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Smart Parking - Find & Reserve Parking Spots'; ?></title>
    <meta name="description" content="Find and reserve parking spots instantly with Smart Parking. Real-time availability, secure payments, and seamless booking experience.">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="/Smart/style.css">
    <script src="/Smart/script.js"></script>
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 220px;
            height: 100vh;
            background: var(--bg-primary, #fff);
            border-right: 1px solid var(--border-color, #e5e7eb);
            display: flex;
            flex-direction: column;
            z-index: 1100;
        }
        .sidebar .logo {
            display: flex;
            align-items: center;
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary-color);
            padding: 2rem 1.5rem 1rem 1.5rem;
        }
        .sidebar .logo i {
            margin-right: 0.75rem;
        }
        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding: 0 1.5rem;
        }
        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-md, 0.5rem);
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
        }
        .sidebar-nav .nav-link.active, .sidebar-nav .nav-link:hover {
            background: var(--primary-light, #3b82f6);
            color: #fff;
        }
        .sidebar-nav .nav-link i {
            margin-right: 0.75rem;
        }
        .main-content {
            margin-left: 220px;
            transition: margin-left 0.3s;
        }
        @media (max-width: 900px) {
            .sidebar { width: 60px; }
            .sidebar .logo span { display: none; }
            .sidebar-nav .nav-link span { display: none; }
            .main-content { margin-left: 60px; }
        }
        @media (max-width: 600px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body class="<?php echo isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : ''; ?>">
    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-car"></i>
            <span>Smart Parking</span>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </a>
            <a href="lots.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'lots.php' ? 'active' : ''; ?>">
                <i class="fas fa-map-marker-alt"></i> <span>Browse Lots</span>
            </a>
            <a href="reservations.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reservations.php' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> <span>My Reservations</span>
            </a>
            <a href="payment.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'payment.php' ? 'active' : ''; ?>">
                <i class="fas fa-credit-card"></i> <span>Payment</span>
            </a>
            <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> <span>Profile</span>
            </a>
            <a href="help.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'help.php' ? 'active' : ''; ?>">
                <i class="fas fa-question-circle"></i> <span>Help</span>
            </a>
            <div style="flex:1;"></div>
            <a href="../auth/logout.php" class="nav-link" style="color:var(--danger-color,#ef4444);margin-top:auto;">
                <i class="fas fa-sign-out-alt"></i> <span>Log Out</span>
            </a>
        </nav>
    </div>
    
        <!-- Mobile Navigation (unchanged) -->
        <nav class="mobile-nav" id="mobileNav">
            <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="lots.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'lots.php' ? 'active' : ''; ?>">
                <i class="fas fa-map-marker-alt"></i> Browse Lots
            </a>
            <a href="reservations.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reservations.php' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> My Reservations
            </a>
            <a href="payment.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'payment.php' ? 'active' : ''; ?>">
                <i class="fas fa-credit-card"></i> Payment
            </a>
            <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> Profile
            </a>
            <a href="help.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'help.php' ? 'active' : ''; ?>">
                <i class="fas fa-question-circle"></i> Help
            </a>
        </nav>
        <!-- Notifications Panel (unchanged) -->
        <div class="notifications-panel" id="notificationsPanel">
            <div class="notifications-header">
                <h3>Notifications</h3>
                <button onclick="toggleNotifications()"><i class="fas fa-times"></i></button>
            </div>
            <div class="notifications-list">
                <div class="notification-item">
                    <i class="fas fa-check-circle text-success"></i>
                    <div>
                        <p>Reservation confirmed for Downtown Plaza</p>
                        <small>2 minutes ago</small>
                    </div>
                </div>
                <div class="notification-item">
                    <i class="fas fa-clock text-warning"></i>
                    <div>
                        <p>Parking expires in 30 minutes</p>
                        <small>15 minutes ago</small>
                    </div>
                </div>
                <div class="notification-item">
                    <i class="fas fa-info-circle text-info"></i>
                    <div>
                        <p>New parking lot available nearby</p>
                        <small>1 hour ago</small>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <main class="main-content">
        <div class="container">