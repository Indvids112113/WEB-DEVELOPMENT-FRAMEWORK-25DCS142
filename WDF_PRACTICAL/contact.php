<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Contact</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="top-bar-controls">
        <!-- Securely display the role from the PHP session -->
        <span class="auth-badge">Role: <?php echo isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'GUEST'; ?></span>
        <button id="themeToggleBtn" class="theme-toggle-btn">🌙 Dark</button>
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
                <li><a href="events.php">Events</a></li>
                <li><a href="faq.php">FAQ</a></li>
                <li><a href="contact.php" class="active">Contact</a></li>
                
                <!-- Practical 10: Dynamic Server-Side Navigation -->
                <?php if(isset($_SESSION['user_id'])): ?>
                    
                    <!-- Role-Based Navigation -->
                    <?php if($_SESSION['role'] === 'admin'): ?>
                        <li><a href="admin_registrations.php">Admin Panel</a></li>
                    <?php else: ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="profile.php">Profile</a></li>
                    <?php endif; ?>
                    
                    <!-- Secure PHP Logout Link -->
                    <li><a href="logout.php" style="color: #ef4444; font-weight: bold;">Logout</a></li>
                
                <?php else: ?>
                    
                    <!-- Guest Links -->
                    <li><a href="register.php">Register</a></li>
                    <li><a href="login.php">Login</a></li>
                    
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <main>
        <h2>Contact Administration</h2>
        
        <section style="max-width: 600px; margin: 0 auto; text-align: center;">
            <h3>Get in touch</h3>
            <address style="font-style: normal; line-height: 2;">
                <strong>StudentHub Support</strong><br>
                University Campus, Building A<br>
                Email: <a href="mailto:support@studenthub.edu" style="color: var(--primary-color);">support@studenthub.edu</a><br>
                Phone: (123) 456-7890
            </address>
        </section>
    </main>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>