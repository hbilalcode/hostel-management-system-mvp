<?php
include 'config/db.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'generate_fees':
                $month_year = $_POST['month_year'];
                $fee_type = $_POST['fee_type'];
                
                // Get all allocated students
                $students_query = "
                    SELECT s.id, s.name, r.rent 
                    FROM students s 
                    JOIN rooms r ON s.room_id = r.id 
                    WHERE s.room_id IS NOT NULL
                ";
                $students = $conn->query($students_query);
                
                $generated_count = 0;
                $conn->begin_transaction();
                
                try {
                    while ($student = $students->fetch_assoc()) {
                        // Check if fee already exists
                        $check_stmt = $conn->prepare("SELECT id FROM fees WHERE student_id = ? AND month_year = ?");
                        $check_stmt->bind_param("is", $student['id'], $month_year);
                        $check_stmt->execute();
                        
                        if ($check_stmt->get_result()->num_rows == 0) {
                            // Generate fee
                            $stmt = $conn->prepare("INSERT INTO fees (student_id, amount, fee_type, month_year) VALUES (?, ?, ?, ?)");
                            $stmt->bind_param("idss", $student['id'], $student['rent'], $fee_type, $month_year);
                            $stmt->execute();
                            $generated_count++;
                        }
                        $check_stmt->close();
                    }
                    
                    $conn->commit();
                    $message = "Generated fees for {$generated_count} students for {$month_year}";
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error generating fees: " . $e->getMessage();
                }
                break;
                
            case 'mark_paid':
                $fee_id = intval($_POST['fee_id']);
                
                $stmt = $conn->prepare("UPDATE fees SET status = 'paid', payment_date = NOW() WHERE id = ?");
                $stmt->bind_param("i", $fee_id);
                
                if ($stmt->execute()) {
                    $message = "Fee marked as paid successfully!";
                } else {
                    $error = "Error updating fee status: " . $conn->error;
                }
                $stmt->close();
                break;
                
            case 'mark_unpaid':
                $fee_id = intval($_POST['fee_id']);
                
                $stmt = $conn->prepare("UPDATE fees SET status = 'unpaid', payment_date = NULL WHERE id = ?");
                $stmt->bind_param("i", $fee_id);
                
                if ($stmt->execute()) {
                    $message = "Fee marked as unpaid successfully!";
                } else {
                    $error = "Error updating fee status: " . $conn->error;
                }
                $stmt->close();
                break;
        }
    }
}

// Get fee statistics
$total_fees = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM fees")->fetch_assoc()['total'];
$paid_fees = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM fees WHERE status = 'paid'")->fetch_assoc()['total'];
$unpaid_fees = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM fees WHERE status = 'unpaid'")->fetch_assoc()['total'];
$unpaid_count = $conn->query("SELECT COUNT(*) as count FROM fees WHERE status = 'unpaid'")->fetch_assoc()['count'];

// Get all fees with student details
$fees_query = "
    SELECT f.*, s.name, s.roll_no, r.room_number
    FROM fees f
    JOIN students s ON f.student_id = s.id
    LEFT JOIN rooms r ON s.room_id = r.id
    ORDER BY f.month_year DESC, s.name
