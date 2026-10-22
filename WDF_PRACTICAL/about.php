<?php
session_start();
require_once 'db.php';

// Practical 10: Strict Role-Based Access Control (Admin Only)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // If not logged in OR not an admin, kick them out
    header("Location: login.php?error=unauthorized");
    exit;
}

// SQL JOIN to combine Students, Events, and Registrations for the Admin View
$sql = "
    SELECT 
        s.name AS student_name, 
        s.id AS student_id, 
        s.course AS department, 
        s.year AS study_year, 
        e.title AS event_name, 
        r.registered_at
    FROM event_registrations r
    JOIN students s ON r.student_id = s.id
    JOIN events e ON r.event_id = e.id
    ORDER BY r.registered_at DESC
";

try {
    $stmt = $pdo->query($sql);
    $registrations = $stmt->fetchAll();
} catch (\PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Admin Panel</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; color: #eee; }
        th, td { border: 1px solid #555; padding: 12px; text-align: left; }
        th { background-color: #3b82f6; color: white; }
        tr:nth-child(even) { background-color: #1f2937; }
    </style>
</head>
<body class="dark-mode">
    <div class="top-bar-controls">
        <span class="auth-badge">Role: <?php echo strtoupper($_SESSION['role']); ?></span>
        <button id="themeToggleBtn" class="theme-toggle-btn">🌙 Dark</button>
    </div>

    <header>
        <h1>StudentHub Administration</h1>
        <p>Event Registrations Report</p>
    </header>
    
    <nav aria-label="Main Navigation">
        <div class="nav-container">
            <button id="hamburgerBtn" class="hamburger-btn">☰</button>
            <ul id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="faq.php">FAQ</a></li>
                <li><a href="contact.php">Contact</a></li>
                
                <!-- We know they are an Admin if they made it past the PHP check at the top -->
                <li><a href="admin_registrations.php" class="active">Admin Panel</a></li>
                <li><a href="logout.php" style="color: #ef4444; font-weight: bold;">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main style="max-width: 1200px; margin: 2rem auto; padding: 1rem;">
        <h2>All Event Registrations</h2>
        
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>Department</th>
                    <th>Year</th>
                    <th>Event Registered For</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($registrations) > 0): ?>
                    <?php foreach($registrations as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                            <td><?php echo htmlspecialchars($row['study_year']); ?></td>
                            <td><?php echo htmlspecialchars($row['event_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['registered_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">No registrations found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>