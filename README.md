# Smart Parking System 🚗

A full-stack web app to book, manage, and pay for parking slots. Includes admin dashboard, real-time availability, time-based pricing, and payment integration.

## 🔧 Features

- User/Admin login
- Live slot availability
- Time-based pricing
- Booking & payment (e.g., UPI)
- Wallet & transaction history
- Responsive design

## 🛠️ Tech Stack

- *Frontend*: HTML, CSS, JS  
- *Backend*: PHP  
- *Database*: MySQL  
- *Tools*: XAMPP, phpMyAdmin

## ▶️ How to Run

1. Copy project to `htdocs` (XAMPP).
2. Start Apache & MySQL from XAMPP.
3. Go to [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
4. Create DB `smart_parking` and import `smart_parking.sql`.
5. Edit DB credentials in `config/db.php`:
   ```php
   $conn = new mysqli('localhost', 'root', '', 'smart_parking');
