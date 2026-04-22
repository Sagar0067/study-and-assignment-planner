<?php
require_once 'db.php';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Redirect to dashboard if logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

// Handle POST request for Login / Register
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Username and password are required!";
    } else {
        if ($action === 'register') {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "Username is already taken. Please choose another.";
            } else {
                // Insert new user
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
                if ($stmt->execute([$username, $hash])) {
                    $success = "Registration successful! You can now log in.";
                } else {
                    $error = "Failed to register user. Please try again.";
                }
            }
        } elseif ($action === 'login') {
            // Verify User
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: dashboard.php");
                exit;
            } else {
                // To support previously auto-registered users from SPA which had blank password hashes
                if ($user && $user['password_hash'] === '') {
                    $error = "This account was created without a password. Please register a new account.";
                } else {
                    $error = "Invalid username or password!";
                }
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
    <title>Study Planner - Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body data-theme="dark">

    <div class="auth-wrapper d-flex align-items-center justify-content-center min-vh-100 position-relative z-1">
        <div class="position-absolute top-0 end-0 p-4">
            <button class="btn btn-link text-light text-decoration-none" id="themeToggle"><i class="fas fa-moon fs-4" id="themeIcon"></i></button>
        </div>
        
        <div class="glass-container p-5 rounded-4 shadow-lg text-center" style="max-width: 450px; width: 100%;">
            
            <div class="brand-icon mb-4">
                <i class="fas fa-book-reader fa-3x text-accent"></i>
            </div>
            <h2 class="fw-bold mb-3 neon-text">Study Planner</h2>
            <p class="text-muted mb-4 small">Organize your syllabus and master your tasks.</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger pt-2 pb-2 small"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success pt-2 pb-2 small"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Tabs -->
            <ul class="nav nav-pills nav-justified mb-4 auth-tabs" id="authTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill text-light" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">Login</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill text-light" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">Register</button>
                </li>
            </ul>

            <div class="tab-content text-start">
                <!-- Login Form -->
                <div class="tab-pane fade show active" id="login" role="tabpanel">
                    <form method="POST" action="login.php">
                        <input type="hidden" name="action" value="login">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control auth-input" id="login-username" name="username" placeholder="Username" required>
                            <label for="login-username"><i class="fas fa-user ms-1 me-2"></i>Username</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control auth-input" id="login-password" name="password" placeholder="Password" required>
                            <label for="login-password"><i class="fas fa-lock ms-1 me-2"></i>Password</label>
                        </div>
                        <button type="submit" class="btn btn-accent w-100 py-2 fw-semibold">Login to Dashboard <i class="fas fa-arrow-right ms-2"></i></button>
                    </form>
                </div>

                <!-- Register Form -->
                <div class="tab-pane fade" id="register" role="tabpanel">
                    <form method="POST" action="login.php">
                        <input type="hidden" name="action" value="register">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control auth-input" id="reg-username" name="username" placeholder="Username" required>
                            <label for="reg-username"><i class="fas fa-user ms-1 me-2"></i>Choose Username</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control auth-input" id="reg-password" name="password" placeholder="Password" required>
                            <label for="reg-password"><i class="fas fa-lock ms-1 me-2"></i>Choose Password</label>
                        </div>
                        <button type="submit" class="btn btn-accent w-100 py-2 fw-semibold">Create Account <i class="fas fa-user-plus ms-2"></i></button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <!-- Background Elements -->
    <div class="bg-orb orb-1"></div>
    <div class="bg-orb orb-2"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="app.js"></script>
</body>
</html>
