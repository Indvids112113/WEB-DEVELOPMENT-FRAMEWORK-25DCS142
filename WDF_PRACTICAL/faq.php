<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - FAQ</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dark-mode">
    <div class="top-bar-controls">
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
                <li><a href="faq.php" class="active">FAQ</a></li>
                <li><a href="contact.php">Contact</a></li>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['role'] === 'admin'): ?>
                        <li><a href="admin_registrations.php">Admin Panel</a></li>
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
        <h2>Frequently Asked Questions</h2>

        <div class="data-controls">
            <input type="text" id="searchFaq" placeholder="Search questions..." class="control-input">
            
            <select id="filterCategoryFaq" class="control-select">
                <option value="All">All Categories</option>
                <option value="Account">Account</option>
                <option value="Academic">Academic</option>
                <option value="Library">Library</option>
                <option value="Events">Events</option>
                <option value="Financial">Financial</option>
                <option value="Hostel">Hostel</option>
            </select>
        </div>
        
        <section>
            <div id="faqLoader" class="loader" style="display:none;">Loading FAQs...</div>
            <div id="faqError" class="error-msg" style="display:none; text-align:center;"></div>
            
            <div id="faqContainer">
                <!-- Fallback FAQ items so content displays cleanly -->
                <div class="faq-item" data-category="Account" style="background: var(--bg-color); border: 1px solid #444; padding: 15px; margin-bottom: 10px; border-radius: 6px;">
                    <h3 style="color: #3b82f6; margin-top:0;">How do I reset my password?</h3>
                    <p>You can use the forgot password link on the login page or contact admin support.</p>
                </div>
                <div class="faq-item" data-category="Academic" style="background: var(--bg-color); border: 1px solid #444; padding: 15px; margin-bottom: 10px; border-radius: 6px;">
                    <h3 style="color: #3b82f6; margin-top:0;">Where can I check my semester grades?</h3>
                    <p>Navigate to your student Dashboard to view all published grades and course results.</p>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>