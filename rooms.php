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
            case 'add':
                $room_number = trim($_POST['room_number']);
                $room_type = $_POST['room_type'];
                $capacity = intval($_POST['capacity']);
                $rent = floatval($_POST['rent']);
                
                $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_type, capacity, rent) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssid", $room_number, $room_type, $capacity, $rent);
                
                if ($stmt->execute()) {
                    $message = "Room added successfully!";
                } else {
                    $error = "Error adding room: " . $conn->error;
                }
                $stmt->close();
                break;
                
            case 'edit':
                $id = intval($_POST['id']);
                $room_number = trim($_POST['room_number']);
                $room_type = $_POST['room_type'];
                $capacity = intval($_POST['capacity']);
                $rent = floatval($_POST['rent']);
                
                $stmt = $conn->prepare("UPDATE rooms SET room_number = ?, room_type = ?, capacity = ?, rent = ? WHERE id = ?");
                $stmt->bind_param("ssidi", $room_number, $room_type, $capacity, $rent, $id);
                
                if ($stmt->execute()) {
                    $message = "Room updated successfully!";
                } else {
                    $error = "Error updating room: " . $conn->error;
                }
                $stmt->close();
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                
                // Check if room is occupied
                $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE room_id = ?");
                $check_stmt->bind_param("i", $id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $count = $result->fetch_assoc()['count'];
                
                if ($count > 0) {
                    $error = "Cannot delete room: Room is currently occupied!";
                } else {
                    $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        $message = "Room deleted successfully!";
                    } else {
                        $error = "Error deleting room: " . $conn->error;
                    }
                    $stmt->close();
                }
                $check_stmt->close();
                break;
        }
    }
}

// Get all rooms with occupancy info
$rooms_query = "
    SELECT r.*, 
           COALESCE(s.occupied_count, 0) as occupied_count,
           CASE 
               WHEN COALESCE(s.occupied_count, 0) >= r.capacity THEN 'occupied'
               ELSE 'vacant'
           END as actual_status
    FROM rooms r
    LEFT JOIN (
        SELECT room_id, COUNT(*) as occupied_count
        FROM students 
        WHERE room_id IS NOT NULL 
        GROUP BY room_id
    ) s ON r.id = s.room_id
    ORDER BY r.room_number
";
$rooms = $conn->query($rooms_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management - Hostel Management System</title>
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

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #667eea;
            color: white;
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
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
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
        }

        .card-title {
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
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
            transition: border-color 0.3s ease;
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
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-vacant {
            background: #d4edda;
            color: #155724;
        }

        .status-occupied {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .btn-edit {
            background: #ffc107;
            color: #212529;
        }

        .btn-delete {
            background: #dc3545;
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

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .close {
            font-size: 2rem;
            cursor: pointer;
            color: #999;
        }

        .close:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏠 Room Management</h1>
        <div class="header-actions">
            <button onclick="showAddModal()" class="btn btn-primary">Add New Room</button>
            <a href="admin_dashboard.php" class="btn btn-secondary">← Dashboard</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">All Rooms</h2>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Room Number</th>
                                <th>Type</th>
                                <th>Capacity</th>
                                <th>Occupied</th>
                                <th>Rent</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($room = $rooms->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($room['room_number']); ?></strong></td>
                                <td><?php echo ucfirst($room['room_type']); ?></td>
                                <td><?php echo $room['capacity']; ?></td>
                                <td><?php echo $room['occupied_count']; ?>/<?php echo $room['capacity']; ?></td>
                                <td><?php echo number_format($room['rent'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $room['actual_status']; ?>">
                                        <?php echo ucfirst($room['actual_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="editRoom(<?php echo htmlspecialchars(json_encode($room)); ?>)" class="btn btn-edit btn-sm">Edit</button>
                                        <button onclick="deleteRoom(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>')" class="btn btn-delete btn-sm">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Room Modal -->
    <div id="roomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Room</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="roomForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="roomId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="room_number">Room Number</label>
                        <input type="text" name="room_number" id="room_number" required>
                    </div>
                    <div class="form-group">
                        <label for="room_type">Room Type</label>
                        <select name="room_type" id="room_type" required>
                            <option value="single">Single</option>
                            <option value="double">Double</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="capacity">Capacity</label>
                        <input type="number" name="capacity" id="capacity" min="1" max="4" required>
                    </div>
                    <div class="form-group">
                        <label for="rent">Monthly Rent</label>
                        <input type="number" name="rent" id="rent" step="0.01" min="0" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Save Room</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Room';
            document.getElementById('formAction').value = 'add';
            document.getElementById('roomForm').reset();
            document.getElementById('roomModal').style.display = 'block';
        }

        function editRoom(room) {
            document.getElementById('modalTitle').textContent = 'Edit Room';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('roomId').value = room.id;
            document.getElementById('room_number').value = room.room_number;
            document.getElementById('room_type').value = room.room_type;
            document.getElementById('capacity').value = room.capacity;
            document.getElementById('rent').value = room.rent;
            document.getElementById('roomModal').style.display = 'block';
        }

        function deleteRoom(id, roomNumber) {
            if (confirm('Are you sure you want to delete room ' + roomNumber + '?')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        function closeModal() {
            document.getElementById('roomModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('roomModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Auto-set capacity based on room type
        document.getElementById('room_type').addEventListener('change', function() {
            const capacity = document.getElementById('capacity');
            if (this.value === 'single') {
                capacity.value = 1;
            } else if (this.value === 'double') {
                capacity.value = 2;
            }
        });
    </script>
</body>
</html>