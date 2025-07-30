<?php
require_once '../config/db.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = '$email' AND password = '$pass'";
    $res = $conn->query($sql);

    if ($res->num_rows == 1) {
        $user = $res->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'admin') {
            header("Location: ../admin/index.php");
        } else {
            header("Location: ../user/index.php");
        }
        exit;
    } else {
        $error_message = "Invalid credentials. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Parking System - Login</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><pattern id="parking-grid" x="0" y="0" width="80" height="120" patternUnits="userSpaceOnUse"><rect width="80" height="120" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/><rect x="10" y="10" width="60" height="100" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="2" rx="5"/></pattern></defs><rect width="1000" height="1000" fill="url(%23parking-grid)"/></svg>') repeat;
            opacity: 0.3;
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

        .parking-lot-visual {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 40px 0;
            max-width: 300px;
            margin-left: auto;
            margin-right: auto;
        }

        .parking-space {
            width: 60px;
            height: 40px;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 8px;
            position: relative;
            transition: all 0.3s ease;
        }

        .parking-space.occupied {
            background: rgba(244, 67, 54, 0.7);
            border-color: #f44336;
        }

        .parking-space.available {
            background: rgba(76, 175, 80, 0.7);
            border-color: #4caf50;
        }

        .parking-space::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
        }

        .parking-space.occupied::after {
            background: rgba(255, 255, 255, 0.9);
        }

        .stats {
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
            margin-bottom: 35px;
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
            margin-bottom: 20px;
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
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .form-group input::placeholder {
            color: #999;
            font-size: 14px;
        }

        .login-button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .login-button:active {
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

        .register-section {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }

        .register-text {
            color: #666;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .register-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            padding: 6px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .register-link:hover {
            background: rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
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

            .parking-lot-visual {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
                margin: 20px 0;
            }

            .parking-space {
                width: 50px;
                height: 35px;
            }

            .right-section {
                flex: 1;
                padding: 20px;
            }

            .stats {
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
                <h1 class="system-title">Smart Parking</h1>
                <p class="system-subtitle">Intelligent Parking Management System</p>
                
                <div class="parking-lot-visual">
                    <div class="parking-space occupied"></div>
                    <div class="parking-space available"></div>
                    <div class="parking-space occupied"></div>
                    <div class="parking-space available"></div>
                    <div class="parking-space available"></div>
                    <div class="parking-space occupied"></div>
                    <div class="parking-space available"></div>
                    <div class="parking-space available"></div>
                    <div class="parking-space occupied"></div>
                    <div class="parking-space available"></div>
                    <div class="parking-space occupied"></div>
                    <div class="parking-space available"></div>
                </div>

                <div class="stats">
                    <div class="stat-item">
                        <span class="stat-number">156</span>
                        <span class="stat-label">Total Spaces</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">89</span>
                        <span class="stat-label">Available</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">67</span>
                        <span class="stat-label">Occupied</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="right-section">
            <div class="form-container">
                <div class="form-header">
                    <h2 class="form-title">Welcome Back</h2>
                    <p class="form-subtitle">Sign in to access your parking dashboard</p>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>

                    <button type="submit" class="login-button">
                        Sign In
                    </button>
                </form>

                <div class="register-section">
                    <p class="register-text">Don't have an account?</p>
                    <a href="register.php" class="register-link">Create an account</a>
                </div>

                <div class="features">
                    <h3>System Features</h3>
                    <div class="feature-list">
                        <div class="feature-item">Real-time space monitoring</div>
                        <div class="feature-item">Automated payment processing</div>
                        <div class="feature-item">Mobile app integration</div>
                        <div class="feature-item">24/7 customer support</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>