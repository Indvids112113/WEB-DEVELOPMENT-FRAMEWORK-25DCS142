<?php
session_start();
require_once 'db.php';

// Fetch events from MySQL
try {
    $stmt = $pdo->query("SELECT * FROM events ORDER BY event_date ASC");
    $events = $stmt->fetchAll();
} catch (\PDOException $e) {
    $events = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Events</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dark-mode">
    <div class="top-bar-controls">
        <span class="auth-badge">Role: <?php echo isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'GUEST'; ?></span>
        <button id="themeToggleBtn" class="theme-toggle-btn">☀️ Light</button>
    </div>

    <header>
        <h1>StudentHub</h1>
        <p>Your centralized student portal</p>
    </header>
    
    <nav aria-label="Main Navigation">
        <div class="nav-container">
            <button id="hamburgerBtn" class="hamburger-btn" aria-label="Toggle navigation">☰</button>
            <ul id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="events.php" class="active">Events</a></li>
                <li><a href="faq.php">FAQ</a></li>
                <li><a href="contact.php">Contact</a></li>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['role'] === 'admin'): ?>
                        <li><a href="admin.php">Admin Panel</a></li>
                    <?php else: ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="profile.php">Profile</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" style="color: #ef4444; font-weight: bold;">Logout</a></li>
                <?php else: ?>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <main>
        <h2>Upcoming Campus Events</h2>

        <!-- Your exact search and filter control bar -->
        <div class="data-controls">
            <input type="text" id="searchEvents" placeholder="Search events by title..." class="control-input">
            
            <select id="filterCategoryEvents" class="control-select">
                <option value="All">All Categories</option>
                <option value="Workshop">Workshop</option>
                <option value="Tech">Tech</option>
                <option value="Cultural">Cultural</option>
                <option value="Sports">Sports</option>
            </select>
            
            <select id="sortEvents" class="control-select">
                <option value="dateAsc">Date: Nearest First</option>
                <option value="dateDesc">Date: Furthest First</option>
                <option value="titleAsc">Title: A-Z</option>
            </select>
        </div>
        
        <section>
            <div id="eventsLoader" class="loader" style="display:none;">Loading events data...</div>
            <div id="eventsError" class="error-msg" style="display:none;"></div>
            
            <div class="events-grid" id="eventsContainer">
                <?php if (!empty($events)): ?>
                    <?php foreach($events as $event): ?>
                        <article class="event-card" data-category="<?php echo htmlspecialchars($event['category']); ?>">
                            <h3 style="color: var(--primary-color); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p><strong>Date:</strong> <span class="event-date"><?php echo htmlspecialchars($event['event_date']); ?></span></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                            <p><strong>Category:</strong> <span class="auth-badge" style="color:var(--text-dark); border:1px solid var(--border-color);"><?php echo htmlspecialchars($event['category']); ?></span></p>
                            <p style="margin-top: 1rem;"><?php echo htmlspecialchars($event['description']); ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">No events found in the database.</div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>