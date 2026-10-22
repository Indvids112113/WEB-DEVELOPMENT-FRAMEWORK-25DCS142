<?php
session_start();
require_once 'db.php';

// Enforce Admin Role Access (Practical 14 - RBAC)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=unauthorized");
    exit;
}

$serverMessage = "";
$msgClass = "";

// Handle Student Deletion & Log Action (Practical 14)
if (isset($_GET['delete_student'])) {
    $delId = $_GET['delete_student'];
    try {
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$delId]);
        
        // Audit Log Entry
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $log = $pdo->prepare("INSERT INTO audit_logs (action, details, ip_address) VALUES (?, ?, ?)");
        $log->execute(['Delete Student', "Deleted student ID: $delId", $ip]);

        $serverMessage = "Student record deleted successfully.";
        $msgClass = "success-msg";
    } catch (\PDOException $e) {
        $serverMessage = "Error: " . $e->getMessage();
        $msgClass = "error-msg";
    }
}

// Fetch Dashboard Aggregation Statistics (Practical 14)
try {
    $totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $totalEvents = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $totalRegistrations = $pdo->query("SELECT COUNT(*) FROM event_registrations")->fetchColumn();
} catch (\PDOException $e) {
    $totalStudents = 0;
    $totalEvents = 0;
    $totalRegistrations = 0;
}

// Fetch Audit Logs (Practical 14)
try {
    $auditStmt = $pdo->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 5");
    $auditLogs = $auditStmt->fetchAll();
} catch (\PDOException $e) {
    $auditLogs = [];
}

// Fetch Students List
$searchStudent = $_GET['search_student'] ?? '';
$studentQuery = "SELECT id, name, email, course, year, status FROM students WHERE (name LIKE ? OR id LIKE ?)";
$stmtStudents = $pdo->prepare($studentQuery);
$stmtStudents->execute(["%$searchStudent%", "%$searchStudent%"]);
$students = $stmtStudents->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Admin Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dark-mode">
    <div class="top-bar-controls">
        <span class="auth-badge">Role: ADMIN</span>
        <button id="themeToggleBtn" class="theme-toggle-btn">☀️ Light</button>
    </div>

    <header>
        <h1>StudentHub</h1>
        <p>Admin Dashboard & Monitoring (Practicals 13 & 14)</p>
    </header>
    
    <nav aria-label="Main Navigation">
        <div class="nav-container">
            <button id="hamburgerBtn" class="hamburger-btn">☰</button>
            <ul id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="admin.php" class="active">Admin Dashboard</a></li>
                <li><a href="logout.php" style="color: #ef4444; font-weight: bold;">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main>
        <h2>System Statistics & Monitoring</h2>

        <?php if (!empty($serverMessage)): ?>
            <div class="<?php echo $msgClass; ?>" style="margin-bottom: 1.5rem; text-align: center; padding: 10px; border-radius: 4px; background: rgba(0,0,0,0.2);"><?php echo htmlspecialchars($serverMessage); ?></div>
        <?php endif; ?>

        <!-- PRACTICAL 14: STATISTICS CARDS -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
            <div style="background: var(--card-bg); padding: 1.5rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); text-align: center;">
                <h3 style="color: var(--primary-color); font-size: 2rem; margin-bottom: 0.5rem;"><?php echo $totalStudents; ?></h3>
                <p style="font-weight: 600;">Total Registered Students</p>
            </div>
            <div style="background: var(--card-bg); padding: 1.5rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); text-align: center;">
                <h3 style="color: #10b981; font-size: 2rem; margin-bottom: 0.5rem;"><?php echo $totalEvents; ?></h3>
                <p style="font-weight: 600;">Active Campus Events</p>
            </div>
            <div style="background: var(--card-bg); padding: 1.5rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); text-align: center;">
                <h3 style="color: #f59e0b; font-size: 2rem; margin-bottom: 0.5rem;"><?php echo $totalRegistrations; ?></h3>
                <p style="font-weight: 600;">Total Event Signups</p>
            </div>
        </div>

        <!-- PRACTICAL 11 & 14: STUDENT DIRECTORY & CRUD -->
        <section style="margin-bottom: 3rem;">
            <h3>Student Directory & Management</h3>
            
            <form method="GET" action="admin.php" class="data-controls">
                <input type="text" name="search_student" placeholder="Search by name or ID..." value="<?php echo htmlspecialchars($searchStudent); ?>" class="control-input">
                <button type="submit" style="width: auto; padding: 0.5rem 1.5rem;">Search</button>
            </form>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
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
                                        <span class="status-badge <?php echo $st['status'] === 'Active' ? 'status-active' : 'status-pending'; ?>">
                                            <?php echo htmlspecialchars($st['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="admin.php?delete_student=<?php echo $st['id']; ?>" onclick="return confirm('Delete this record?');" style="color: #ef4444; text-decoration: none; font-weight: bold;">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center;">No students found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- PRACTICAL 14: AUDIT LOGS MONITORING -->
        <section>
            <h3>Security & Audit Trail Logs</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Log ID</th>
                            <th>Action Performed</th>
                            <th>Details</th>
                            <th>IP Address</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($auditLogs) > 0): ?>
                            <?php foreach($auditLogs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['id']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($log['action']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                    <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center;">No audit logs recorded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>