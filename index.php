<?php
// Home page for Coffee Management System

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// Get statistics for dashboard
try {
    $db = Database::getInstance()->getConnection();
    
    // Total sellers
    $stmt = $db->prepare('SELECT COUNT(*) as total FROM users WHERE role = ?');
    $stmt->execute([ROLE_SELLER]);
    $totalSellers = $stmt->fetch()['total'];
    
    // Total coffee types
    $stmt = $db->query('SELECT COUNT(*) as total FROM coffee_types');
    $totalCoffeeTypes = $stmt->fetch()['total'];
    
    // Total available stock
    $stmt = $db->query('SELECT SUM(kilos) as total FROM stocks WHERE kilos > 0');
    $totalStock = $stmt->fetch()['total'] ?? 0;
    
    // Recent stocks
    $stmt = $db->query('
        SELECT s.kilos, ct.name as coffee_type, u.name as seller_name, u.location, s.updated_at
        FROM stocks s
        JOIN coffee_types ct ON s.coffee_type_id = ct.id
        JOIN users u ON s.seller_id = u.id
        WHERE s.kilos > 0
        ORDER BY s.updated_at DESC
        LIMIT 6
    ');
    $recentStocks = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log('Dashboard error: ' . $e->getMessage());
    $totalSellers = $totalCoffeeTypes = $totalStock = 0;
    $recentStocks = [];
}

$pageTitle = 'Welcome to Coffee Management System';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content text-center">
            <h1 class="hero-title">
                <i class="fas fa-coffee"></i>
                <?php echo APP_NAME; ?>
            </h1>
            <p class="hero-subtitle">Connecting Coffee Sellers with Customers</p>
            <div class="hero-buttons">
                <a href="<?php echo BASE_URL; ?>/public/coffee.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-search"></i> Browse Coffee
                </a>
                <?php if (!isLoggedIn()): ?>
                    <a href="<?php echo BASE_URL; ?>/seller/register.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-store"></i> Become a Seller
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="statistics-section">
    <div class="container">
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo number_format($totalSellers); ?></div>
                <div class="stat-label">Registered Sellers</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-coffee"></i>
                </div>
                <div class="stat-number"><?php echo number_format($totalCoffeeTypes); ?></div>
                <div class="stat-label">Coffee Types</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-weight"></i>
                </div>
                <div class="stat-number"><?php echo number_format($totalStock, 1); ?></div>
                <div class="stat-label">Total Kilos Available</div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Stocks Section -->
<?php if (!empty($recentStocks)): ?>
<section class="recent-stocks-section">
    <div class="container">
        <div class="section-header">
            <h2>Recent Coffee Stock Updates</h2>
            <p>Latest additions to our coffee inventory</p>
        </div>
        
        <div class="coffee-grid">
            <?php foreach ($recentStocks as $stock): ?>
                <div class="coffee-card">
                    <div class="coffee-card-image">
                        <i class="fas fa-coffee"></i>
                    </div>
                    <div class="coffee-card-body">
                        <div class="coffee-card-title">
                            <?php echo htmlspecialchars($stock['coffee_type']); ?>
                        </div>
                        <div class="coffee-card-info">
                            <i class="fas fa-user"></i> 
                            <?php echo htmlspecialchars($stock['seller_name']); ?>
                        </div>
                        <div class="coffee-card-info">
                            <i class="fas fa-map-marker-alt"></i> 
                            <?php echo htmlspecialchars($stock['location']); ?>
                        </div>
                        <div class="coffee-card-price">
                            <?php echo number_format($stock['kilos'], 1); ?> kg
                        </div>
                        <div class="coffee-card-info text-muted">
                            <small>Updated: <?php echo formatDate($stock['updated_at'], 'M j, Y'); ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="<?php echo BASE_URL; ?>/public/coffee.php" class="btn btn-primary">
                <i class="fas fa-th"></i> View All Coffee
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <div class="section-header text-center">
            <h2>Why Choose Our Platform?</h2>
            <p>Simple, efficient, and reliable coffee management</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Secure Platform</h3>
                <p>Advanced security measures to protect your data and transactions.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Real-time Updates</h3>
                <p>Live inventory tracking and instant stock updates.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3>Mobile Friendly</h3>
                <p>Access your account from any device, anywhere.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>24/7 Support</h3>
                <p>Dedicated customer support to help you succeed.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<style>
/* Additional styles for home page */
.hero-section {
    background: linear-gradient(135deg, #6f4e37 0%, #8b6f47 100%);
    color: white;
    padding: 6rem 0;
    margin-bottom: 3rem;
}

.hero-title {
    font-size: 3.5rem;
    font-weight: bold;
    margin-bottom: 1rem;
}

.hero-subtitle {
    font-size: 1.25rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.hero-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-outline-light {
    background: transparent;
    color: white;
    border: 2px solid white;
}

.btn-outline-light:hover {
    background: white;
    color: #6f4e37;
}

.statistics-section {
    margin-bottom: 3rem;
}

.recent-stocks-section {
    margin-bottom: 3rem;
}

.section-header {
    text-align: center;
    margin-bottom: 3rem;
}

.section-header h2 {
    color: #6f4e37;
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.section-header p {
    color: #666;
    font-size: 1.1rem;
}

.features-section {
    background: #f8f9fa;
    padding: 4rem 0;
    margin-bottom: 3rem;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.feature-card {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
}

.feature-icon {
    font-size: 3rem;
    color: #6f4e37;
    margin-bottom: 1rem;
}

.feature-card h3 {
    color: #333;
    margin-bottom: 1rem;
}

.feature-card p {
    color: #666;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .hero-title {
        font-size: 2.5rem;
    }
    
    .hero-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
}
</style>
