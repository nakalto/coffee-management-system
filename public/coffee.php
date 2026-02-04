<?php
// Public coffee browsing page

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = Database::getInstance()->getConnection();

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// Filters
$search = sanitizeInput($_GET['search'] ?? '');
$coffeeTypeId = intval($_GET['coffee_type_id'] ?? 0);
$location = sanitizeInput($_GET['location'] ?? '');
$sortBy = $_GET['sort'] ?? 'updated_desc';

// Build WHERE clause
$whereClause = 'WHERE s.kilos > 0';
$params = [];

if (!empty($search)) {
    $whereClause .= ' AND (ct.name LIKE ? OR u.name LIKE ? OR u.location LIKE ?)';
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($coffeeTypeId > 0) {
    $whereClause .= ' AND s.coffee_type_id = ?';
    $params[] = $coffeeTypeId;
}

if (!empty($location)) {
    $whereClause .= ' AND u.location LIKE ?';
    $locationParam = "%$location%";
    $params[] = $locationParam;
}

// Build ORDER BY clause
$orderBy = 'ORDER BY s.updated_at DESC';
switch ($sortBy) {
    case 'kilos_desc':
        $orderBy = 'ORDER BY s.kilos DESC';
        break;
    case 'kilos_asc':
        $orderBy = 'ORDER BY s.kilos ASC';
        break;
    case 'name_asc':
        $orderBy = 'ORDER BY ct.name ASC';
        break;
    case 'name_desc':
        $orderBy = 'ORDER BY ct.name DESC';
        break;
    case 'location_asc':
        $orderBy = 'ORDER BY u.location ASC';
        break;
    case 'updated_asc':
        $orderBy = 'ORDER BY s.updated_at ASC';
        break;
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

// Get coffee stocks with pagination
$sql = "
    SELECT s.id, s.kilos, s.updated_at,
           u.id as seller_id, u.name as seller_name, u.phone as seller_phone, u.location,
           ct.id as coffee_type_id, ct.name as coffee_type_name
    FROM stocks s
    JOIN users u ON s.seller_id = u.id
    JOIN coffee_types ct ON s.coffee_type_id = ct.id
    $whereClause
    $orderBy
    LIMIT ? OFFSET ?
";
$stmt = $db->prepare($sql);
$stockParams = array_merge($params, [$limit, $offset]);
$stmt->execute($stockParams);
$stocks = $stmt->fetchAll();

// Get filter options
$coffeeTypes = [];
$stmt = $db->query('SELECT id, name FROM coffee_types ORDER BY name');
$coffeeTypes = $stmt->fetchAll();

// Get unique locations
$stmt = $db->query('
    SELECT DISTINCT u.location 
    FROM stocks s
    JOIN users u ON s.seller_id = u.id
    WHERE s.kilos > 0
    ORDER BY u.location
');
$locations = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get statistics
$stmt = $db->query('SELECT COUNT(*) as total FROM stocks WHERE kilos > 0');
$totalStockRecords = $stmt->fetch()['total'];

$stmt = $db->query('SELECT SUM(kilos) as total FROM stocks WHERE kilos > 0');
$totalKilos = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query('SELECT COUNT(DISTINCT seller_id) as total FROM stocks WHERE kilos > 0');
$totalSellers = $stmt->fetch()['total'];

$pageTitle = 'Available Coffee';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section-coffee">
    <div class="container">
        <div class="hero-content text-center">
            <h1 class="hero-title">
                <i class="fas fa-coffee"></i>
                Available Coffee
            </h1>
            <p class="hero-subtitle">Browse our selection of premium coffee from local sellers</p>
        </div>
    </div>
</section>

<!-- Statistics -->
<div class="container">
    <div class="dashboard-grid mb-4">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-coffee"></i>
            </div>
            <div class="stat-number"><?php echo number_format($totalStockRecords); ?></div>
            <div class="stat-label">Available Stock</div>
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
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number"><?php echo number_format($totalSellers); ?></div>
            <div class="stat-label">Active Sellers</div>
        </div>
    </div>
</div>

<!-- Search and Filters -->
<div class="container">
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-filter"></i> Search & Filter Coffee</h5>
        </div>
        <div class="card-body">
            <form method="GET" id="search-form">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" 
                                   id="search-input" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Coffee type, seller, location..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="coffee_type_id" class="form-label">Coffee Type</label>
                            <select name="coffee_type_id" id="coffee_type_id" class="form-control">
                                <option value="">All Types</option>
                                <?php foreach ($coffeeTypes as $type): ?>
                                    <option value="<?php echo $type['id']; ?>" <?php echo $coffeeTypeId === $type['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="location" class="form-label">Location</label>
                            <select name="location" id="location" class="form-control">
                                <option value="">All Locations</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $location === $loc ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($loc); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="sort" class="form-label">Sort By</label>
                            <select name="sort" id="sort" class="form-control">
                                <option value="updated_desc" <?php echo $sortBy === 'updated_desc' ? 'selected' : ''; ?>>Recently Updated</option>
                                <option value="kilos_desc" <?php echo $sortBy === 'kilos_desc' ? 'selected' : ''; ?>>Most Stock</option>
                                <option value="kilos_asc" <?php echo $sortBy === 'kilos_asc' ? 'selected' : ''; ?>>Least Stock</option>
                                <option value="name_asc" <?php echo $sortBy === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                                <option value="name_desc" <?php echo $sortBy === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                                <option value="location_asc" <?php echo $sortBy === 'location_asc' ? 'selected' : ''; ?>>Location (A-Z)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <a href="<?php echo BASE_URL; ?>/public/coffee.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear All
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Coffee Results -->
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Available Coffee (<?php echo number_format($total); ?> found)</h5>
        <?php if (!isLoggedIn()): ?>
            <a href="<?php echo BASE_URL; ?>/seller/register.php" class="btn btn-outline-primary">
                <i class="fas fa-store"></i> Become a Seller
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($stocks)): ?>
        <div id="search-results">
            <div class="coffee-grid" id="coffee-results">
                <?php foreach ($stocks as $stock): ?>
                    <div class="coffee-card">
                        <div class="coffee-card-image">
                            <i class="fas fa-coffee"></i>
                        </div>
                        <div class="coffee-card-body">
                            <div class="coffee-card-title">
                                <?php echo htmlspecialchars($stock['coffee_type_name']); ?>
                            </div>
                            
                            <div class="coffee-card-info">
                                <i class="fas fa-user"></i> 
                                <strong><?php echo htmlspecialchars($stock['seller_name']); ?></strong>
                            </div>
                            
                            <div class="coffee-card-info">
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo htmlspecialchars($stock['location']); ?>
                            </div>
                            
                            <div class="coffee-card-info">
                                <i class="fas fa-phone"></i> 
                                <?php echo htmlspecialchars($stock['seller_phone']); ?>
                            </div>
                            
                            <div class="coffee-card-price">
                                <?php echo number_format((float)$stock['kilos'], 1); ?> kg
                            </div>
                            
                            <div class="coffee-card-info text-muted">
                                <small><i class="fas fa-clock"></i> Updated: <?php echo formatDate($stock['updated_at'], 'M j, Y'); ?></small>
                            </div>
                            
                            <div class="coffee-card-actions">
                                <button class="btn btn-sm btn-primary" onclick="showContactInfo(<?php echo (int)$stock['id']; ?>)">
                                    <i class="fas fa-envelope"></i> Contact Seller
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <nav aria-label="Coffee pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['has_prev']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo buildQueryString($search, $coffeeTypeId, $location, $sortBy); ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo buildQueryString($search, $coffeeTypeId, $location, $sortBy); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo buildQueryString($search, $coffeeTypeId, $location, $sortBy); ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
        
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-coffee fa-3x text-muted mb-3"></i>
            <h5>No Coffee Found</h5>
            <p class="text-muted">
                <?php if (!empty($search) || $coffeeTypeId > 0 || !empty($location)): ?>
                    No coffee found matching your criteria. <a href="<?php echo BASE_URL; ?>/public/coffee.php">Clear filters</a> to see all available coffee.
                <?php else: ?>
                    No coffee is currently available. Check back later or <a href="<?php echo BASE_URL; ?>/seller/register.php">become a seller</a> to add coffee to the platform.
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<!-- Contact Modal -->
<div class="modal fade" id="contactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Contact Seller</h5>
                <button type="button" class="btn-close" data-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contact-info">
                <!-- Contact info will be loaded here -->
            </div>
        </div>
    </div>
</div>

<?php
// Helper function to build query string
function buildQueryString($search, $coffeeTypeId, $location, $sortBy) {
    $params = [];
    if (!empty($search)) $params[] = 'search=' . urlencode($search);
    if ($coffeeTypeId > 0) $params[] = 'coffee_type_id=' . $coffeeTypeId;
    if (!empty($location)) $params[] = 'location=' . urlencode($location);
    if ($sortBy !== 'updated_desc') $params[] = 'sort=' . urlencode($sortBy);
    return empty($params) ? '' : '&' . implode('&', $params);
}
?>

<style>
.hero-section-coffee {
    background: linear-gradient(135deg, #6f4e37 0%, #8b6f47 100%);
    color: white;
    padding: 4rem 0;
    margin-bottom: 2rem;
}

.hero-title {
    font-size: 3rem;
    font-weight: bold;
    margin-bottom: 1rem;
}

.hero-subtitle {
    font-size: 1.25rem;
    opacity: 0.9;
}

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

.coffee-card-actions {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e0e0e0;
}
</style>

<script>
// Store stock data for contact modal
const stockData = <?php echo json_encode($stocks); ?>;

function showContactInfo(stockId) {
    const stock = stockData.find(s => s.id == stockId);
    if (stock) {
        const contactInfo = `
            <div class="contact-details">
                <h6>Coffee Details</h6>
                <p><strong>Type:</strong> ${stock.coffee_type_name}</p>
                <p><strong>Available:</strong> ${parseFloat(stock.kilos).toFixed(1)} kg</p>
                
                <h6 class="mt-3">Seller Information</h6>
                <p><strong>Name:</strong> ${stock.seller_name}</p>
                <p><strong>Location:</strong> ${stock.location}</p>
                <p><strong>Phone:</strong> ${stock.seller_phone}</p>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i>
                    Please contact the seller directly using the phone number above to inquire about purchasing this coffee.
                </div>
            </div>
        `;
        
        document.getElementById('contact-info').innerHTML = contactInfo;
        document.getElementById('contactModal').classList.add('show');
    }
}

// Close modal functionality
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-close') || e.target.classList.contains('modal')) {
        document.getElementById('contactModal').classList.remove('show');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
