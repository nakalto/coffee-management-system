<?php
// Admin coffee types management

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Require admin role
requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? '';
$coffeeId = $_GET['id'] ?? '';

$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        
        if (empty($name)) {
            $errors[] = 'Coffee type name is required.';
        } elseif (strlen($name) < 2) {
            $errors[] = 'Coffee type name must be at least 2 characters long.';
        }
        
        if (empty($errors)) {
            try {
                if ($_POST['action'] === 'add') {
                    // Check for duplicate
                    $stmt = $db->prepare('SELECT id FROM coffee_types WHERE name = ?');
                    $stmt->execute([$name]);
                    if ($stmt->fetch()) {
                        $errors[] = 'Coffee type already exists.';
                    } else {
                        $stmt = $db->prepare('INSERT INTO coffee_types (name) VALUES (?)');
                        $stmt->execute([$name]);
                        $success = 'Coffee type added successfully!';
                    }
                } elseif ($_POST['action'] === 'edit') {
                    $id = $_POST['id'] ?? '';
                    
                    // Check for duplicate (excluding current record)
                    $stmt = $db->prepare('SELECT id FROM coffee_types WHERE name = ? AND id != ?');
                    $stmt->execute([$name, $id]);
                    if ($stmt->fetch()) {
                        $errors[] = 'Coffee type already exists.';
                    } else {
                        $stmt = $db->prepare('UPDATE coffee_types SET name = ? WHERE id = ?');
                        $stmt->execute([$name, $id]);
                        $success = 'Coffee type updated successfully!';
                    }
                }
                
                if ($success) {
                    setFlashMessage('success', $success);
                    redirect(BASE_URL . '/admin/coffee-types.php');
                }
                
            } catch (PDOException $e) {
                error_log('Coffee type error: ' . $e->getMessage());
                $errors[] = 'Operation failed. Please try again.';
            }
        }
    }
}

// Handle delete action
if ($action === 'delete' && !empty($coffeeId)) {
    if (validateCSRFToken($_GET['csrf_token'] ?? '')) {
        try {
            // Check if coffee type is used in stocks
            $stmt = $db->prepare('SELECT COUNT(*) as count FROM stocks WHERE coffee_type_id = ?');
            $stmt->execute([$coffeeId]);
            $count = $stmt->fetch()['count'];
            
            if ($count > 0) {
                $errors[] = 'Cannot delete coffee type. It is used in stock records.';
            } else {
                $stmt = $db->prepare('DELETE FROM coffee_types WHERE id = ?');
                $stmt->execute([$coffeeId]);
                setFlashMessage('success', 'Coffee type deleted successfully!');
                redirect(BASE_URL . '/admin/coffee-types.php');
            }
        } catch (PDOException $e) {
            error_log('Delete coffee type error: ' . $e->getMessage());
            $errors[] = 'Delete failed. Please try again.';
        }
    }
}

// Get coffee type for editing
$coffeeType = null;
if ($action === 'edit' && !empty($coffeeId)) {
    $stmt = $db->prepare('SELECT * FROM coffee_types WHERE id = ?');
    $stmt->execute([$coffeeId]);
    $coffeeType = $stmt->fetch();
    
    if (!$coffeeType) {
        setFlashMessage('error', 'Coffee type not found.');
        redirect(BASE_URL . '/admin/coffee-types.php');
    }
}

// Get all coffee types
$stmt = $db->query('SELECT * FROM coffee_types ORDER BY name');
$coffeeTypes = $stmt->fetchAll();

$pageTitle = 'Manage Coffee Types';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Coffee Types Management</h2>
    <a href="<?php echo BASE_URL; ?>/admin/coffee-types.php?action=add" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Coffee Type
    </a>
</div>

<?php if (!empty($errors)): ?>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- Add/Edit Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><?php echo $action === 'edit' ? 'Edit Coffee Type' : 'Add New Coffee Type'; ?></h5>
        </div>
        <div class="card-body">
            <form method="POST" data-validate>
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo $coffeeType['id']; ?>">
                <?php endif; ?>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="name" class="form-label">Coffee Type Name</label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           class="form-control" 
                           placeholder="Enter coffee type name"
                           required
                           value="<?php echo htmlspecialchars($coffeeType['name'] ?? ($_POST['name'] ?? '')); ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 
                        <?php echo $action === 'edit' ? 'Update' : 'Add'; ?> Coffee Type
                    </button>
                    <a href="<?php echo BASE_URL; ?>/admin/coffee-types.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Coffee Types List -->
<div class="card">
    <div class="card-header">
        <h5>All Coffee Types</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($coffeeTypes)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Created Date</th>
                            <th>Stock Count</th>
                            <th>Total Kilos</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coffeeTypes as $type): ?>
                            <?php
                            // Get stock statistics for this coffee type
                            $stmt = $db->prepare('
                                SELECT COUNT(*) as count, COALESCE(SUM(kilos), 0) as total_kilos 
                                FROM stocks 
                                WHERE coffee_type_id = ? AND kilos > 0
                            ');
                            $stmt->execute([$type['id']]);
                            $stockStats = $stmt->fetch();
                            ?>
                            <tr>
                                <td><?php echo $type['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($type['name']); ?></strong>
                                </td>
                                <td><?php echo formatDate($type['created_at'], 'M j, Y'); ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $stockStats['count']; ?> records
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-success">
                                        <?php echo number_format($stockStats['total_kilos'], 1); ?> kg
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/admin/coffee-types.php?action=edit&id=<?php echo $type['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    
                                    <?php if ($stockStats['count'] == 0): ?>
                                        <a href="<?php echo BASE_URL; ?>/admin/coffee-types.php?action=delete&id=<?php echo $type['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this coffee type?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-danger" disabled title="Cannot delete - used in stock records">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No coffee types found. <a href="<?php echo BASE_URL; ?>/admin/coffee-types.php?action=add">Add the first one</a>.</p>
        <?php endif; ?>
    </div>
</div>

<style>
.badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-info {
    background-color: #17a2b8;
    color: white;
}

.badge-success {
    background-color: #28a745;
    color: white;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
