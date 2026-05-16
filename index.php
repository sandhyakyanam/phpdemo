<?php
// task_manager.php - Complete Task Manager in one file

// Database connection (using SQLite for easy setup)
try {
    $pdo = new PDO('sqlite:tasks.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tasks table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle form submissions
$message = '';
$messageType = '';

// Add Task
if (isset($_POST['add_task'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    if (!empty($title)) {
        $stmt = $pdo->prepare("INSERT INTO tasks (title, description) VALUES (?, ?)");
        if ($stmt->execute([$title, $description])) {
            $message = "Task added successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to add task.";
            $messageType = "error";
        }
    } else {
        $message = "Title is required!";
        $messageType = "error";
    }
}

// Update Task Status
if (isset($_POST['update_status'])) {
    $taskId = $_POST['task_id'];
    $newStatus = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    if ($stmt->execute([$newStatus, $taskId])) {
        $message = "Task status updated!";
        $messageType = "success";
    }
}

// Delete Task
if (isset($_GET['delete'])) {
    $taskId = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    if ($stmt->execute([$taskId])) {
        $message = "Task deleted successfully!";
        $messageType = "success";
    }
}

// Edit Task (Get data for editing)
$editTask = null;
if (isset($_GET['edit'])) {
    $taskId = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    $editTask = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Update Task (Edit)
if (isset($_POST['edit_task'])) {
    $taskId = $_POST['task_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    if (!empty($title)) {
        $stmt = $pdo->prepare("UPDATE tasks SET title = ?, description = ? WHERE id = ?");
        if ($stmt->execute([$title, $description, $taskId])) {
            $message = "Task updated successfully!";
            $messageType = "success";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } else {
        $message = "Title is required!";
        $messageType = "error";
    }
}

// Get all tasks
$stmt = $pdo->query("SELECT * FROM tasks ORDER BY created_at DESC");
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager - PHP Project</title>
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
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .content {
            padding: 30px;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: none;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .form-section h2 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        
        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        .tasks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .tasks-table th,
        .tasks-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .tasks-table th {
            background: #667eea;
            color: white;
        }
        
        .tasks-table tr:hover {
            background: #f5f5f5;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        
        .status-pending {
            background: #ffc107;
            color: #856404;
        }
        
        .status-completed {
            background: #28a745;
            color: white;
        }
        
        .status-in-progress {
            background: #17a2b8;
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
            text-decoration: none;
            border-radius: 3px;
        }
        
        .btn-edit {
            background: #28a745;
            color: white;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .status-form {
            display: inline-block;
            margin-left: 10px;
        }
        
        .status-select {
            padding: 5px;
            font-size: 12px;
            width: auto;
            display: inline-block;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .tasks-table {
                display: block;
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Task Manager</h1>
            <p>Organize your tasks efficiently</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($editTask): ?>
                <!-- Edit Task Form -->
                <div class="form-section">
                    <h2>✏️ Edit Task</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="task_id" value="<?php echo $editTask['id']; ?>">
                        <div class="form-group">
                            <label>Task Title *</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($editTask['title']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description"><?php echo htmlspecialchars($editTask['description']); ?></textarea>
                        </div>
                        <button type="submit" name="edit_task">Update Task</button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" style="margin-left: 10px; text-decoration: none; color: #666;">Cancel</a>
                    </form>
                </div>
            <?php else: ?>
                <!-- Add Task Form -->
                <div class="form-section">
                    <h2>➕ Add New Task</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Task Title *</label>
                            <input type="text" name="title" placeholder="Enter task title" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" placeholder="Enter task description (optional)"></textarea>
                        </div>
                        <button type="submit" name="add_task">Add Task</button>
                    </form>
                </div>
            <?php endif; ?>
            
            <!-- Tasks List -->
            <h2>📋 Your Tasks</h2>
            <?php if (count($tasks) > 0): ?>
                <table class="tasks-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><?php echo $task['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($task['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($task['description'] ?: '-'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $task['status']; ?>">
                                        <?php echo ucfirst($task['status']); ?>
                                    </span>
                                    
                                    <form method="POST" action="" class="status-form">
                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                        <select name="status" class="status-select" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $task['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="in-progress" <?php echo $task['status'] == 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="completed" <?php echo $task['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                        <input type="submit" name="update_status" value="Update" style="display: none;">
                                    </form>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($task['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit=<?php echo $task['id']; ?>" class="btn-small btn-edit">Edit</a>
                                        <a href="?delete=<?php echo $task['id']; ?>" class="btn-small btn-delete" onclick="return confirm('Are you sure you want to delete this task?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>No tasks yet. Create your first task above! 🎯</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Auto-hide message after 3 seconds
        setTimeout(function() {
            const message = document.querySelector('.message');
            if (message) {
                message.style.display = 'none';
            }
        }, 3000);
    </script>
</body>
</html>