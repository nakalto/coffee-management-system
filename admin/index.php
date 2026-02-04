<?php
// Admin dashboard

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Require admin role
requireRole(ROLE_ADMIN);

// Get dashboard statistics
try {
    $db = Database::getInstance()->getConnection();
    
    // Total sellers
    $stmt = $db->prepare('SELECT COUNT(*) as total FROM users WHERE role = ?');
    $stmt->execute([ROLE_SELLER]);
    $totalSellers = $stmt->fetch()['total'];
    
    // Total coffee types
    $stmt = $db->query('SELECT COUNT(*) as total FROM coffee_types');
    $totalCoffeeTypes = $stmt->fetch()['total'];
    
    // Total stock
    $stmt = $db->query('SELECT SUM(kilos) as total, COUNT(*) as records FROM stocks');
    $stockData = $stmt->fetch();
    $totalStock = $stockData['total'] ?? 0;
    $totalStockRecords = $stockData['records'] ?? 0;
    
    // Recent sellers
    $stmt = $db->prepare('
        SELECT id, name, phone, location, created_at 
        FROM users 
        WHERE role = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ');
    $stmt->execute([ROLE_SELLER]);
    $recentSellers = $stmt->fetchAll();
    
    // Recent stock updates
    $stmt = $db->query('
        SELECT s.kilos, ct.name as coffee_type, u.name as seller_name, u.location, s.updated_at
        FROM stocks s
        JOIN coffee_types ct ON s.coffee_type_id = ct.id
        JOIN users u ON s.seller_id = u.id
        ORDER BY s.updated_at DESC
        LIMIT 5
    ');
    $recentStocks = $stmt->fetchAll();
    
    // Coffee type distribution
    $stmt = $db->query('
        SELECT ct.name, COALESCE(SUM(s.kilos), 0) as total_kilos
        FROM coffee_types ct
        LEFT JOIN stocks s ON ct.id = s.coffee_type_id
        GROUP BY ct.id, ct.name
        ORDER BY total_kilos DESC
    ');
    $coffeeDistribution = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log('Admin dashboard error: ' . $e->getMessage());
    $totalSellers = $totalCoffeeTypes = $totalStock = $totalStockRecords = 0;
    $recentSellers = $recentStocks = $coffeeDistribution = [];
}

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Dashboard Statistics -->
<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-number"><?php echo number_format($totalSellers); ?></div>
        <div class="stat-label">Total Sellers</div>
        <a href="<?php echo BASE_URL; ?>/admin/sellers.php" class="btn btn-sm btn-secondary mt-2">
            View All
        </a>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-coffee"></i>
        </div>
        <div class="stat-number"><?php echo number_format($totalCoffeeTypes); ?></div>
        <div class="stat-label">Coffee Types</div>
        <a href="<?php echo BASE_URL; ?>/admin/coffee-types.php" class="btn btn-sm btn-secondary mt-2">
            Manage
        </a>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-weight"></i>
        </div>
        <div class="stat-number"><?php echo number_format($totalStock, 1); ?></div>
        <div class="stat-label">Total Kilos in Stock</div>
        <a href="<?php echo BASE_URL; ?>/admin/stocks.php" class="btn btn-sm btn-secondary mt-2">
            View Stock
        </a>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-database"></i>
        </div>
        <div class="stat-number"><?php echo number_format($totalStockRecords); ?></div>
        <div class="stat-label">Stock Records</div>
        <a href="<?php echo BASE_URL; ?>/admin/stocks.php" class="btn btn-sm btn-secondary mt-2">
            View All
        </a>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-user-plus"></i> Recent Sellers</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recentSellers)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Location</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSellers as $seller): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/admin/seller-details.php?id=<?php echo $seller['id']; ?>">
                                                <?php echo htmlspecialchars($seller['name']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($seller['location']); ?></td>
                                        <td><?php echo formatDate($seller['created_at'], 'M j, Y'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No sellers registered yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-clock"></i> Recent Stock Updates</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recentStocks)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Coffee Type</th>
                                    <th>Seller</th>
                                    <th>Kilos</th>
                                    <th>Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentStocks as $stock): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stock['coffee_type']); ?></td>
                                        <td><?php echo htmlspecialchars($stock['seller_name']); ?></td>
                                        <td><?php echo number_format($stock['kilos'], 1); ?></td>
                                        <td><?php echo formatDate($stock['updated_at'], 'M j, Y'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No stock updates yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Coffee Distribution -->
<?php if (!empty($coffeeDistribution)): ?>
<div class="card mt-4">
    <div class="card-header">
        <h5><i class="fas fa-chart-pie"></i> Coffee Type Distribution</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($coffeeDistribution as $coffee): ?>
                <div class="col-md-4 mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><?php echo htmlspecialchars($coffee['name']); ?></span>
                        <strong><?php echo number_format((float)($coffee['total_kilos'] ?? 0), 1); ?> kg</strong>
                    </div>
                    <div class="progress mt-1">
                        <?php 
                        $coffeeKilos = (float)($coffee['total_kilos'] ?? 0);
                        $percentage = $totalStock > 0 ? ($coffeeKilos / (float)$totalStock) * 100 : 0;
                        ?>
                        <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="card mt-4">
    <div class="card-header">
        <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-2">
                <a href="<?php echo BASE_URL; ?>/admin/coffee-types.php?action=add" class="btn btn-primary btn-block">
                    <i class="fas fa-plus"></i> Add Coffee Type
                </a>
            </div>
            <div class="col-md-3 mb-2">
                <a href="<?php echo BASE_URL; ?>/admin/sellers.php" class="btn btn-secondary btn-block">
                    <i class="fas fa-users"></i> Manage Sellers
                </a>
            </div>
            <div class="col-md-3 mb-2">
                <a href="<?php echo BASE_URL; ?>/admin/stocks.php" class="btn btn-secondary btn-block">
                    <i class="fas fa-weight"></i> View All Stock
                </a>
            </div>
            <div class="col-md-3 mb-2">
                <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="btn btn-secondary btn-block">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.progress {
    height: 8px;
    background-color: #e9ecef;
}

.progress-bar {
    background: linear-gradient(135deg, #6f4e37 0%, #8b6f47 100%);
}

.table-sm th,
.table-sm td {
    padding: 0.5rem;
    font-size: 0.875rem;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
