</div>
    <!-- End Main Content -->
    
    <!-- Footer -->
    <footer class="bg-light border-top py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo SITE_NAME; ?></h5>
                    <p class="text-muted mb-0">
                        <?php echo getCurrentLang() == 'tr' ? 
                            'Güvenli ve hızlı kripto para ve forex işlemleri.' : 
                            'Safe and fast cryptocurrency and forex trading.'; ?>
                    </p>
                </div>
                <div class="col-md-3">
                    <h6><?php echo getCurrentLang() == 'tr' ? 'Hızlı Linkler' : 'Quick Links'; ?></h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-muted text-decoration-none"><?php echo t('markets'); ?></a></li>
                        <?php if (isLoggedIn()): ?>
                        <li><a href="trading.php" class="text-muted text-decoration-none"><?php echo t('trading'); ?></a></li>
                        <li><a href="wallet.php" class="text-muted text-decoration-none"><?php echo t('wallet'); ?></a></li>
                        <?php else: ?>
                        <li><a href="login.php" class="text-muted text-decoration-none"><?php echo t('login'); ?></a></li>
                        <li><a href="register.php" class="text-muted text-decoration-none"><?php echo t('register'); ?></a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6><?php echo getCurrentLang() == 'tr' ? 'Destek' : 'Support'; ?></h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted text-decoration-none">
                            <?php echo getCurrentLang() == 'tr' ? 'Yardım Merkezi' : 'Help Center'; ?>
                        </a></li>
                        <li><a href="#" class="text-muted text-decoration-none">
                            <?php echo getCurrentLang() == 'tr' ? 'İletişim' : 'Contact'; ?>
                        </a></li>
                        <li><a href="#" class="text-muted text-decoration-none">
                            <?php echo getCurrentLang() == 'tr' ? 'API Dokümantasyonu' : 'API Documentation'; ?>
                        </a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. 
                        <?php echo getCurrentLang() == 'tr' ? 'Tüm hakları saklıdır.' : 'All rights reserved.'; ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        <?php echo getCurrentLang() == 'tr' ? 
                            'Son güncelleme: ' . date('d.m.Y H:i') : 
                            'Last update: ' . date('m/d/Y H:i'); ?>
                    </small>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <script>
        // Auto refresh market data every 30 seconds
        setInterval(function() {
            if (typeof refreshMarketData === 'function') {
                refreshMarketData();
            }
        }, 30000);
        
        // Format numbers on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading states
            const tables = document.querySelectorAll('.market-table');
            tables.forEach(table => {
                table.classList.add('table-hover');
            });
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
        
        // Price change animation
        function animatePriceChange(element, isIncrease) {
            element.style.transition = 'all 0.3s ease';
            element.style.backgroundColor = isIncrease ? '#d4edda' : '#f8d7da';
            
            setTimeout(() => {
                element.style.backgroundColor = 'transparent';
            }, 1000);
        }
        
        // Number formatting for Turkish locale
        function formatTurkishNumber(number, decimals = 2) {
            return new Intl.NumberFormat('tr-TR', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(number);
        }
        
        // Copy to clipboard function
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const toast = document.createElement('div');
                toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed top-0 end-0 m-3';
                toast.style.zIndex = '9999';
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            ${getCurrentLang() == 'tr' ? 'Panoya kopyalandı!' : 'Copied to clipboard!'}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                document.body.appendChild(toast);
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
                
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 3000);
            });
        }
        
        // Get current language for JS
        function getCurrentLang() {
            return '<?php echo getCurrentLang(); ?>';
        }
    </script>
    
    <?php if (isset($additional_js)): ?>
        <?php echo $additional_js; ?>
    <?php endif; ?>
</body>
</html>
