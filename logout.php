<?php
// Logout script for Coffee Management System

require_once __DIR__ . '/includes/functions.php';

// Logout and redirect
logoutUser();
setFlashMessage('success', 'You have been logged out successfully.');

redirect(BASE_URL . '/index.php');
?>
