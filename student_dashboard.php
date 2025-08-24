<?php
include 'config/db.php';
redirectIfNotLoggedIn();

// Get student details
$stmt = $conn->prepare("
    SELECT s.*, u.email, r.room_number, r.room_type, r.rent
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN rooms r ON s.room_id = r.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    // If student record doesn't exist, redirect to complete profile
    header("Location: complete_profile.php");
    exit();
}

// Get fee records
$fee_stmt = $conn->prepare("
    SELECT * FROM fees 
    WHERE student_id = ? 
    ORDER BY month_year DESC
");
$fee_stmt->bind_param("i", $student['id']);
$fee_stmt->execute();
$fees = $fee_stmt->get_result();

// Get fee statistics
$paid_fees = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM fees WHERE student_id = ? AND status = 'paid'");
$paid_fees->bind_param("i", $student['id']);
$paid_fees->execute();
$paid_total = $paid_fees->get_result()->fetch_assoc()['total'];

$unpaid_fees = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM fees WHERE student_id = ? AND status = 'unpaid'");
$unpaid_fees->bind_param("i", $student['id']);
$unpaid_fees->execute();
$unpaid_total = $unpaid_fees->get_result()->fetch_assoc()['total'];

$unpaid_count = $conn->prepare("SELECT COUNT(*) as count FROM fees WHERE student_id = ? AND status = 'unpaid'");
$unpaid_count->bind_param("i", $student['id']);
$unpaid_count->execute();
$unpaid_count_result = $unpaid_count->get_result()->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Hostel Management System</title>
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

        .profile-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
        }

        .card-title {
            color: #333;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .profile-info {
            display: grid;
            gap: 1rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #666;
            font-weight: 600;
        }

        .info-value {
            color: #333;
            font-weight: 500;
        }

        .allocation-status {
            text-align: center;
            padding: 2rem;
        }

        .status-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .status-allocated {
            color: #28a745;
        }

        .status-unallocated {
            color: #ffc107;
        }

        .status-message {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .status-description {
            color: #666;
        }

        .room-details {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-number.paid {
            color: #28a745;
        }

        .stat-number.unpaid {
            color: #dc3545;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-unpaid {
            background: #f8d7da;
            color: #721c24;
        }

        .no-data {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .profile-section {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎓 Student Dashboard</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($student['name']); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Profile Section -->
        <div class="profile-section">
            <!-- Personal Information -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">👤 Personal Information</h2>
                </div>
                <div class="card-body">
                    <div class="profile-info">
                        <div class="info-item">
                            <span class="info-label">Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Roll Number</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['roll_no']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Contact</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['contact']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Department</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['department']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Registration Date</span>
                            <span class="info-value"><?php echo date('F d, Y', strtotime($student['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Room Allocation Status -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">🏠 Room Allocation</h2>
                </div>
                <div class="card-body">
                    <?php if ($student['room_id']): ?>
                        <div class="allocation-status">
                            <div class="status-icon status-allocated">✅</div>
                            <div class="status-message status-allocated">Room Allocated</div>
                            <div class="status-description">You have been allocated a room</div>
                        </div>
                        <div class="room-details">
                            <div class="info-item">
                                <span class="info-label">Room Number</span>
                                <span class="info-value"><?php echo htmlspecialchars($student['room_number']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Room Type</span>
                                <span class="info-value"><?php echo ucfirst($student['room_type']); ?> Room</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Monthly Rent</span>
                                <span class="info-value"><?php echo number_format($student['rent'], 2); ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="allocation-status">
                            <div class="status-icon status-unallocated">⏳</div>
                            <div class="status-message status-unallocated">Awaiting Room Allocation</div>
                            <div class="status-description">Your room allocation is pending. Please contact the administration.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Fee Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number paid"><?php echo number_format($paid_total, 0); ?></div>
                <div class="stat-label">Total Paid</div>
            </div>
            <div class="stat-card">
                <div class="stat-number unpaid"><?php echo number_format($unpaid_total, 0); ?></div>
                <div class="stat-label">Pending Amount</div>
            </div>
            <div class="stat-card">
                <div class="stat-number unpaid"><?php echo $unpaid_count_result; ?></div>
                <div class="stat-label">Unpaid Records</div>
            </div>
        </div>

        <!-- Fee Records -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">💳 Fee Records</h2>
            </div>
            <div class="card-body">
                <?php if ($fees->num_rows > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Month/Year</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Payment Date</th>
                                    <th>Due Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($fee = $fees->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('F Y', strtotime($fee['month_year'] . '-01')); ?></td>
                                    <td><?php echo number_format($fee['amount'], 2); ?></td>
                                    <td><?php echo ucfirst($fee['fee_type']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $fee['status']; ?>">
                                            <?php echo ucfirst($fee['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $fee['payment_date'] ? date('M d, Y', strtotime($fee['payment_date'])) : '-'; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $due_date = date('Y-m-t', strtotime($fee['month_year'] . '-01'));
                                        echo date('M d, Y', strtotime($due_date));
                                        ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📋</div>
                        <h3>No Fee Records</h3>
                        <p>No fee records have been generated for your account yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>