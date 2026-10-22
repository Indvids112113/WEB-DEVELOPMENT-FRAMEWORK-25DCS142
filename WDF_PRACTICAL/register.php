<?php
session_start();
require_once 'db.php';
$serverMessage = "";
$msgClass = "";

// Generate CSRF Token 
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $serverMessage = "Security Error: Invalid CSRF token.";
        $msgClass = "error-msg server-msg";
    } else {
        // 2. Sanitize Inputs
        $name = htmlspecialchars(trim($_POST["fullName"]));
        $studentId = htmlspecialchars(trim($_POST["studentId"]));
        $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
        $mobile = htmlspecialchars(trim($_POST["mobile"]));
        $course = htmlspecialchars(trim($_POST["course"]));
        $year = htmlspecialchars(trim($_POST["year"]));
        $password = $_POST["password"]; 

        // 3. Server-Side Validation
        if (empty($name) || empty($studentId) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
            $serverMessage = "Error: Invalid data submitted. Please check your inputs.";
            $msgClass = "error-msg server-msg";
        } else {
            try {
                // 4. Duplicate Check
                $checkStmt = $pdo->prepare("SELECT id FROM students WHERE id = ? OR email = ?");
                $checkStmt->execute([$studentId, $email]);
                
                if ($checkStmt->rowCount() > 0) {
                    $serverMessage = "Error: Student ID or Email is already registered.";
                    $msgClass = "error-msg server-msg";
                } else {
                    // 5. Password Hashing
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                    // 6. Database Insertion
                    $sql = "INSERT INTO students (id, name, email, password, status, course, year) 
                            VALUES (?, ?, ?, ?, 'Active', ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    
                    if ($stmt->execute([$studentId, $name, $email, $hashedPassword, $course, $year])) {
                        $serverMessage = "Registration successful! Your account has been created.";
                        $msgClass = "success-msg server-msg";
                        
                        // 7. Audit Log Entry
                        $auditSql = "INSERT INTO audit_logs (action, details, ip_address) VALUES (?, ?, ?)";
                        $auditStmt = $pdo->prepare($auditSql);
                        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
                        $auditStmt->execute(['User Registration', "Registered Student ID: $studentId, Email: $email", $ip]);
                        
                    } else {
                        $serverMessage = "System Error: Could not save to the database.";
                        $msgClass = "error-msg server-msg";
                    }
                }
            } catch (\PDOException $e) {
                $serverMessage = "Database Error: " . $e->getMessage();
                $msgClass = "error-msg server-msg";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Register</title>
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
            <button id="hamburgerBtn" class="hamburger-btn">☰</button>
            <ul id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="faq.php">FAQ</a></li>
                <li><a href="contact.php">Contact</a></li>
                
                <!-- Practical 10 Dynamic Server-Side Navigation Menu -->
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['role'] === 'admin'): ?>
                        <li><a href="admin.php">Admin Panel</a></li>
                    <?php else: ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="profile.php">Profile</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" style="color: #ef4444; font-weight: bold;">Logout</a></li>
                <?php else: ?>
                    <li><a href="register.php" class="active">Register</a></li>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <main>
        <section>
            <h2>Create an Account</h2>
            
            <?php if (!empty($serverMessage)): ?>
                <div class="<?php echo $msgClass; ?>" style="padding: 1rem; border: 1px solid; margin-bottom: 1rem; text-align: center; border-radius: 4px;">
                    <?php echo htmlspecialchars($serverMessage); ?>
                </div>
            <?php endif; ?>

            <form id="registerForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <fieldset>
                    <legend>Personal Information</legend>
                    <div class="form-group">
                        <label for="fullName">Full Name:</label>
                        <input type="text" id="fullName" name="fullName" aria-required="true">
                        <span class="error-msg" id="fullNameError"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="studentId">Student ID:</label>
                        <input type="text" id="studentId" name="studentId" aria-required="true">
                        <span class="error-msg" id="studentIdError"></span>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address:</label>
                        <input type="email" id="email" name="email" aria-required="true">
                        <span class="error-msg" id="emailError"></span>
                    </div>

                    <div class="form-group">
                        <label for="mobile">Mobile Number:</label>
                        <input type="tel" id="mobile" name="mobile" aria-required="true">
                        <span class="error-msg" id="mobileError"></span>
                    </div>

                    <div class="form-group row-group">
                        <label>Gender:</label>
                        <div class="radio-group">
                            <input type="radio" id="genderMale" name="gender" value="Male">
                            <label for="genderMale" class="inline-label">Male</label>
                            
                            <input type="radio" id="genderFemale" name="gender" value="Female">
                            <label for="genderFemale" class="inline-label">Female</label>
                            
                            <input type="radio" id="genderOther" name="gender" value="Other">
                            <label for="genderOther" class="inline-label">Other</label>
                        </div>
                        <span class="error-msg" id="genderError"></span>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Academic & Security</legend>
                    <div class="form-group">
                        <label for="course">Course:</label>
                        <select id="course" name="course" aria-required="true">
                            <option value="">Select a Course</option>
                            <option value="BTech CSE">B.Tech CSE</option>
                            <option value="BTech IT">B.Tech IT</option>
                            <option value="BTech CE">B.Tech CE</option>
                        </select>
                        <span class="error-msg" id="courseError"></span>
                    </div>

                    <div class="form-group">
                        <label for="year">Year of Study:</label>
                        <select id="year" name="year" aria-required="true">
                            <option value="">Select Year</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                        <span class="error-msg" id="yearError"></span>
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" aria-required="true">
                        <div class="strength-meter-container">
                            <div class="strength-meter" id="strengthBar"></div>
                        </div>
                        <small id="strengthText" class="strength-text"></small>
                        <span class="error-msg" id="passwordError"></span>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password:</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" aria-required="true">
                        <span class="error-msg" id="confirmPasswordError"></span>
                    </div>
                </fieldset>

                <div class="form-group checkbox-group">
                    <input type="checkbox" id="terms" name="terms">
                    <label for="terms" class="inline-label">I accept the Terms and Conditions</label>
                    <span class="error-msg" id="termsError"></span>
                </div>

                <button type="submit">Complete Registration</button>
            </form>
        </section>
    </main>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>