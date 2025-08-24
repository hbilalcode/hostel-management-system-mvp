<?php
include 'config/db.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Invalid access, redirect or show error
    header('Location: student_management.php');
    exit;
}

$student_id = intval($_GET['id']);

$error = '';
$student = null;

// Fetch student details with room and user info
$stmt = $conn->prepare("
    SELECT s.*, u.email, u.created_at as user_created_at, r.room_number, r.rent, r.status as room_status
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN rooms r ON s.room_id = r.id
    WHERE s.id = ?
");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error = "Student not found.";
} else {
    $student = $result->fetch_assoc();
}

// Fetch fees associated with this student (if any)
$fees = [];
if ($student) {
    $fees_result = $conn->prepare("SELECT * FROM fees WHERE student_id = ? ORDER BY due_date DESC");
    $fees_result->bind_param('i', $student_id);
    $fees_result->execute();
    $fees_data = $fees_result->get_result();
    while ($fee = $fees_data->fetch_assoc()) {
        $fees[] = $fee;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Student Details - Hostel Management System</title>
<style>
    /* Reuse or add styles consistent with your existing stylesheet */
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f5f7fa;
        min-height: 100vh;
        padding: 2rem;
        max-width: 900px;
        margin: 0 auto;
    }
    .header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem 2rem;
        margin-bottom: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    h1, h2 {
        margin-bottom: 1rem;
    }
    a.btn {
        background: rgba(255,255,255,0.2);
        color: white;
        text-decoration: none;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 1rem;
    }
    a.btn:hover {
        background: rgba(255,255,255,0.3);
    }
    .card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    .label {
        font-weight: 600;
        color: #444;
        margin-top: 1rem;
        font-size: 1.1rem;
    }
    .value {
        margin-top: 0.25rem;
        font-size: 1rem;
        color: #333;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    th, td {
        padding: 0.75rem;
        border-bottom: 1px solid #eee;
        text-align: left;
    }
    th {
        background: #f8f9fa;
        font-weight: 600;
    }
    .no-data {
        text-align: center;
        color: #666;
        padding: 1rem 0;
    }
</style>
</head>
<body>

<div class="header">
    <h1>Student Details</h1>
</div>

<a href="student_management.php" class="btn">← Back to Students</a>

<?php if ($error): ?>
    <div class="card" style="background: #f8d7da; color: #721c24;">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php else: ?>

<div class="card">
    <h2>Basic Information</h2>
    <div><span class="label">Name:</span> <span class="value"><?php echo htmlspecialchars($student['name']); ?></span></div>
    <div><span class="label">Roll Number:</span> <span class="value"><?php echo htmlspecialchars($student['roll_no']); ?></span></div>
    <div><span class="label">Contact:</span> <span class="value"><?php echo htmlspecialchars($student['contact']); ?></span></div>
    <div><span class="label">Email:</span> <span class="value"><?php echo htmlspecialchars($student['email']); ?></span></div>
    <div><span class="label">Department:</span> <span class="value"><?php echo htmlspecialchars($student['department']); ?></span></div>
    <div><span class="label">Registration Date:</span> <span class="value"><?php echo date('M d, Y', strtotime($student['created_at'])); ?></span></div>
</div>

<div class="card">
    <h2>Room Allocation</h2>
    <?php if ($student['room_id']): ?>
        <div><span class="label">Room Number:</span> <span class="value"><?php echo htmlspecialchars($student['room_number']); ?></span></div>
        <div><span class="label">Monthly Rent:</span> <span class="value"><?php echo number_format($student['rent'], 2); ?></span></div>
        <div><span class="label">Room Status:</span> <span class="value"><?php echo htmlspecialchars(ucfirst($student['room_status'])); ?></span></div>
    <?php else: ?>
        <p>This student has not been allocated a room.</p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Fees</h2>
    <?php if (count($fees) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Fee Type</th>
                    <th>Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fees as $fee): ?>
                <tr>
                    <td><?php echo htmlspecialchars($fee['fee_type']); ?></td>
                    <td><?php echo number_format($fee['amount'], 2); ?></td>
                    <td><?php echo date('M d, Y', strtotime($fee['due_date'])); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($fee['status'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-data">No fee records found for this student.</p>
    <?php endif; ?>
</div>

<?php endif; ?>

</body>
</html>
