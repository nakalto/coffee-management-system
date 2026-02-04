<?php
// Admin stock management

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Require admin role
requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Filters
$sellerId = intval($_GET['seller_id'] ?? 0);
$coffeeTypeId = intval($_GET['coffee_type_id'] ?? 0);
$search = sanitizeInput($_GET['search'] ?? '');

// Build WHERE clause
$whereClause = 'WHERE s.kilos > 0';
$params = [];

if ($sellerId > 0) {
    $whereClause .= ' AND s.seller_id = ?';
    $params[] = $sellerId;
}

if ($coffeeTypeId > 0) {
    $whereClause .= ' AND s.coffee_type_id = ?';
    $params[] = $coffeeTypeId;
}

if (!empty($search)) {
    $whereClause .= ' AND (u.name LIKE ? OR ct.name LIKE ? OR u.location LIKE ?)';
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

// Get total records count
$countSql = "
    SELECT COUNT(*) as total 
    FROM stocks s
    JOIN users u ON s.seller_id = u.id
    JOIN coffee_types ct ON s.coffee_type_id = ct.id
    $whereClause
";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetch()['total'];

$pagination = getPagination($total, $page, $limit);

// Get stocks with pagination
$sql = "
    SELECT s.id, s.kilos, s.updated_at,
           u.id as seller_id, u.name as seller_name, u.phone as seller_phone, u.location,
           ct.id as coffee_type_id, ct.name as coffee_type_name
    FROM stocks s
    JOIN users u ON s.seller_id = u.id
    JOIN coffee_types ct ON s.coffee_type_id = ct.id
    $whereClause
    ORDER BY s.updated_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $db->prepare($sql);
$stockParams = array_merge($params, [$limit, $offset]);
$stmt->execute($stockParams);
$stocks = $stmt->fetchAll();

// Get filter options
$sellers = [];
$stmt = $db->prepare('SELECT id, name FROM users WHERE role = ? ORDER BY name');
$stmt->execute([ROLE_SELLER]);
$sellers = $stmt->fetchAll();

$coffeeTypes = [];
$stmt = $db->query('SELECT id, name FROM coffee_types ORDER BY name');
$coffeeTypes = $stmt->fetchAll();

// Get statistics
$stmt = $db->query('SELECT COUNT(*) as total FROM stocks WHERE kilos > 0');
$totalStockRecords = $stmt->fetch()['total'];

$stmt = $db->query('SELECT SUM(kilos) as total FROM stocks WHERE kilos > 0');
$totalKilos = $stmt->fetch()['total'] ?? 0;

$pageTitle = 'Manage Stock';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Statistics -->
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
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-filter"></i> Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="search-form">
            <div class="form-group">
                <label for="seller_id">Seller</label>
                <select name="seller_id" id="seller_id" class="form-control">
                    <option value="">All Sellers</option>
                    <?php foreach ($sellers as $seller): ?>
                        <option value="<?php echo $seller['id']; ?>" <?php echo $sellerId === $seller['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($seller['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="coffee_type_id">Coffee Type</label>
                <select name="coffee_type_id" id="coffee_type_id" class="form-control">
                    <option value="">All Coffee Types</option>
                    <?php foreach ($coffeeTypes as $type): ?>
                        <option value="<?php echo $type['id']; ?>" <?php echo $coffeeTypeId === $type['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="search">Search</label>
                <input type="text" 
                       name="search" 
                       id="search" 
                       class="form-control" 
                       placeholder="Search by seller, coffee type, or location..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Apply Filters
            </button>
            <a href="<?php echo BASE_URL; ?>/admin/stocks.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Clear
            </a>
        </form>
    </div>
</div>

<!-- Stock List -->
<div class="card">
    <div class="card-header">
        <h5>Stock Records (<?php echo number_format($total); ?> total)</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($stocks)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Seller</th>
                            <th>Phone</th>
                            <th>Location</th>
                            <th>Coffee Type</th>
                            <th>Kilos</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stocks as $stock): ?>
                            <tr>
                                <td><?php echo $stock['id']; ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/admin/seller-details.php?id=<?php echo $stock['seller_id']; ?>">
                                        <?php echo htmlspecialchars($stock['seller_name']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($stock['seller_phone']); ?></td>
                                <td><?php echo htmlspecialchars($stock['location']); ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($stock['coffee_type_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo number_format($stock['kilos'], 1); ?></strong> kg
                                </td>
                                <td><?php echo formatDate($stock['updated_at'], 'M j, Y H:i'); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/admin/seller-details.php?id=<?php echo $stock['seller_id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View Seller
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <nav aria-label="Stock pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['has_prev']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $sellerId > 0 ? '&seller_id=' . $sellerId : ''; ?><?php echo $coffeeTypeId > 0 ? '&coffee_type_id=' . $coffeeTypeId : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $sellerId > 0 ? '&seller_id=' . $sellerId : ''; ?><?php echo $coffeeTypeId > 0 ? '&coffee_type_id=' . $coffeeTypeId : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $sellerId > 0 ? '&seller_id=' . $sellerId : ''; ?><?php echo $coffeeTypeId > 0 ? '&coffee_type_id=' . $coffeeTypeId : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <p class="text-muted">
                No stock records found matching your criteria. 
                <a href="<?php echo BASE_URL; ?>/admin/stocks.php">Clear filters</a>.
            </p>
        <?php endif; ?>
    </div>
</div>

<style>
.pagination {
    margin-top: 2rem;
}

.pagination .page-link {
    color: #6f4e37;
    border-color: #6f4e37;
}

.pagination .page-link:hover {
    color: #5d4037;
    background-color: #f8f9fa;
}

.pagination .page-item.active .page-link {
    background-color: #6f4e37;
    border-color: #6f4e37;
}

.badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-info {
    background-color: #17a2b8;
    color: white;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
