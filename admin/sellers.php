<?php
// Admin sellers management

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Require admin role
requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$search = sanitizeInput($_GET['search'] ?? '');
$whereClause = '';
$params = [ROLE_SELLER];

if (!empty($search)) {
    $whereClause = 'AND (name LIKE ? OR phone LIKE ? OR location LIKE ?)';
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

// Get total sellers count
$countSql = "SELECT COUNT(*) as total FROM users WHERE role = ? $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetch()['total'];

$pagination = getPagination($total, $page, $limit);

// Get sellers with pagination
$sql = "
    SELECT id, name, phone, location, created_at 
    FROM users 
    WHERE role = ? $whereClause
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $db->prepare($sql);
$sellerParams = array_merge($params, [$limit, $offset]);
$stmt->execute($sellerParams);
$sellers = $stmt->fetchAll();

// Get seller statistics
$stmt = $db->prepare('SELECT COUNT(*) as total FROM users WHERE role = ?');
$stmt->execute([ROLE_SELLER]);
$totalSellers = $stmt->fetch()['total'];

$stmt = $db->prepare('SELECT COUNT(*) as total FROM users WHERE role = ? AND DATE(created_at) = DATE("now")');
$stmt->execute([ROLE_SELLER]);
$newSellersToday = $stmt->fetch()['total'];

$pageTitle = 'Manage Sellers';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Statistics -->
<div class="dashboard-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-number"><?php echo number_format($totalSellers); ?></div>
        <div class="stat-label">Total Sellers</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-user-plus"></i>
        </div>
        <div class="stat-number"><?php echo number_format($newSellersToday); ?></div>
        <div class="stat-label">New Sellers Today</div>
    </div>
</div>

<!-- Search and Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-search"></i> Search Sellers</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="search-form">
            <div class="form-group">
                <input type="text" 
                       name="search" 
                       class="form-control" 
                       placeholder="Search by name, phone, or location..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Search
            </button>
            <?php if (!empty($search)): ?>
                <a href="<?php echo BASE_URL; ?>/admin/sellers.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Sellers List -->
<div class="card">
    <div class="card-header">
        <h5>All Sellers (<?php echo number_format($total); ?> total)</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($sellers)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Location</th>
                            <th>Stock Records</th>
                            <th>Total Kilos</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sellers as $seller): ?>
                            <?php
                            // Get seller stock statistics
                            $stmt = $db->prepare('
                                SELECT COUNT(*) as count, COALESCE(SUM(kilos), 0) as total_kilos 
                                FROM stocks 
                                WHERE seller_id = ? AND kilos > 0
                            ');
                            $stmt->execute([$seller['id']]);
                            $stockStats = $stmt->fetch();
                            ?>
                            <tr>
                                <td><?php echo $seller['id']; ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/admin/seller-details.php?id=<?php echo $seller['id']; ?>">
                                        <?php echo htmlspecialchars($seller['name']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($seller['phone']); ?></td>
                                <td><?php echo htmlspecialchars($seller['location']); ?></td>
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
                                <td><?php echo formatDate($seller['created_at'], 'M j, Y'); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/admin/seller-details.php?id=<?php echo $seller['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>/admin/stocks.php?seller_id=<?php echo $seller['id']; ?>" 
                                       class="btn btn-sm btn-secondary">
                                        <i class="fas fa-weight"></i> Stock
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <nav aria-label="Sellers pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['has_prev']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <p class="text-muted">
                <?php if (!empty($search)): ?>
                    No sellers found matching your search. <a href="<?php echo BASE_URL; ?>/admin/sellers.php">Clear search</a>.
                <?php else: ?>
                    No sellers registered yet.
                <?php endif; ?>
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

.badge-success {
    background-color: #28a745;
    color: white;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
