<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

include 'config/db.php';


// Get dashboard statistics
$total_rooms = $conn->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'];
$vacant_rooms = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'vacant'")->fetch_assoc()['count'];
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$pending_fees = $conn->query("SELECT COUNT(*) as count FROM fees WHERE status = 'unpaid'")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM fees WHERE status = 'paid'")->fetch_assoc()['total'];

// Recent activities
$recent_students = $conn->query("SELECT name, roll_no, created_at FROM students ORDER BY created_at DESC LIMIT 5");
$recent_payments = $conn->query("SELECT s.name, f.amount, f.payment_date FROM fees f JOIN students s ON f.student_id = s.id WHERE f.status = 'paid' ORDER BY f.payment_date DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Hostel Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logout-btn {
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 1.1rem;
        }

        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .nav-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-decoration: none;
            color: #333;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s ease;
        }

        .nav-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .nav-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #667eea;
        }

        .recent-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .recent-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .recent-card h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .recent-list {
            list-style: none;
        }

        .recent-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .recent-item:last-child {
            border-bottom: none;
        }

        .recent-name {
            font-weight: 600;
            color: #333;
        }

        .recent-meta {
            color: #666;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
            }
            
            .recent-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏨 Admin Dashboard</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🏠</div>
                <div class="stat-number"><?php echo $total_rooms; ?></div>
                <div class="stat-label">Total Rooms</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📭</div>
                <div class="stat-number"><?php echo $vacant_rooms; ?></div>
                <div class="stat-label">Vacant Rooms</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?php echo $total_students; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⏰</div>
                <div class="stat-number"><?php echo $pending_fees; ?></div>
                <div class="stat-label">Pending Fees</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-number"><?php echo number_format($total_revenue, 0); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <!-- Navigation Cards -->
        <div class="nav-grid">
            <a href="rooms.php" class="nav-card">
                <div class="nav-icon">🏠</div>
                <h3>Room Management</h3>
                <p>Add, edit, and manage rooms</p>
            </a>
            
            <a href="students.php" class="nav-card">
                <div class="nav-icon">👥</div>
                <h3>Student Management</h3>
                <p>Manage student records</p>
            </a>
            
            <a href="allocations.php" class="nav-card">
                <div class="nav-icon">🔗</div>
                <h3>Room Allocation</h3>
                <p>Assign rooms to students</p>
            </a>
            
            <a href="fees.php" class="nav-card">
                <div class="nav-icon">💳</div>
                <h3>Fee Management</h3>
                <p>Manage student fees</p>
            </a>
        </div>

        <!-- Recent Activities -->
        <div class="recent-section">
            <div class="recent-card">
                <h3>Recent Registrations</h3>
                <ul class="recent-list">
                    <?php while ($student = $recent_students->fetch_assoc()): ?>
                    <li class="recent-item">
                        <div>
                            <div class="recent-name"><?php echo htmlspecialchars($student['name']); ?></div>
                            <div class="recent-meta">Roll: <?php echo htmlspecialchars($student['roll_no']); ?></div>
                        </div>
                        <div class="recent-meta"><?php echo date('M d, Y', strtotime($student['created_at'])); ?></div>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
            
            <div class="recent-card">
                <h3>Recent Payments</h3>
                <ul class="recent-list">
                    <?php while ($payment = $recent_payments->fetch_assoc()): ?>
                    <li class="recent-item">
                        <div>
                            <div class="recent-name"><?php echo htmlspecialchars($payment['name']); ?></div>
                            <div class="recent-meta"><?php echo number_format($payment['amount'], 2); ?></div>
                        </div>
                        <div class="recent-meta"><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></div>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>