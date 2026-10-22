<?php
require_once 'auth_check.php';

try {
    $stmt = $pdo->prepare("SELECT id, name, email, course, year, status, last_login, created_at FROM students WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();
} catch (\PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Profile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dark-mode">
    <div class="top-bar-controls">
        <span class="auth-badge">Role: <?php echo strtoupper($_SESSION['role']); ?></span>
        <button id="themeToggleBtn" class="theme-toggle-btn">🌙 Dark</button>
    </div>

    <header>
        <h1>StudentHub</h1>
        <p>Your Student Profile</p>
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="profile.php" class="active">Profile</a></li>
                <li><a href="logout.php" style="color: #ef4444; font-weight: bold;">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main style="max-width: 600px; margin: 2rem auto; padding: 20px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px;">
        <h2>My Profile</h2>
        
        <?php if ($student): ?>
            <ul style="list-style: none; padding: 0; line-height: 2.2;">
                <li><strong>Student ID:</strong> <?php echo htmlspecialchars($student['id']); ?></li>
                <li><strong>Full Name:</strong> <?php echo htmlspecialchars($student['name']); ?></li>
                <li><strong>Email Address:</strong> <?php echo htmlspecialchars($student['email']); ?></li>
                <li><strong>Course:</strong> <?php echo htmlspecialchars($student['course']); ?></li>
                <li><strong>Year:</strong> Year <?php echo htmlspecialchars($student['year']); ?></li>
                <li><strong>Account Status:</strong> <span style="color: #10b981; font-weight: bold;"><?php echo htmlspecialchars($student['status']); ?></span></li>
                <li><strong>Last Login:</strong> <?php echo htmlspecialchars($student['last_login'] ?? 'First login / Not recorded'); ?></li>
                <li><strong>Member Since:</strong> <?php echo htmlspecialchars($student['created_at']); ?></li>
            </ul>
        <?php else: ?>
            <p>Profile information could not be retrieved.</p>
        <?php endif; ?>
    </main>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>