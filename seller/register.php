<?php
// Seller registration page

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . '/index.php');
}

$errors = [];
$success = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $location = sanitizeInput($_POST['location'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        // Validate input
        if (empty($name)) {
            $errors[] = 'Full name is required.';
        } elseif (strlen($name) < 3) {
            $errors[] = 'Name must be at least 3 characters long.';
        }
        
        if (empty($phone)) {
            $errors[] = 'Phone number is required.';
        } elseif (!validatePhone($phone)) {
            $errors[] = 'Please enter a valid phone number (10-15 digits).';
        }
        
        if (empty($location)) {
            $errors[] = 'Location is required.';
        } elseif (strlen($location) < 3) {
            $errors[] = 'Location must be at least 3 characters long.';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }
        
        if ($password !== $password_confirm) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (empty($errors)) {
            try {
                $db = Database::getInstance()->getConnection();
                
                // Check if phone already exists
                $stmt = $db->prepare('SELECT id FROM users WHERE phone = ?');
                $stmt->execute([$phone]);
                if ($stmt->fetch()) {
                    $errors[] = 'A user with this phone number already exists.';
                } else {
                    // Create new seller
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $db->prepare('
                        INSERT INTO users (name, phone, location, password, role) 
                        VALUES (?, ?, ?, ?, ?)
                    ');
                    $stmt->execute([$name, $phone, $location, $hashedPassword, ROLE_SELLER]);
                    
                    setFlashMessage('success', 'Registration successful! You can now login.');
                    redirect(BASE_URL . '/login.php');
                }
                
            } catch (PDOException $e) {
                error_log('Registration error: ' . $e->getMessage());
                $errors[] = 'Registration failed. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Register as Seller';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header text-center">
                <i class="fas fa-store fa-2x mb-2"></i>
                <h4>Register as Coffee Seller</h4>
                <p class="mb-0">Join our platform and start selling your coffee</p>
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
                        <label for="name" class="form-label">
                            <i class="fas fa-user"></i> Full Name
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               class="form-control" 
                               placeholder="Enter your full name"
                               required
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">
                            <i class="fas fa-phone"></i> Phone Number
                        </label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               class="form-control" 
                               placeholder="Enter your phone number (10-15 digits)"
                               required
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        <small class="form-text text-muted">This will be used for login</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="location" class="form-label">
                            <i class="fas fa-map-marker-alt"></i> Location
                        </label>
                        <input type="text" 
                               id="location" 
                               name="location" 
                               class="form-control" 
                               placeholder="Enter your city/location"
                               required
                               value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Enter your password (min 6 characters)"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm" class="form-label">
                            <i class="fas fa-lock"></i> Confirm Password
                        </label>
                        <input type="password" 
                               id="password_confirm" 
                               name="password_confirm" 
                               class="form-control" 
                               placeholder="Confirm your password"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-user-plus"></i> Register
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p>Already have an account?</p>
                    <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline-primary">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Benefits Section -->
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-star"></i> Why Sell With Us?</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        <strong>Easy Inventory Management</strong> - Track your coffee stock in real-time
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        <strong>Wide Customer Reach</strong> - Connect with coffee buyers directly
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        <strong>Secure Platform</strong> - Your data and transactions are protected
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        <strong>Free Registration</strong> - No setup fees or hidden charges
                    </li>
                    <li>
                        <i class="fas fa-check text-success"></i>
                        <strong>24/7 Access</strong> - Manage your business anytime, anywhere
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
