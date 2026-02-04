        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4><?php echo APP_NAME; ?></h4>
                    <p>Professional coffee management system for vendors and customers.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>/index.php">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/public/coffee.php">Available Coffee</a></li>
                        <?php if (!isLoggedIn()): ?>
                            <li><a href="<?php echo BASE_URL; ?>/seller/register.php">Register as Seller</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p>Email: herieth.ngungulu@icloud.com</p>
                    <p>Phone: +255 672 191 968</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
