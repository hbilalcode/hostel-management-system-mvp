<?php
include 'config/db.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

$message = '';
$error = '';

// Handle room allocation/deallocation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'allocate':
                $student_id = intval($_POST['student_id']);
                $room_id = intval($_POST['room_id']);
                
                $conn->begin_transaction();
                try {
                    // Check room capacity
                    $capacity_check = $conn->prepare("
                        SELECT r.capacity, COALESCE(COUNT(s.id), 0) as current_occupancy 
                        FROM rooms r 
                        LEFT JOIN students s ON r.id = s.room_id 
                        WHERE r.id = ? 
                        GROUP BY r.id
                    ");
                    $capacity_check->bind_param("i", $room_id);
                    $capacity_check->execute();
                    $capacity_result = $capacity_check->get_result()->fetch_assoc();
                    
                    if ($capacity_result['current_occupancy'] >= $capacity_result['capacity']) {
                        throw new Exception("Room is already at full capacity!");
                    }
                    
                    // Check if student is already allocated
                    $student_check = $conn->prepare("SELECT room_id FROM students WHERE id = ?");
                    $student_check->bind_param("i", $student_id);
                    $student_check->execute();
                    $student_result = $student_check->get_result()->fetch_assoc();
                    
                    if ($student_result['room_id']) {
                        throw new Exception("Student is already allocated to a room!");
                    }
                    
                    // Allocate room
                    $stmt = $conn->prepare("UPDATE students SET room_id = ? WHERE id = ?");
                    $stmt->bind_param("ii", $room_id, $student_id);
                    $stmt->execute();
                    
                    // Update room status if at capacity
                    if ($capacity_result['current_occupancy'] + 1 >= $capacity_result['capacity']) {
                        $conn->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?")->execute([$room_id]);
                    }
                    
                    $conn->commit();
                    $message = "Room allocated successfully!";
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error: " . $e->getMessage();
                }
                break;
                
            case 'deallocate':
                $student_id = intval($_POST['student_id']);
                
                $conn->begin_transaction();
                try {
                    // Get current room
                    $stmt = $conn->prepare("SELECT room_id FROM students WHERE id = ?");
                    $stmt->bind_param("i", $student_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $current_room = $result->fetch_assoc();
                    
                    if (!$current_room['room_id']) {
                        throw new Exception("Student is not allocated to any room!");
                    }
                    
                    // Deallocate
                    $stmt = $conn->prepare("UPDATE students SET room_id = NULL WHERE id = ?");
                    $stmt->bind_param("i", $student_id);
                    $stmt->execute();
                    
                    // Update room status to vacant
                    $stmt = $conn->prepare("UPDATE rooms SET status = 'vacant' WHERE id = ?");
                    $stmt->bind_param("i", $current_room['room_id']);
                    $stmt->execute();
                    
                    $conn->commit();
                    $message = "Room deallocated successfully!";
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get unallocated students
$unallocated_students = $conn->query("
    SELECT s.id, s.name, s.roll_no, s.department 
    FROM students s 
    WHERE s.room_id IS NULL 
    ORDER BY s.created_at ASC
");

// Get available rooms
$available_rooms = $conn->query("
    SELECT r.id, r.room_number, r.room_type, r.capacity, r.rent,
           COALESCE(COUNT(s.id), 0) as current_occupancy
    FROM rooms r 
    LEFT JOIN students s ON r.id = s.room_id 
    GROUP BY r.id
    HAVING current_occupancy < r.capacity
    ORDER BY r.room_number
");

// Get current allocations
$allocations = $conn->query("
    SELECT s.id as student_id, s.name, s.roll_no, s.department,
           r.id as room_id, r.room_number, r.room_type, r.rent,
           s.created_at as allocation_date
    FROM students s
    JOIN rooms r ON s.room_id = r.id
    ORDER BY r.room_number, s.name
");

// Get statistics
$total_allocations = $conn->query("SELECT COUNT(*) as count FROM students WHERE room_id IS NOT NULL")->fetch_assoc()['count'];
$pending_allocations = $conn->query("SELECT COUNT(*) as count FROM students WHERE room_id IS NULL")->fetch_assoc()['count'];
$occupied_rooms = $conn->query("SELECT COUNT(DISTINCT room_id) as count FROM students WHERE room_id IS NOT NULL")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Allocation - Hostel Management System</title>
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

        .btn-danger {
            background: #dc3545;
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

        .allocation-form {
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

        .form-group select {
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-group select:focus {
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

        .room-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .room-number {
            font-weight: 600;
            color: #667eea;
        }

        .room-details {
            color: #666;
            font-size: 0.9rem;
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

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .allocation-form {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔗 Room Allocation</h1>
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
                <div class="stat-number"><?php echo $total_allocations; ?></div>
                <div class="stat-label">Total Allocations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_allocations; ?></div>
                <div class="stat-label">Pending Allocations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $occupied_rooms; ?></div>
                <div class="stat-label">Occupied Rooms</div>
            </div>
        </div>

        <!-- Allocation Form -->
        <?php if ($unallocated_students->num_rows > 0 && $available_rooms->num_rows > 0): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Allocate Room</h2>
            </div>
            <div class="card-body">
                <form method="POST" class="allocation-form">
                    <input type="hidden" name="action" value="allocate">
                    
                    <div class="form-group">
                        <label for="student_id">Select Student</label>
                        <select name="student_id" id="student_id" required>
                            <option value="">Choose a student...</option>
                            <?php $unallocated_students->data_seek(0); ?>
                            <?php while ($student = $unallocated_students->fetch_assoc()): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['name']); ?> 
                                    (<?php echo htmlspecialchars($student['roll_no']); ?>) - 
                                    <?php echo htmlspecialchars($student['department']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="room_id">Select Room</label>
                        <select name="room_id" id="room_id" required>
                            <option value="">Choose a room...</option>
                            <?php $available_rooms->data_seek(0); ?>
                            <?php while ($room = $available_rooms->fetch_assoc()): ?>
                                <option value="<?php echo $room['id']; ?>">
                                    Room <?php echo htmlspecialchars($room['room_number']); ?> 
                                    (<?php echo ucfirst($room['room_type']); ?>) - 
                                    <?php echo $room['current_occupancy']; ?>/<?php echo $room['capacity']; ?> - 
                                    <?php echo number_format($room['rent'], 2); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Allocate Room</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Current Allocations -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Current Allocations</h2>
            </div>
            <div class="card-body">
                <?php if ($allocations->num_rows > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Room</th>
                                    <th>Monthly Rent</th>
                                    <th>Allocation Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($allocation = $allocations->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="student-info">
                                            <div class="student-name"><?php echo htmlspecialchars($allocation['name']); ?></div>
                                            <div class="student-details">
                                                <?php echo htmlspecialchars($allocation['roll_no']); ?> • 
                                                <?php echo htmlspecialchars($allocation['department']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="room-info">
                                            <div class="room-number">Room <?php echo htmlspecialchars($allocation['room_number']); ?></div>
                                            <div class="room-details"><?php echo ucfirst($allocation['room_type']); ?> Room</div>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($allocation['rent'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($allocation['allocation_date'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="deallocate">
                                            <input type="hidden" name="student_id" value="<?php echo $allocation['student_id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Are you sure you want to deallocate this room?')">
                                                Deallocate
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🏠</div>
                        <h3>No Room Allocations</h3>
                        <p>No students have been allocated rooms yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>