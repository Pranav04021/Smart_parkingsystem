<?php
require_once '../config/db.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $pass = $_POST['password'];
    
    // Check if email already exists
    $check_email = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($check_email);
    
    if ($result->num_rows > 0) {
        $error_message = "Email already exists. Please use a different email.";
    } else {
        $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$pass')";
        if ($conn->query($sql)) {
            $success_message = "Registration successful! You can now login.";
        } else {
            $error_message = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Parking System - Register</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            overflow: hidden;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        .left-section {
            flex: 1;
            background: linear-gradient(135deg, #4CAF50 0%, #2196F3 100%);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            overflow: hidden;
        }

        .left-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><pattern id="car-pattern" x="0" y="0" width="100" height="60" patternUnits="userSpaceOnUse"><rect width="100" height="60" fill="none"/><path d="M20 30 L80 30 L75 20 L70 20 L65 15 L35 15 L30 20 L25 20 Z M25 35 L75 35 L75 45 L25 45 Z" fill="rgba(255,255,255,0.1)" stroke="rgba(255,255,255,0.2)" stroke-width="1"/><circle cx="30" cy="45" r="3" fill="rgba(255,255,255,0.2)"/><circle cx="70" cy="45" r="3" fill="rgba(255,255,255,0.2)"/></pattern></defs><rect width="1000" height="1000" fill="url(%23car-pattern)"/></svg>') repeat;
            opacity: 0.2;
        }

        .parking-visual {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 500px;
            padding: 40px;
        }

        .main-logo {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .main-logo::before {
            content: 'P';
            font-size: 60px;
            font-weight: bold;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .system-title {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .system-subtitle {
            font-size: 20px;
            margin-bottom: 40px;
            opacity: 0.9;
            font-weight: 300;
        }

        .benefits-visual {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 40px 0;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .benefit-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .benefit-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
        }

        .benefit-icon {
            font-size: 32px;
            margin-bottom: 10px;
            display: block;
        }

        .benefit-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .benefit-desc {
            font-size: 12px;
            opacity: 0.8;
        }

        .join-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 30px;
            text-align: center;
        }

        .stat-item {
            flex: 1;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            display: block;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.8;
            margin-top: 5px;
        }

        .right-section {
            flex: 1;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            min-height: 100vh;
        }

        .form-container {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-title {
            font-size: 28px;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .form-subtitle {
            color: #666;
            font-size: 15px;
            line-height: 1.4;
        }

        .form-group {
            margin-bottom: 18px;
            position: relative;
        }

        .form-group label {
            display: block;
            color: #555;
            font-weight: 500;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #fafafa;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4CAF50;
            background: white;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
            transform: translateY(-1px);
        }

        .form-group input::placeholder {
            color: #999;
            font-size: 14px;
        }

        .register-button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #4CAF50 0%, #2196F3 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            box-sizing: border-box;
        }

        .register-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(76, 175, 80, 0.3);
        }

        .register-button:active {
            transform: translateY(0);
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 18px;
            border-left: 4px solid #c62828;
            font-size: 13px;
            line-height: 1.4;
        }

        .success-message {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 18px;
            border-left: 4px solid #4caf50;
            font-size: 13px;
            line-height: 1.4;
        }

        .login-section {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }

        .login-text {
            color: #666;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .login-link {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            padding: 6px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .login-link:hover {
            background: rgba(76, 175, 80, 0.1);
            transform: translateY(-1px);
        }

        .password-requirements {
            font-size: 11px;
            color: #666;
            margin-top: 4px;
            padding-left: 5px;
            line-height: 1.3;
        }

        .features {
            margin-top: 25px;
        }

        .features h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 12px;
            text-align: center;
            font-weight: 600;
        }

        .feature-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
            font-size: 13px;
            padding: 6px 0;
            line-height: 1.3;
        }

        .feature-item::before {
            content: '✓';
            color: #4CAF50;
            font-weight: bold;
            font-size: 14px;
            width: 18px;
            height: 18px;
            background: rgba(76, 175, 80, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .left-section {
                flex: none;
                height: 40vh;
                padding: 20px;
            }

            .system-title {
                font-size: 32px;
            }

            .system-subtitle {
                font-size: 16px;
            }

            .benefits-visual {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                margin: 20px 0;
            }

            .benefit-card {
                padding: 15px;
            }

            .benefit-icon {
                font-size: 24px;
            }

            .right-section {
                flex: 1;
                padding: 20px;
            }

            .join-stats {
                margin-top: 20px;
            }

            .stat-number {
                font-size: 24px;
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .form-container {
            animation: slideIn 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .parking-visual {
            animation: fadeInUp 0.8s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <div class="parking-visual">
                <div class="main-logo"></div>
                <h1 class="system-title">Join Smart Parking</h1>
                <p class="system-subtitle">Experience the future of parking management</p>
                
                <div class="benefits-visual">
                    <div class="benefit-card">
                        <span class="benefit-icon">🚗</span>
                        <div class="benefit-title">Reserved Spots</div>
                        <div class="benefit-desc">Guaranteed parking space</div>
                    </div>
                    <div class="benefit-card">
                        <span class="benefit-icon">📱</span>
                        <div class="benefit-title">Mobile App</div>
                        <div class="benefit-desc">Control from your phone</div>
                    </div>
                    <div class="benefit-card">
                        <span class="benefit-icon">💳</span>
                        <div class="benefit-title">Easy Payment</div>
                        <div class="benefit-desc">Contactless transactions</div>
                    </div>
                    <div class="benefit-card">
                        <span class="benefit-icon">⚡</span>
                        <div class="benefit-title">Real-time</div>
                        <div class="benefit-desc">Live space updates</div>
                    </div>
                </div>

                <div class="join-stats">
                    <div class="stat-item">
                        <span class="stat-number">2.5K+</span>
                        <span class="stat-label">Active Users</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">50+</span>
                        <span class="stat-label">Locations</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">99.9%</span>
                        <span class="stat-label">Uptime</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="right-section">
            <div class="form-container">
                <div class="form-header">
                    <h2 class="form-title">Create Account</h2>
                    <p class="form-subtitle">Join thousands of satisfied parkers</p>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($success_message)): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Create a secure password" required>
                        <div class="password-requirements">
                            Use a strong password with letters, numbers, and symbols
                        </div>
                    </div>

                    <button type="submit" class="register-button">
                        Create Account
                    </button>
                </form>

                <div class="login-section">
                    <p class="login-text">Already have an account?</p>
                    <a href="login.php" class="login-link">Sign in here</a>
                </div>

                <div class="features">
                    <h3>What You Get</h3>
                    <div class="feature-list">
                        <div class="feature-item">Priority parking reservations</div>
                        <div class="feature-item">Mobile payment integration</div>
                        <div class="feature-item">Real-time space notifications</div>
                        <div class="feature-item">24/7 customer support</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>