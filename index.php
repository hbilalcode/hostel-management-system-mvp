<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }

        .logo {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #667eea;
        }

        h1 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }

        p {
            color: #666;
            margin-bottom: 2rem;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            min-width: 150px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .features {
            margin-top: 2rem;
            text-align: left;
        }

        .feature {
            display: flex;
            align-items: center;
            margin: 0.5rem 0;
            color: #555;
        }

        .feature-icon {
            color: #667eea;
            margin-right: 0.5rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🏨</div>
        <h1>Hostel Management System</h1>
        <p>Streamline your hostel operations with our comprehensive management solution</p>
        
        <div class="btn-group">
            <a href="login.php" class="btn btn-primary">Login</a>
            <a href="register.php" class="btn btn-secondary">Register</a>
        </div>

        <div class="features">
            <div class="feature">
                <span class="feature-icon">✓</span>
                Room Management & Allocation
            </div>
            <div class="feature">
                <span class="feature-icon">✓</span>
                Student Registration & Management
            </div>
            <div class="feature">
                <span class="feature-icon">✓</span>
                Fee Management & Receipts
            </div>
            <div class="feature">
                <span class="feature-icon">✓</span>
                Real-time Dashboard & Reports
            </div>
        </div>
    </div>

    <?php
    // Check if user is already logged in
    include 'config/db.php';
    if (isLoggedIn()) {
        if (isAdmin()) {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: student_dashboard.php");
        }
        exit();
    }
    ?>
</body>
</html>