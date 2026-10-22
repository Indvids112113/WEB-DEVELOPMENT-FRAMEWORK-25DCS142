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

// 1. Handle Student Delete
if (isset($_GET['delete_student'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$_GET['delete_student']]);
        $serverMessage = "Student record deleted successfully.";
        $msgClass = "success-msg";
    } catch (\PDOException $e) {
        $serverMessage = "Error deleting student: " . $e->getMessage();
        $msgClass = "error-msg";
    }
}

// 2. Handle Event Delete
if (isset($_GET['delete_event'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$_GET['delete_event']]);
        $serverMessage = "Event deleted successfully.";
        $msgClass = "success-msg";
    } catch (\PDOException $e) {
        $serverMessage = "Error deleting event: " . $e->getMessage();
        $msgClass = "error-msg";
    }
}

// 3. Handle Add Student Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_type']) && $_POST['action_type'] === 'student_save') {
    $id = trim($_POST['student_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $course = trim($_POST['course']);
    $year = intval($_POST['year']);
    $status = trim($_POST['status']);
    $password = password_hash('Student@123', PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO students (id, name, email, password, course, year, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $name, $email, $password, $course, $year, $status]);
        $serverMessage = "Student added successfully.";
        $msgClass = "success-msg";
    } catch (\PDOException $e) {
        $serverMessage = "Error: Student ID or Email already exists.";
        $msgClass = "error-msg";
    }
}

// 4. Handle Add Event Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_type']) && $_POST['action_type'] === 'event_save') {
    $title = trim($_POST['event_title']);
    $date = trim($_POST['event_date']);
    $location = trim($_POST['event_location']);
    $category = trim($_POST['event_category']);
    $description = trim($_POST['event_description']);

    try {
        $stmt = $pdo->prepare("INSERT INTO events (title, event_date, location, category, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $date, $location, $category, $description]);
        $serverMessage = "Event added successfully.";
        $msgClass = "success-msg";
    } catch (\PDOException $e) {
        $serverMessage = "Database Error: " . $e->getMessage();
        $msgClass = "error-msg";
    }
}

// Fetch Students with Search and Filter Logic
$searchStudent = $_GET['search_student'] ?? '';
$statusFilter = $_GET['status_filter'] ?? 'All';

$studentQuery = "SELECT id, name, email, course, year, status FROM students WHERE (name LIKE ? OR id LIKE ?)";
$studentParams = ["%$searchStudent%", "%$searchStudent%"];

if ($statusFilter !== 'All') {
    $studentQuery .= " AND status = ?";
    $studentParams[] = $statusFilter;
}

$stmtStudents = $pdo->prepare($studentQuery);
$stmtStudents->execute($studentParams);
$students = $stmtStudents->fetchAll();

