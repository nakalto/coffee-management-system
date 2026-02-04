<?php
// Admin seller details page

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Require admin role
requireRole(ROLE_ADMIN);

$sellerId = intval($_GET['id'] ?? 0);

if ($sellerId === 0) {
    setFlashMessage('error', 'Invalid seller ID.');
    redirect(BASE_URL . '/admin/sellers.php');
}

$db = Database::getInstance()->getConnection();

// Get seller details
$stmt = $db->prepare('SELECT * FROM users WHERE id = ? AND role = ?');
$stmt->execute([$sellerId, ROLE_SELLER]);
$seller = $stmt->fetch();

if (!$seller) {
    setFlashMessage('error', 'Seller not found.');
    redirect(BASE_URL . '/admin/sellers.php');
}

// Get seller's stocks
$stmt = $db->prepare('
    SELECT s.id, s.kilos, s.updated_at,
           ct.name as coffee_type_name
    FROM stocks s
    JOIN coffee_types ct ON s.coffee_type_id = ct.id
    WHERE s.seller_id = ? AND s.kilos > 0
    ORDER BY s.updated_at DESC
');
$stmt->execute([$sellerId]);
$stocks = $stmt->fetchAll();

// Get seller statistics
$stmt = $db->prepare('SELECT COUNT(*) as count FROM stocks WHERE seller_id = ?');
$stmt->execute([$sellerId]);
$totalStockRecords = $stmt->fetch()['count'];

$stmt = $db->prepare('SELECT SUM(kilos) as total FROM stocks WHERE seller_id = ? AND kilos > 0');
$stmt->execute([$sellerId]);
$totalKilos = $stmt->fetch()['total'] ?? 0;

$pageTitle = 'Seller Details - ' . htmlspecialchars($seller['name']);
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Seller Information -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-user"></i> Seller Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>ID:</strong></td>
                        <td><?php echo $seller['id']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Name:</strong></td>
                        <td><?php echo htmlspecialchars($seller['name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Phone:</strong></td>
                        <td><?php echo htmlspecialchars($seller['phone']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Location:</strong></td>
                        <td><?php echo htmlspecialchars($seller['location']); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Role:</strong></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo ucfirst($seller['role']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Member Since:</strong></td>
                        <td><?php echo formatDate($seller['created_at'], 'F j, Y'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Stock Records:</strong></td>
                        <td><?php echo number_format($totalStockRecords); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Kilos:</strong></td>
                        <td><strong><?php echo number_format($totalKilos, 1); ?> kg</strong></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="<?php echo BASE_URL; ?>/admin/sellers.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Sellers
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/stocks.php?seller_id=<?php echo $seller['id']; ?>" class="btn btn-primary">
                <i class="fas fa-weight"></i> View All Stock
            </a>
        </div>
    </div>
</div>

<!-- Stock Statistics -->
<div class="dashboard-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-database"></i>
        </div>
        <div class="stat-number"><?php echo number_format($totalStockRecords); ?></div>
        <div class="stat-label">Stock Records</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-weight"></i>
        </div>
        <div class="stat-number"><?php echo number_format($totalKilos, 1); ?></div>
        <div class="stat-label">Total Kilos</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-coffee"></i>
        </div>
        <div class="stat-number"><?php echo count($stocks); ?></div>
        <div class="stat-label">Active Coffee Types</div>
    </div>
</div>

<!-- Current Stock -->
<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-weight"></i> Current Stock</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($stocks)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Coffee Type</th>
                            <th>Kilos Available</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stocks as $stock): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($stock['coffee_type_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo number_format($stock['kilos'], 1); ?></strong> kg
                                </td>
                                <td><?php echo formatDate($stock['updated_at'], 'M j, Y H:i'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Total Stock Value:</strong> 
                        <span class="text-success"><?php echo number_format($totalKilos, 1); ?> kg</span>
                    </div>
                    <div class="col-md-6 text-right">
                        <strong>Active Coffee Types:</strong> 
                        <span class="text-info"><?php echo count($stocks); ?></span>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <p class="text-muted">This seller has no stock records yet.</p>
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

.table-borderless td {
    border: none;
    padding: 0.5rem 0;
}

.table-borderless td:first-child {
    width: 120px;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
