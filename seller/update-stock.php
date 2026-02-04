<?php
// Update stock handler for sellers

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Require seller role
requireRole(ROLE_SELLER);

$sellerId = $_SESSION['user_id'];
$db = Database::getInstance()->getConnection();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $stockId = intval($_POST['stock_id'] ?? 0);
        $kilos = floatval($_POST['kilos'] ?? 0);
        
        // Validate input
        if ($stockId === 0) {
            $errors[] = 'Invalid stock record.';
        }
        
        if ($kilos < 0) {
            $errors[] = 'Kilos cannot be negative.';
        } elseif ($kilos > 999999) {
            $errors[] = 'Kilos value is too large.';
        }
        
        if (empty($errors)) {
            try {
                // Verify stock belongs to this seller and get coffee type info
                $stmt = $db->prepare('
                    SELECT s.id, ct.name as coffee_type_name 
                    FROM stocks s
                    JOIN coffee_types ct ON s.coffee_type_id = ct.id
                    WHERE s.id = ? AND s.seller_id = ?
                ');
                $stmt->execute([$stockId, $sellerId]);
                $stock = $stmt->fetch();
                
                if (!$stock) {
                    $errors[] = 'Stock record not found or does not belong to you.';
                } else {
                    // Update the stock
                    $stmt = $db->prepare('
                        UPDATE stocks 
                        SET kilos = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id = ? AND seller_id = ?
                    ');
                    $stmt->execute([$kilos, $stockId, $sellerId]);
                    
                    setFlashMessage('success', "Stock updated successfully! {$kilos} kg of {$stock['coffee_type_name']} is now available.");
                    redirect(BASE_URL . '/seller/stocks.php');
                }
                
            } catch (PDOException $e) {
                error_log('Update stock error: ' . $e->getMessage());
                $errors[] = 'Failed to update stock. Please try again.';
            }
        }
    }
}

// If we got here, there was an error
if (!empty($errors)) {
    foreach ($errors as $error) {
        setFlashMessage('error', $error);
    }
}

redirect(BASE_URL . '/seller/stocks.php');
?>
