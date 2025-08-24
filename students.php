<?php
include 'config/db.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $student_id = intval($_POST['student_id']);
        
        $conn->begin_transaction();
        try {
            // Get user_id first
            $stmt = $conn->prepare("SELECT user_id, room_id FROM students WHERE id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $student_data = $result->fetch_assoc();
            
            if ($student_data) {
                // Update room status if student was allocated
                if ($student_data['room_id']) {
                    $conn->query("UPDATE rooms SET status = 'vacant' WHERE id = " . $student_data['room_id']);
                }
                
                // Delete student record (this will cascade delete fees)
                $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                
                // Delete user account
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $student_data['user_id']);
                $stmt->execute();
                
                $conn->commit();
                $message = "Student deleted successfully!";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error deleting student: " . $e->getMessage();
        }
    }
}

// Get all students with room details
$students_query = "
    SELECT s.*, u.email, r.room_number, r.rent
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN rooms r ON s.room_id = r.id
    ORDER BY s.created_at DESC
";
$students = $conn->query($students_query);

// Get statistics
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$allocated_students = $conn->query("SELECT COUNT(*) as count FROM students WHERE room_id IS NOT NULL")->fetch_assoc()['count'];
$unallocated_students = $total_students - $allocated_students;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Hostel Management System</title>
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
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
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
            justify-content: between;
            align-items: center;
        }

        .card-title {
            color: #333;
            font-size: 1.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .search-box {
            margin-bottom: 1rem;
        }

        .search-box input {
            width: 100%;
            max-width: 400px;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
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
            position: sticky;
            top: 0;
            z-index: 10;
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

        .student-roll {
            color: #666;
            font-size: 0.9rem;
        }

        .allocation-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .allocated {
            background: #d4edda;
            color: #155724;
        }

        .unallocated {
            background: #fff3cd;
            color: #856404;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
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

        .no-data {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
            }
            
            table {
                font-size: 0.9rem;
            }
            
            th, td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>👥 Student Management</h1>
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
                <div class="stat-number"><?php echo $total_students; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $allocated_students; ?></div>
                <div class="stat-label">Allocated Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $unallocated_students; ?></div>
                <div class="stat-label">Unallocated Students</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">All Students</h2>
            </div>
            <div class="card-body">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search by name, roll number, or department..." onkeyup="filterStudents()">
                </div>
                
                <div class="table-container">
                    <table id="studentsTable">
                        <thead>
                            <tr>
                                <th>Student Details</th>
                                <th>Contact</th>
                                <th>Department</th>
                                <th>Room Allocation</th>
                                <th>Monthly Rent</th>
                                <th>Registration Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($students->num_rows > 0): ?>
                                <?php while ($student = $students->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="student-info">
                                            <div class="student-name"><?php echo htmlspecialchars($student['name']); ?></div>
                                            <div class="student-roll">Roll: <?php echo htmlspecialchars($student['roll_no']); ?></div>
                                            <div class="student-roll"><?php echo htmlspecialchars($student['email']); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['contact']); ?></td>
                                    <td><?php echo htmlspecialchars($student['department']); ?></td>
                                    <td>
                                        <?php if ($student['room_id']): ?>
                                            <span class="allocation-badge allocated">
                                                Room <?php echo htmlspecialchars($student['room_number']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="allocation-badge unallocated">Not Allocated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['rent']): ?>
                                            <?php echo number_format($student['rent'], 2); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="student_details.php?id=<?php echo $student['id']; ?>" class="btn btn-info btn-sm">View</a>
                                            <button onclick="deleteStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name']); ?>')" class="btn btn-danger btn-sm">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-data">No students found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="student_id" id="deleteStudentId">
    </form>

    <script>
        function deleteStudent(studentId, studentName) {
            if (confirm('Are you sure you want to delete student "' + studentName + '"?\n\nThis will also delete their user account and all fee records. This action cannot be undone.')) {
                document.getElementById('deleteStudentId').value = studentId;
                document.getElementById('deleteForm').submit();
            }
        }

        function filterStudents() {
            const searchInput = document.getElementById('searchInput');
            const table = document.getElementById('studentsTable');
            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = tbody.getElementsByTagName('tr');
            const searchTerm = searchInput.value.toLowerCase();

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                
                if (cells.length > 0) {
                    const studentInfo = cells[0].textContent.toLowerCase();
                    const department = cells[2].textContent.toLowerCase();
                    const contact = cells[1].textContent.toLowerCase();
                    
                    if (studentInfo.includes(searchTerm) || 
                        department.includes(searchTerm) || 
                        contact.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            }
        }
    </script>
</body>
</html>