// Fetch Events for Admin View
$stmtEvents = $pdo->query("SELECT * FROM events ORDER BY event_date ASC");
$events = $stmtEvents->fetchAll();
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
    <p>Admin Control Panel </p>
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
        <h2>Administrative Dashboard</h2>

        <?php if (!empty($serverMessage)): ?>
            <div class="<?php echo $msgClass; ?>" style="margin-bottom: 1.5rem; text-align: center; padding: 10px; border-radius: 4px; background: rgba(0,0,0,0.2);"><?php echo htmlspecialchars($serverMessage); ?></div>
        <?php endif; ?>

        <!-- PRACTICAL 11: STUDENT MANAGEMENT -->
        <section style="margin-bottom: 3rem;">
            <h3>Student Management & Search</h3>
            
            <form method="GET" action="admin.php" class="data-controls">
                <input type="text" name="search_student" placeholder="Search by name or ID..." value="<?php echo htmlspecialchars($searchStudent); ?>" class="control-input">
                
                <select name="status_filter" class="control-select">
                    <option value="All" <?php if($statusFilter=='All') echo 'selected'; ?>>All Statuses</option>
                    <option value="Active" <?php if($statusFilter=='Active') echo 'selected'; ?>>Active</option>
                    <option value="Pending" <?php if($statusFilter=='Pending') echo 'selected'; ?>>Pending</option>
                    <option value="Suspended" <?php if($statusFilter=='Suspended') echo 'selected'; ?>>Suspended</option>
                </select>
                
                <button type="submit" style="width: auto; padding: 0.5rem 1.5rem;">Filter</button>
            </form>

            <div style="overflow-x: auto;">
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
                                        <a href="admin.php?delete_student=<?php echo $st['id']; ?>" onclick="return confirm('Delete this student record?');" style="color: #ef4444; text-decoration: none; font-weight: bold;">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center;">No student records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add Student Form -->
            <div style="margin-top: 2rem; background: var(--card-bg); padding: 1.5rem; border-radius: var(--border-radius); border: 1px solid var(--border-color);">
                <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Add New Student</h4>
                <form method="POST" action="admin.php" style="max-width: 100%;">
                    <input type="hidden" name="action_type" value="student_save">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div>
                            <label>Student ID:</label>
                            <input type="text" name="student_id" required class="control-input" style="width:100%;">
                        </div>
                        <div>
                            <label>Full Name:</label>
                            <input type="text" name="name" required class="control-input" style="width:100%;">
                        </div>
                        <div>
                            <label>Email Address:</label>
                            <input type="email" name="email" required class="control-input" style="width:100%;">
                        </div>
                        <div>
                            <label>Course:</label>
                            <select name="course" required class="control-select" style="width:100%;">
                                <option value="BTech CSE">B.Tech CSE</option>
                                <option value="BTech IT">B.Tech IT</option>
                                <option value="BTech CE">B.Tech CE</option>
                            </select>
                        </div>
                        <div>
                            <label>Year:</label>
                            <select name="year" required class="control-select" style="width:100%;">
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                        <div>
                            <label>Status:</label>
                            <select name="status" required class="control-select" style="width:100%;">
                                <option value="Active">Active</option>
                                <option value="Pending">Pending</option>
                                <option value="Suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" style="margin-top: 1rem; width: auto; padding: 0.5rem 2rem;">Add Student</button>
                </form>
            </div>
        </section>

        <!-- PRACTICAL 12: EVENT MANAGEMENT -->
        <section>
            <h3>Event Management</h3>
            
            <div style="overflow-x: auto; margin-bottom: 2rem;">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($events) > 0): ?>
                            <?php foreach($events as $ev): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ev['title']); ?></td>
                                    <td><?php echo htmlspecialchars($ev['event_date']); ?></td>
                                    <td><?php echo htmlspecialchars($ev['location']); ?></td>
                                    <td><?php echo htmlspecialchars($ev['category']); ?></td>
                                    <td><?php echo htmlspecialchars($ev['description']); ?></td>
                                    <td>
                                        <a href="admin.php?delete_event=<?php echo $ev['id']; ?>" onclick="return confirm('Delete this event?');" style="color: #ef4444; text-decoration: none; font-weight: bold;">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center;">No events found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add Event Form -->
            <div style="background: var(--card-bg); padding: 1.5rem; border-radius: var(--border-radius); border: 1px solid var(--border-color);">
                <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Create New Event</h4>
                <form method="POST" action="admin.php" style="max-width: 100%;">
                    <input type="hidden" name="action_type" value="event_save">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label>Event Title:</label>
                            <input type="text" name="event_title" required class="control-input" style="width:100%;">
                        </div>
                        <div>
                            <label>Event Date:</label>
                            <input type="date" name="event_date" required class="control-input" style="width:100%;">
                        </div>
                        <div>
                            <label>Location:</label>
                            <input type="text" name="event_location" required class="control-input" style="width:100%;">
                        </div>
                        <div>
                            <label>Category:</label>
                            <select name="event_category" required class="control-select" style="width:100%;">
                                <option value="Tech">Tech</option>
                                <option value="Workshop">Workshop</option>
                                <option value="Cultural">Cultural</option>
                                <option value="Sports">Sports</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label>Description:</label>
                        <textarea name="event_description" rows="3" required class="control-input" style="width:100%;"></textarea>
                    </div>
                    <button type="submit" style="width: auto; padding: 0.5rem 2rem;">Add Event</button>
                </form>
            </div>
        </section>
    </main>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>