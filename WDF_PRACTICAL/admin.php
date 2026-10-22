<?php
session_start();
require_once 'db.php';

// Enforce Admin Role Access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=unauthorized");
    exit;
}

$serverMessage = "";
$msgClass = "";

// Handle Delete Request
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    try {
        $delStmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        if ($delStmt->execute([$deleteId])) {
            $serverMessage = "Student record deleted successfully.";
            $msgClass = "success-msg";
        }
    } catch (\PDOException $e) {
        $serverMessage = "Error deleting record: " . $e->getMessage();
        $msgClass = "error-msg";
    }
}

// Handle Add/Edit Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $id = trim($_POST['student_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $course = trim($_POST['course']);
    $year = intval($_POST['year']);
    $status = trim($_POST['status']);

    if ($_POST['action'] === 'add') {
        $password = password_hash('Student@123', PASSWORD_DEFAULT); // Default temporary password
        try {
            $stmt = $pdo->prepare("INSERT INTO students (id, name, email, password, course, year, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$id, $name, $email, $password, $course, $year, $status])) {
                $serverMessage = "Student added successfully.";
                $msgClass = "success-msg";
            }
        } catch (\PDOException $e) {
            $serverMessage = "Error: Student ID or Email already exists.";
            $msgClass = "error-msg";
        }
    } elseif ($_POST['action'] === 'edit') {
        try {
            $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, course = ?, year = ?, status = ? WHERE id = ?");
            if ($stmt->execute([$name, $email, $course, $year, $status, $id])) {
                $serverMessage = "Student record updated successfully.";
                $msgClass = "success-msg";
            }
        } catch (\PDOException $e) {
            $serverMessage = "Error updating record: " . $e->getMessage();
            $msgClass = "error-msg";
        }
    }
}

// Fetch Students with Search and Filter Logic
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'All';

$query = "SELECT id, name, email, course, year, status, created_content FROM students WHERE (name LIKE ? OR id LIKE ?)";
$params = ["%$search%", "%$search%"];

if ($statusFilter !== 'All') {
    $query .= " AND status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY created_content DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Admin Panel</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dark-mode">
    <div class="top-bar-controls">
        <span class="auth-badge">Role: ADMIN</span>
        <button id="themeToggleBtn" class="theme-toggle-btn">☀️ Light</button>
    </div>

    <header>
        <h1>StudentHub</h1>
        <p>Admin Control Panel - Student Management</p>
    </header>
    
    <nav aria-label="Main Navigation">
        <div class="nav-container">
            <button id="hamburgerBtn" class="hamburger-btn">☰</button>
            <ul id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="admin.php" class="active">Admin Panel</a></li>
                <li><a href="logout.php" style="color: #ef4444; font-weight: bold;">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main>
        <h2>Student Records CRUD</h2>

        <?php if (!empty($serverMessage)): ?>
            <div class="<?php echo $msgClass; ?>" style="margin-bottom: 1.5rem; text-align: center;"><?php echo htmlspecialchars($serverMessage); ?></div>
        <?php endif; ?>

        <!-- Search and Filter Controls -->
        <form method="GET" action="admin.php" class="data-controls">
            <input type="text" name="search" placeholder="Search by name or ID..." value="<?php echo htmlspecialchars($search); ?>" class="control-input">
            
            <select name="status" class="control-select">
                <option value="All" <?php if($statusFilter=='All') echo 'selected'; ?>>All Statuses</option>
                <option value="Active" <?php if($statusFilter=='Active') echo 'selected'; ?>>Active</option>
                <option value="Pending" <?php if($statusFilter=='Pending') echo 'selected'; ?>>Pending</option>
                <option value="Suspended" <?php if($statusFilter=='Suspended') echo 'selected'; ?>>Suspended</option>
            </select>
            
            <button type="submit" style="width: auto; padding: 0.5rem 1.5rem;">Filter</button>
        </form>

        <!-- Students Table -->
        <section style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($students) > 0): ?>
                        <?php foreach($students as $st): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($st['id']); ?></td>
                                <td><?php echo htmlspecialchars($st['name']); ?></td>
                                <td><?php echo htmlspecialchars($st['email']); ?></td>
                                <td><?php echo htmlspecialchars($st['course']); ?></td>
                                <td>Year <?php echo htmlspecialchars($st['year']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $st['status'] === 'Active' ? 'status-active' : ($st['status'] === 'Pending' ? 'status-pending' : 'status-suspended'); ?>">
                                        <?php echo htmlspecialchars($st['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="admin.php?delete_id=<?php echo $st['id']; ?>" onclick="return confirm('Are you sure you want to delete this student record?');" style="color: #ef4444; text-decoration: none; font-weight: bold; margin-right: 10px;">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center;">No student records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>