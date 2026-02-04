<?php
// Seller dashboard

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Require seller role
requireRole(ROLE_SELLER);

$sellerId = $_SESSION['user_id'];

try {
    $db = Database::getInstance()->getConnection();
    
    // Get seller's stock statistics
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM stocks WHERE seller_id = ?');
    $stmt->execute([$sellerId]);
    $totalStockRecords = $stmt->fetch()['count'];
    
    $stmt = $db->prepare('SELECT SUM(kilos) as total FROM stocks WHERE seller_id = ? AND kilos > 0');
    $stmt->execute([$sellerId]);
    $totalKilos = $stmt->fetch()['total'] ?? 0;
    
    // Get recent stock updates
    $stmt = $db->prepare('
        SELECT s.kilos, ct.name as coffee_type, s.updated_at
        FROM stocks s
        JOIN coffee_types ct ON s.coffee_type_id = ct.id
        WHERE s.seller_id = ? AND s.kilos > 0
        ORDER BY s.updated_at DESC
        LIMIT 5
    ');
    $stmt->execute([$sellerId]);
    $recentStocks = $stmt->fetchAll();
    
    // Get coffee type distribution for this seller
    $stmt = $db->prepare('
        SELECT ct.name, COALESCE(SUM(s.kilos), 0) as total_kilos
        FROM coffee_types ct
        LEFT JOIN stocks s ON ct.id = s.coffee_type_id AND s.seller_id = ?
        GROUP BY ct.id, ct.name
        HAVING total_kilos > 0
        ORDER BY total_kilos DESC
    ');
    $stmt->execute([$sellerId]);
    $coffeeDistribution = $stmt->fetchAll();
    
    // Get available coffee types for adding stock
    $stmt = $db->query('SELECT id, name FROM coffee_types ORDER BY name');
    $availableCoffeeTypes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log('Seller dashboard error: ' . $e->getMessage());
    $totalStockRecords = $totalKilos = 0;
    $recentStocks = $coffeeDistribution = $availableCoffeeTypes = [];
}

$pageTitle = 'Seller Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Welcome Section -->
<div class="card mb-4">
    <div class="card-header">
        <h4><i class="fas fa-store"></i> Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h4>
    </div>
    <div class="card-body">
        <p class="mb-0">Manage your coffee inventory and track your stock levels from this dashboard.</p>
    </div>
</div>

<!-- Dashboard Statistics -->
<div class="dashboard-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-database"></i>
        </div>
        <div class="stat-number"><?php echo number_format($totalStockRecords); ?></div>
        <div class="stat-label">Stock Records</div>
        <a href="<?php echo BASE_URL; ?>/seller/stocks.php" class="btn btn-sm btn-secondary mt-2">
            Manage Stock
        </a>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-weight"></i>
        </div>
        <div class="stat-number"><?php echo number_format($totalKilos, 1); ?></div>
        <div class="stat-label">Total Kilos</div>
        <a href="<?php echo BASE_URL; ?>/seller/add-stock.php" class="btn btn-sm btn-primary mt-2">
            Add Stock
        </a>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-coffee"></i>
        </div>
        <div class="stat-number"><?php echo count($coffeeDistribution); ?></div>
        <div class="stat-label">Coffee Types</div>
        <a href="<?php echo BASE_URL; ?>/seller/add-stock.php" class="btn btn-sm btn-secondary mt-2">
            Add More
        </a>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-2">
                <a href="<?php echo BASE_URL; ?>/seller/add-stock.php" class="btn btn-primary btn-block">
                    <i class="fas fa-plus"></i> Add New Stock
                </a>
            </div>
            <div class="col-md-4 mb-2">
                <a href="<?php echo BASE_URL; ?>/seller/stocks.php" class="btn btn-secondary btn-block">
                    <i class="fas fa-list"></i> View All Stock
                </a>
            </div>
            <div class="col-md-4 mb-2">
                <a href="<?php echo BASE_URL; ?>/public/coffee.php" class="btn btn-secondary btn-block">
                    <i class="fas fa-eye"></i> Public View
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Stock Updates -->
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
                                    <th>Kilos</th>
                                    <th>Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentStocks as $stock): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stock['coffee_type']); ?></td>
                                        <td><?php echo number_format($stock['kilos'], 1); ?></td>
                                        <td><?php echo formatDate($stock['updated_at'], 'M j, Y'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No stock records yet. <a href="<?php echo BASE_URL; ?>/seller/add-stock.php">Add your first stock</a>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Coffee Distribution -->
    <?php if (!empty($coffeeDistribution)): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-pie"></i> Stock Distribution</h5>
            </div>
            <div class="card-body">
                <?php foreach ($coffeeDistribution as $coffee): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><?php echo htmlspecialchars($coffee['name']); ?></span>
                            <strong><?php echo number_format((float)($coffee['total_kilos'] ?? 0), 1); ?> kg</strong>
                        </div>
                        <div class="progress mt-1">
                            <?php 
                            $coffeeKilos = (float)($coffee['total_kilos'] ?? 0);
                            $percentage = $totalKilos > 0 ? ($coffeeKilos / (float)$totalKilos) * 100 : 0;
                            ?>
                            <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add Stock Form (Quick) -->
<?php if (!empty($availableCoffeeTypes)): ?>
<div class="card mt-4">
    <div class="card-header">
        <h5><i class="fas fa-plus"></i> Quick Add Stock</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?php echo BASE_URL; ?>/seller/add-stock.php" data-validate>
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="coffee_type_id" class="form-label">Coffee Type</label>
                        <select name="coffee_type_id" id="coffee_type_id" class="form-control" required>
                            <option value="">Select coffee type</option>
                            <?php foreach ($availableCoffeeTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>">
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="kilos" class="form-label">Kilos</label>
                        <input type="number" 
                               id="kilos" 
                               name="kilos" 
                               class="form-control" 
                               placeholder="Enter kilos"
                               step="0.1"
                               min="0"
                               required>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

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
