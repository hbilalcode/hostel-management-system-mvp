<?php
// Database connection details
$host = "localhost";   // Change if needed
$user = "root";        // Your DB username
$pass = "";            // Your DB password
$db   = "hostel_management";

// Connect to MySQL
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Admin credentials
$username = "admin";
$email = "admin@hostel.com";
$password = "admin123";  // Plain password
$role = "admin";

// Hash the password before inserting
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Check if admin already exists
$check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$check->bind_param("ss", $username, $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo "⚠️ Admin already exists in database.";
} else {
    // Insert admin into users table
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);

    if ($stmt->execute()) {
        echo "✅ Admin inserted successfully!";
    } else {
        echo "❌ Error: " . $stmt->error;
    }

    $stmt->close();
}

$check->close();
$conn->close();
?>
