<?php
// Admin reports page

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->query('SELECT COUNT(*) as total FROM users WHERE role = "seller"');
    $totalSellers = $stmt->fetch()['total'] ?? 0;

    $stmt = $db->query('SELECT COUNT(*) as total FROM coffee_types');
    $totalCoffeeTypes = $stmt->fetch()['total'] ?? 0;

    $stmt = $db->query('SELECT COUNT(*) as total FROM stocks');
    $totalStockRecords = $stmt->fetch()['total'] ?? 0;

    $stmt = $db->query('SELECT SUM(kilos) as total FROM stocks WHERE kilos > 0');
    $totalKilos = $stmt->fetch()['total'] ?? 0;

    $stmt = $db->query('
        SELECT ct.name, COALESCE(SUM(s.kilos), 0) as total_kilos
        FROM coffee_types ct
        LEFT JOIN stocks s ON ct.id = s.coffee_type_id
        GROUP BY ct.id, ct.name
        ORDER BY total_kilos DESC
    ');
    $byCoffeeType = $stmt->fetchAll();

    $stmt = $db->query('
        SELECT u.name as seller_name, u.location, COALESCE(SUM(s.kilos), 0) as total_kilos
        FROM users u
        LEFT JOIN stocks s ON u.id = s.seller_id
        WHERE u.role = "seller"
        GROUP BY u.id, u.name, u.location
        ORDER BY total_kilos DESC
        LIMIT 10
    ');
    $topSellers = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Reports error: ' . $e->getMessage());
    $totalSellers = $totalCoffeeTypes = $totalStockRecords = 0;
    $totalKilos = 0;
    $byCoffeeType = [];
    $topSellers = [];
}

$pageTitle = 'Reports';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-number"><?php echo number_format($totalSellers); ?></div>
        <div class="stat-label">Sellers</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-coffee"></i></div>
        <div class="stat-number"><?php echo number_format($totalCoffeeTypes); ?></div>
        <div class="stat-label">Coffee Types</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-database"></i></div>
        <div class="stat-number"><?php echo number_format($totalStockRecords); ?></div>
        <div class="stat-label">Stock Records</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-weight"></i></div>
        <div class="stat-number"><?php echo number_format($totalKilos, 1); ?></div>
        <div class="stat-label">Total Kilos</div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-pie"></i> Stock by Coffee Type</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($byCoffeeType)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Coffee Type</th>
                                    <th>Total Kilos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($byCoffeeType as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><strong><?php echo number_format($row['total_kilos'] ?? 0, 1); ?></strong> kg</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-trophy"></i> Top Sellers (by kilos)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($topSellers)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Seller</th>
                                    <th>Location</th>
                                    <th>Total Kilos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topSellers as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['seller_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                                        <td><strong><?php echo number_format($row['total_kilos'] ?? 0, 1); ?></strong> kg</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="<?php echo BASE_URL; ?>/admin/" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
