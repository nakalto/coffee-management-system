<?php
// Add stock page for sellers

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Require seller role
requireRole(ROLE_SELLER);

$sellerId = $_SESSION['user_id'];
$db = Database::getInstance()->getConnection();

$errors = [];
$success = '';

// Get available coffee types
$stmt = $db->query('SELECT id, name FROM coffee_types ORDER BY name');
$coffeeTypes = $stmt->fetchAll();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $coffeeTypeId = intval($_POST['coffee_type_id'] ?? 0);
        $kilos = floatval($_POST['kilos'] ?? 0);
        
        // Validate input
        if ($coffeeTypeId === 0) {
            $errors[] = 'Please select a coffee type.';
        }
        
        if ($kilos <= 0) {
            $errors[] = 'Kilos must be greater than 0.';
        } elseif ($kilos > 999999) {
            $errors[] = 'Kilos value is too large.';
        }
        
        if (empty($errors)) {
            try {
                // Check if coffee type exists
                $stmt = $db->prepare('SELECT name FROM coffee_types WHERE id = ?');
                $stmt->execute([$coffeeTypeId]);
                $coffeeType = $stmt->fetch();
                
                if (!$coffeeType) {
                    $errors[] = 'Invalid coffee type selected.';
                } else {
                    // Insert or update stock record
                    $stmt = $db->prepare('
                        INSERT INTO stocks (seller_id, coffee_type_id, kilos, updated_at)
                        VALUES (?, ?, ?, CURRENT_TIMESTAMP)
                        ON CONFLICT(seller_id, coffee_type_id) 
                        DO UPDATE SET 
                            kilos = kilos + excluded.kilos,
                            updated_at = CURRENT_TIMESTAMP
                    ');
                    $stmt->execute([$sellerId, $coffeeTypeId, $kilos]);
                    
                    setFlashMessage('success', "Successfully added {$kilos} kg of {$coffeeType['name']} to your stock!");
                    redirect(BASE_URL . '/seller/stocks.php');
                }
                
            } catch (PDOException $e) {
                error_log('Add stock error: ' . $e->getMessage());
                $errors[] = 'Failed to add stock. Please try again.';
            }
        }
    }
}

$pageTitle = 'Add Stock';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-plus"></i> Add Coffee Stock</h4>
                <p class="mb-0 text-muted">Add new inventory to your stock</p>
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
                        <label for="coffee_type_id" class="form-label">
                            <i class="fas fa-coffee"></i> Coffee Type
                        </label>
                        <select name="coffee_type_id" id="coffee_type_id" class="form-control" required>
                            <option value="">Select a coffee type</option>
                            <?php foreach ($coffeeTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>" <?php echo (isset($_POST['coffee_type_id']) && $_POST['coffee_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Choose from the available coffee types</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="kilos" class="form-label">
                            <i class="fas fa-weight"></i> Available Kilos
                        </label>
                        <input type="number" 
                               id="kilos" 
                               name="kilos" 
                               class="form-control" 
                               placeholder="Enter available kilos"
                               step="0.1"
                               min="0"
                               required
                               value="<?php echo htmlspecialchars($_POST['kilos'] ?? ''); ?>">
                        <small class="form-text text-muted">Enter the amount of coffee available in kilograms</small>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-plus"></i> Add Stock
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="<?php echo BASE_URL; ?>/seller/" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>/seller/stocks.php" class="btn btn-outline-primary">
                        <i class="fas fa-list"></i> View Current Stock
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Info Card -->
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> Stock Management Tips</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        <strong>Regular Updates:</strong> Keep your stock levels current for accurate inventory tracking
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        <strong>Quality Control:</strong> Only add coffee that is ready for sale
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        <strong>Multiple Types:</strong> You can stock multiple coffee types simultaneously
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        <strong>Decimal Values:</strong> Use decimal points for precise measurements (e.g., 15.5 kg)
                    </li>
                    <li>
                        <i class="fas fa-check text-success"></i>
                        <strong>Automatic Updates:</strong> Stock levels are automatically updated when you add more inventory
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