";
$fees = $conn->query($fees_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Management - Hostel Management System</title>
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

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
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
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-number.paid {
            color: #28a745;
        }

        .stat-number.unpaid {
            color: #dc3545;
        }

        .stat-number.total {
            color: #667eea;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            color: #333;
            font-size: 1.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .fee-generation-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 600;
        }

        .form-group input, .form-group select {
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
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

        .student-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .student-name {
            font-weight: 600;
            color: #333;
        }

        .student-details {
            color: #666;
            font-size: 0.9rem;
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

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
        }

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            align-items: center;
        }

        .filters select {
            padding: 0.5rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .fee-generation-form {
                grid-template-columns: 1fr;
            }
            
            .filters {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>💳 Fee Management</h1>
        <a href="admin_dashboard.php" class="btn btn-secondary">← Dashboard</a>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number total"><?php echo number_format($total_fees, 0); ?></div>
                <div class="stat-label">Total Fees</div>
            </div>
            <div class="stat-card">
                <div class="stat-number paid"><?php echo number_format($paid_fees, 0); ?></div>
                <div class="stat-label">Collected</div>
            </div>
            <div class="stat-card">
                <div class="stat-number unpaid"><?php echo number_format($unpaid_fees, 0); ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number unpaid"><?php echo $unpaid_count; ?></div>
                <div class="stat-label">Unpaid Records</div>
            </div>
        </div>

        <!-- Fee Generation -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Generate Monthly Fees</h2>
            </div>
            <div class="card-body">
                <form method="POST" class="fee-generation-form">
                    <input type="hidden" name="action" value="generate_fees">
                    
                    <div class="form-group">
                        <label for="month_year">Month & Year</label>
                        <input type="month" name="month_year" id="month_year" required value="<?php echo date('Y-m'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="fee_type">Fee Type</label>
                        <select name="fee_type" id="fee_type" required>
                            <option value="monthly">Monthly</option>
                            <option value="semester">Semester</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Generate Fees</button>
                </form>
                
                <p style="color: #666; margin-top: 1rem; font-size: 0.9rem;">
                    This will generate fees for all currently allocated students based on their room rent.
                </p>
            </div>
        </div>

        <!-- Fee Records -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Fee Records</h2>
            </div>
            <div class="card-body">
                <div class="filters">
                    <label>Filter by Status:</label>
                    <select id="statusFilter" onchange="filterFees()">
                        <option value="">All</option>
                        <option value="paid">Paid</option>
                        <option value="unpaid">Unpaid</option>
                    </select>
                    
                    <label>Filter by Month:</label>
                    <select id="monthFilter" onchange="filterFees()">
                        <option value="">All Months</option>
                        <?php
                        $months = $conn->query("SELECT DISTINCT month_year FROM fees ORDER BY month_year DESC");
                        while ($month = $months->fetch_assoc()):
                        ?>
                            <option value="<?php echo $month['month_year']; ?>">
                                <?php echo date('F Y', strtotime($month['month_year'] . '-01')); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="table-container">
                    <table id="feesTable">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Room</th>
                                <th>Amount</th>
                                <th>Month/Year</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Payment Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $fees->data_seek(0); ?>
                            <?php while ($fee = $fees->fetch_assoc()): ?>
                            <tr data-status="<?php echo $fee['status']; ?>" data-month="<?php echo $fee['month_year']; ?>">
                                <td>
                                    <div class="student-info">
                                        <div class="student-name"><?php echo htmlspecialchars($fee['name']); ?></div>
                                        <div class="student-details"><?php echo htmlspecialchars($fee['roll_no']); ?></div>
                                    </div>
                                </td>
                                <td><?php echo $fee['room_number'] ? 'Room ' . htmlspecialchars($fee['room_number']) : 'N/A'; ?></td>
                                <td><?php echo number_format($fee['amount'], 2); ?></td>
                                <td><?php echo date('F Y', strtotime($fee['month_year'] . '-01')); ?></td>
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
                                    <?php if ($fee['status'] == 'unpaid'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="mark_paid">
                                            <input type="hidden" name="fee_id" value="<?php echo $fee['id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm">Mark Paid</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="mark_unpaid">
                                            <input type="hidden" name="fee_id" value="<?php echo $fee['id']; ?>">
                                            <button type="submit" class="btn btn-warning btn-sm">Mark Unpaid</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function filterFees() {
            const statusFilter = document.getElementById('statusFilter').value;
            const monthFilter = document.getElementById('monthFilter').value;
            const table = document.getElementById('feesTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const rowStatus = row.getAttribute('data-status');
                const rowMonth = row.getAttribute('data-month');
                
                let showRow = true;
                
                if (statusFilter && rowStatus !== statusFilter) {
                    showRow = false;
                }
                
                if (monthFilter && rowMonth !== monthFilter) {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            }
        }
    </script>
</body>
</html>