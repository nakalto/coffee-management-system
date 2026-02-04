<?php
// Login page for Coffee Management System

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (hasRole(ROLE_ADMIN)) {
        redirect(BASE_URL . '/admin/');
    } elseif (hasRole(ROLE_SELLER)) {
        redirect(BASE_URL . '/seller/');
    } else {
        redirect(BASE_URL . '/index.php');
    }
}

$errors = [];
$success = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validate input
        if (empty($phone)) {
            $errors[] = 'Phone number is required.';
        } elseif (!validatePhone($phone)) {
            $errors[] = 'Please enter a valid phone number.';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required.';
        }
        
        if (empty($errors)) {
            try {
                $db = Database::getInstance()->getConnection();
                
                // Get user by phone
                $stmt = $db->prepare('SELECT id, name, phone, location, password, role FROM users WHERE phone = ?');
                $stmt->execute([$phone]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Login successful
                    regenerateSession();
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_phone'] = $user['phone'];
                    $_SESSION['user_location'] = $user['location'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['login_time'] = time();
                    
                    setFlashMessage('success', 'Welcome back, ' . $user['name'] . '!');
                    
                    // Redirect based on role
                    if ($user['role'] === ROLE_ADMIN) {
                        redirect(BASE_URL . '/admin/');
                    } elseif ($user['role'] === ROLE_SELLER) {
                        redirect(BASE_URL . '/seller/');
                    } else {
                        redirect(BASE_URL . '/index.php');
                    }
                } else {
                    $errors[] = 'Invalid phone number or password.';
                }
                
            } catch (PDOException $e) {
                error_log('Login error: ' . $e->getMessage());
                $errors[] = 'Login failed. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-header text-center">
                <i class="fas fa-coffee fa-2x mb-2"></i>
                <h4>Login to Your Account</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <?php foreach ($errors as $error): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <form method="POST" data-validate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">
                            <i class="fas fa-phone"></i> Phone Number
                        </label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               class="form-control" 
                               placeholder="Enter your phone number"
                               required
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Enter your password"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p>Don't have an account?</p>
                    <a href="<?php echo BASE_URL; ?>/seller/register.php" class="btn btn-outline-primary">
                        <i class="fas fa-user-plus"></i> Register as Seller
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
