<?php
// View and manage seller stocks

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Require seller role
requireRole(ROLE_SELLER);

$sellerId = $_SESSION['user_id'];
$db = Database::getInstance()->getConnection();

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$search = sanitizeInput($_GET['search'] ?? '');
$whereClause = 'WHERE s.seller_id = ?';
$params = [$sellerId];

if (!empty($search)) {
    $whereClause .= ' AND ct.name LIKE ?';
    $searchParam = "%$search%";
    $params[] = $searchParam;
}

// Get total records count
$countSql = "
    SELECT COUNT(*) as total 
    FROM stocks s
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
           ct.id as coffee_type_id, ct.name as coffee_type_name
    FROM stocks s
    JOIN coffee_types ct ON s.coffee_type_id = ct.id
    $whereClause
    ORDER BY s.updated_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $db->prepare($sql);
$stockParams = array_merge($params, [$limit, $offset]);
$stmt->execute($stockParams);
$stocks = $stmt->fetchAll();

// Get statistics
$stmt = $db->prepare('SELECT COUNT(*) as count FROM stocks WHERE seller_id = ?');
$stmt->execute([$sellerId]);
$totalStockRecords = $stmt->fetch()['count'];

$stmt = $db->prepare('SELECT SUM(kilos) as total FROM stocks WHERE seller_id = ? AND kilos > 0');
$stmt->execute([$sellerId]);
$totalKilos = $stmt->fetch()['total'] ?? 0;

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid request. Please try again.');
    } else {
        $stockId = intval($_POST['stock_id'] ?? 0);
        
        if ($stockId > 0) {
            try {
                // Verify stock belongs to this seller
                $stmt = $db->prepare('SELECT id FROM stocks WHERE id = ? AND seller_id = ?');
                $stmt->execute([$stockId, $sellerId]);
                if ($stmt->fetch()) {
                    $stmt = $db->prepare('DELETE FROM stocks WHERE id = ? AND seller_id = ?');
                    $stmt->execute([$stockId, $sellerId]);
                    setFlashMessage('success', 'Stock record deleted successfully!');
                    redirect(BASE_URL . '/seller/stocks.php');
                } else {
                    setFlashMessage('error', 'Stock record not found.');
                }
            } catch (PDOException $e) {
                error_log('Delete stock error: ' . $e->getMessage());
                setFlashMessage('error', 'Failed to delete stock. Please try again.');
            }
        }
    }
}

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
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-plus"></i>
        </div>
        <div class="stat-number">
            <a href="<?php echo BASE_URL; ?>/seller/add-stock.php" class="btn btn-primary btn-sm">
                Add Stock
            </a>
        </div>
        <div class="stat-label">Quick Action</div>
    </div>
</div>

<!-- Search and Actions -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-search"></i> Search & Actions</h5>
    </div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-8">
                <form method="GET" class="search-form">
                    <div class="form-group mb-0">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search by coffee type..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="<?php echo BASE_URL; ?>/seller/stocks.php" class="btn btn-secondary mt-2">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-4 text-right">
                <a href="<?php echo BASE_URL; ?>/seller/add-stock.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Stock
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stock List -->
<div class="card">
    <div class="card-header">
        <h5>Your Stock Records (<?php echo number_format($total); ?> total)</h5>
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
                            <th>Actions</th>
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
                                <td>
                                    <!-- Update Stock Button -->
                                    <button type="button" 
                                            class="btn btn-sm btn-primary" 
                                            data-toggle="modal" 
                                            data-target="#updateModal<?php echo $stock['id']; ?>">
                                        <i class="fas fa-edit"></i> Update
                                    </button>
                                    
                                    <!-- Delete Form -->
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Are you sure you want to delete this stock record?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="stock_id" value="<?php echo $stock['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            
                            <!-- Update Stock Modal -->
                            <div class="modal fade" id="updateModal<?php echo $stock['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Update Stock</h5>
                                            <button type="button" class="btn-close" data-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" action="<?php echo BASE_URL; ?>/seller/update-stock.php" data-validate>
                                                <input type="hidden" name="stock_id" value="<?php echo $stock['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Coffee Type</label>
                                                    <input type="text" 
                                                           class="form-control" 
                                                           value="<?php echo htmlspecialchars($stock['coffee_type_name']); ?>" 
                                                           readonly>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="kilos_<?php echo $stock['id']; ?>" class="form-label">New Kilos Amount</label>
                                                    <input type="number" 
                                                           id="kilos_<?php echo $stock['id']; ?>"
                                                           name="kilos" 
                                                           class="form-control" 
                                                           placeholder="Enter new kilos amount"
                                                           step="0.1"
                                                           min="0"
                                                           required>
                                                    <small class="form-text text-muted">Current: <?php echo number_format($stock['kilos'], 1); ?> kg</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save"></i> Update Stock
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                        Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <h5>No Stock Records Found</h5>
                <p class="text-muted">
                    <?php if (!empty($search)): ?>
                        No stock records found matching your search. <a href="<?php echo BASE_URL; ?>/seller/stocks.php">Clear search</a>.
                    <?php else: ?>
                        You haven't added any stock yet. <a href="<?php echo BASE_URL; ?>/seller/add-stock.php">Add your first stock</a> to get started.
                    <?php endif; ?>
                </p>
                <a href="<?php echo BASE_URL; ?>/seller/add-stock.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Stock
                </a>
            </div>
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

.modal {
    display: none;
    position: fixed;
    z-index: 1050;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal.show {
    display: block;
}

.modal-dialog {
    position: relative;
    width: auto;
    max-width: 500px;
    margin: 1.75rem auto;
}

.modal-content {
    background: white;
    border-radius: 0.3rem;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}

.modal-header {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
}

.modal-body {
    padding: 1rem;
}

.btn-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
}
</style>

<script>
// Simple modal functionality
document.addEventListener('click', function(e) {
    if (e.target.hasAttribute('data-toggle') && e.target.getAttribute('data-toggle') === 'modal') {
        e.preventDefault();
        const targetId = e.target.getAttribute('data-target');
        const modal = document.querySelector(targetId);
        if (modal) {
            modal.classList.add('show');
        }
    }
    
    if (e.target.classList.contains('btn-close') || e.target.classList.contains('modal')) {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => modal.classList.remove('show'));
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
