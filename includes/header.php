<?php
// Header component for Coffee Management System

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

// Generate CSRF token for forms
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="<?php echo BASE_URL; ?>/index.php" class="brand-link">
                    <i class="fas fa-coffee"></i>
                    <?php echo APP_NAME; ?>
                </a>
            </div>
            
            <div class="nav-links">
                <a href="<?php echo BASE_URL; ?>/index.php" class="nav-link">
                    <i class="fas fa-home"></i> Home
                </a>
                
                <?php if (isLoggedIn()): ?>
                    <?php if (hasRole(ROLE_ADMIN)): ?>
                        <a href="<?php echo BASE_URL; ?>/admin/" class="nav-link">
                            <i class="fas fa-user-shield"></i> Admin Panel
                        </a>
                    <?php endif; ?>
                    
                    <?php if (hasRole(ROLE_SELLER)): ?>
                        <a href="<?php echo BASE_URL; ?>/seller/" class="nav-link">
                            <i class="fas fa-store"></i> Seller Panel
                        </a>
                    <?php endif; ?>
                    
                    <div class="nav-dropdown">
                        <a href="#" class="nav-link dropdown-toggle">
                            <i class="fas fa-user"></i> 
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <div class="dropdown-menu">
                            <a href="<?php echo BASE_URL; ?>/logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/login.php" class="nav-link">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="<?php echo BASE_URL; ?>/seller/register.php" class="nav-link">
                        <i class="fas fa-user-plus"></i> Register as Seller
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <div class="container">
        <?php echo displayFlashMessages(); ?>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <?php if (isset($pageTitle)): ?>
                <div class="page-header">
                    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
                </div>
            <?php endif; ?>

<input type="hidden" id="csrf_token" value="<?php echo $csrfToken; ?>">
