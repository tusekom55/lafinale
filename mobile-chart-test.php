<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📱 Mobile Chart Test - GlobalBorsa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .test-header {
            background: linear-gradient(135deg, #007bff, #6610f2);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .test-modal .modal-dialog {
            max-width: 1200px;
        }
        
        /* Desktop Layout */
        @media (min-width: 769px) {
            .mobile-chart-tabs {
                display: none !important;
            }
            
            .desktop-layout {
                display: flex !important;
            }
        }
        
        /* Mobile Layout */
        @media (max-width: 768px) {
            .test-modal .modal-dialog {
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                margin: 0 !important;
                max-height: 90vh !important;
                border-radius: 16px 16px 0 0 !important;
                transform: translateY(100%) !important;
                transition: transform 0.3s ease !important;
            }
            
            .test-modal.show .modal-dialog {
                transform: translateY(0) !important;
            }
            
            .modal-content {
                border-radius: 16px 16px 0 0 !important;
                height: 100%;
            }
            
            .desktop-layout {
                display: none !important;
            }
            
            .mobile-chart-tabs {
                display: block !important;
            }
            
            .mobile-tab-content {
                height: calc(90vh - 120px);
                overflow-y: auto;
            }
            
            .chart-container-mobile {
                height: 300px;
                padding: 1rem;
            }
            
            .trading-container-mobile {
                padding: 1rem;
                max-height: none;
                overflow-y: visible;
            }
        }
        
        /* Tab Styling */
        .mobile-chart-tabs .nav-link {
            border-radius: 0;
            border: none;
            border-bottom: 3px solid transparent;
            background: none;
            padding: 1rem 1.5rem;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        
        .mobile-chart-tabs .nav-link.active {
            background: none;
            color: #007bff;
            border-bottom-color: #007bff;
        }
        
        .mobile-chart-tabs .nav-link:hover {
            background: #f8f9fa;
            color: #495057;
        }
        
        /* Chart specific */
        .chart-container {
            height: 400px;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            border: 1px solid #e9ecef;
        }
        
        .chart-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        /* Trading form styling */
        .trading-section {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
        }
        
        .device-indicator {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            z-index: 9999;
        }
        
        /* Test buttons */
        .test-controls {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .test-btn {
            margin: 0.25rem;
            min-width: 120px;
        }
    </style>
</head>
<body>
    <!-- Device Indicator -->
    <div class="device-indicator">
        <span class="d-block d-md-none">📱 Mobile View</span>
        <span class="d-none d-md-block">💻 Desktop View</span>
    </div>

    <!-- Header -->
    <div class="test-header">
        <div class="container">
            <h1><i class="fas fa-flask me-2"></i>Mobile Chart Test Lab</h1>
            <p class="mb-0">Test different chart layouts for mobile and desktop</p>
        </div>
    </div>

    <div class="container">
        <!-- Test Controls -->
        <div class="test-controls">
            <h5><i class="fas fa-cogs me-2"></i>Test Controls</h5>
            <div class="row">
                <div class="col-md-6">
                    <h6>Test Modal</h6>
                    <button type="button" class="btn btn-primary test-btn" data-bs-toggle="modal" data-bs-target="#testModal">
                        <i class="fas fa-chart-line me-1"></i>Chart Modal'ı Aç
                    </button>
                    <button type="button" class="btn btn-outline-primary test-btn" onclick="testResponsive()">
                        <i class="fas fa-mobile-alt me-1"></i>Responsive Test
                    </button>
                </div>
                <div class="col-md-6">
                    <h6>Quick Tests</h6>
                    <button type="button" class="btn btn-success test-btn" onclick="openTestModal('AAPL', '175.50')">
                        📈 AAPL Test
                    </button>
                    <button type="button" class="btn btn-warning test-btn" onclick="openTestModal('NVDA', '435.20')">
                        🚀 NVDA Test  
                    </button>
                </div>
            </div>
        </div>

        <!-- Info Cards -->
        <div class="row">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-desktop fa-2x text-primary mb-3"></i>
                        <h6>Desktop Layout</h6>
                        <p class="text-muted small">Chart + Trading yan yana</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-mobile-alt fa-2x text-success mb-3"></i>
                        <h6>Mobile Layout</h6>
                        <p class="text-muted small">Tab sistemi ile ayrılmış</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-area fa-2x text-info mb-3"></i>
                        <h6>TradingView</h6>
                        <p class="text-muted small">Responsive widget</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Modal -->
    <div class="modal fade test-modal" id="testModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" 
                             style="width: 40px; height: 40px;">
                            <i class="fas fa-chart-line text-white"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0" id="testSymbol">AAPL</h5>
                            <small class="text-muted" id="testName">Apple Inc.</small>
                        </div>
                        <div class="ms-auto text-end">
                            <div class="h5 mb-0" id="testPrice">$175.50</div>
                            <small class="text-success" id="testChange">+2.35%</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-0">
                    <!-- Mobile Tab Navigation -->
                    <div class="mobile-chart-tabs" style="display: none;">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item flex-fill" role="presentation">
                                <button class="nav-link active w-100" id="trading-tab-mobile" data-bs-toggle="tab" 
                                        data-bs-target="#trading-pane-mobile" type="button" role="tab">
                                    <i class="fas fa-coins me-1"></i>💰 İşlem
                                </button>
                            </li>
                            <li class="nav-item flex-fill" role="presentation">
                                <button class="nav-link w-100" id="chart-tab-mobile" data-bs-toggle="tab" 
                                        data-bs-target="#chart-pane-mobile" type="button" role="tab">
                                    <i class="fas fa-chart-line me-1"></i>📈 Grafik
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content mobile-tab-content">
                            <!-- Mobile Trading Tab - İLK AKTIF -->
                            <div class="tab-pane fade show active" id="trading-pane-mobile" role="tabpanel">
                                <div class="trading-container-mobile">
                                    <div class="trading-section">
                                        <h6><i class="fas fa-shopping-cart me-2"></i>İşlem Yap</h6>
                                        <form>
                                            <div class="mb-3">
                                                <label class="form-label">USD Miktar</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" placeholder="10.00" step="0.01">
                                                    <span class="input-group-text">USD</span>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Kaldıraç <span class="badge bg-primary">1x</span></label>
                                                <input type="range" class="form-range" min="1" max="100" value="1">
                                                <div class="d-flex justify-content-between">
                                                    <small class="text-muted">1x</small>
                                                    <small class="text-muted">100x</small>
                                                </div>
                                            </div>
                                            
                                            <div class="card border-0 bg-light mb-3">
                                                <div class="card-body p-3">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <small class="text-muted">Toplam:</small>
                                                        <small class="fw-bold">$10.00</small>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <small class="text-muted">Ücret:</small>
                                                        <small class="fw-bold">$0.01</small>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <small class="text-muted">Net:</small>
                                                        <small class="fw-bold">$10.01</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <button type="button" class="btn btn-success w-100">
                                                        <i class="fas fa-arrow-up me-1"></i>AL
                                                    </button>
                                                </div>
                                                <div class="col-6">
                                                    <button type="button" class="btn btn-danger w-100">
                                                        <i class="fas fa-arrow-down me-1"></i>SAT
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Mobile Chart Tab -->
                            <div class="tab-pane fade" id="chart-pane-mobile" role="tabpanel">
                                <div class="chart-container-mobile">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">📈 Fiyat Grafiği</h6>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-secondary">1D</button>
                                            <button type="button" class="btn btn-outline-secondary active">1H</button>
                                            <button type="button" class="btn btn-outline-secondary">15M</button>
                                        </div>
                                    </div>
                                    <div class="chart-container">
                                        <iframe id="tradingview-mobile" 
                                                src="https://www.tradingview.com/widgetembed/?frameElementId=tradingview_mobile&symbol=AAPL&interval=1H&hidesidetoolbar=1&hidetoptoolbar=1&symboledit=1&saveimage=1&toolbarbg=F1F3F6&studies=[]&hideideas=1&theme=Light&style=1&timezone=Etc%2FUTC&locale=en&utm_source=localhost&utm_medium=widget&utm_campaign=chart&utm_term=AAPL">
                                        </iframe>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    
                    <!-- Desktop Layout -->
                    <div class="desktop-layout row g-0">
                        <!-- Trading Section - SOL TARAF -->
                        <div class="col-md-4 border-end">
                            <div class="p-3">
                                <div class="trading-section">
                                    <ul class="nav nav-pills nav-fill mb-3">
                                        <li class="nav-item">
                                            <button class="nav-link active">
                                                <i class="fas fa-arrow-up me-1"></i>LONG
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link">
                                                <i class="fas fa-arrow-down me-1"></i>SHORT
                                            </button>
                                        </li>
                                    </ul>
                                    
                                    <form>
                                        <div class="mb-3">
                                            <label class="form-label">USD Miktar</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" placeholder="10.00" step="0.01">
                                                <span class="input-group-text">USD</span>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Kaldıraç <span class="badge bg-primary">1x</span></label>
                                            <input type="range" class="form-range" min="1" max="100" value="1">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">1x</small>
                                                <small class="text-muted">100x</small>
                                            </div>
                                        </div>
                                        
                                        <div class="card border-0 bg-light mb-3">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <small class="text-muted">Toplam Değer:</small>
                                                    <small class="fw-bold">$10.00</small>
                                                </div>
                                                <div class="d-flex justify-content-between mb-1">
                                                    <small class="text-muted">İşlem Ücreti:</small>
                                                    <small class="fw-bold">$0.01</small>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <small class="text-muted">Gerekli Margin:</small>
                                                    <small class="fw-bold">$10.01</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="button" class="btn btn-success w-100">
                                            <i class="fas fa-arrow-up me-2"></i>LONG POZISYON AÇ
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Chart Section - SAĞ TARAF -->
                        <div class="col-md-8">
                            <div class="p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">📈 Fiyat Grafiği</h6>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary">1D</button>
                                        <button type="button" class="btn btn-outline-secondary active">1H</button>
                                        <button type="button" class="btn btn-outline-secondary">15M</button>
                                    </div>
                                </div>
                                <div class="chart-container">
                                    <iframe id="tradingview-desktop" 
                                            src="https://www.tradingview.com/widgetembed/?frameElementId=tradingview_desktop&symbol=AAPL&interval=1H&hidesidetoolbar=1&hidetoptoolbar=1&symboledit=1&saveimage=1&toolbarbg=F1F3F6&studies=[]&hideideas=1&theme=Light&style=1&timezone=Etc%2FUTC&locale=en&utm_source=localhost&utm_medium=widget&utm_campaign=chart&utm_term=AAPL">
                                    </iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Test functions
        function openTestModal(symbol, price) {
            document.getElementById('testSymbol').textContent = symbol;
            document.getElementById('testName').textContent = getCompanyName(symbol);
            document.getElementById('testPrice').textContent = '$' + price;
            
            // Update TradingView widgets
            const desktopFrame = document.getElementById('tradingview-desktop');
            const mobileFrame = document.getElementById('tradingview-mobile');
            
            const newSrc = `https://www.tradingview.com/widgetembed/?frameElementId=tradingview&symbol=${symbol}&interval=1H&hidesidetoolbar=1&hidetoptoolbar=1&symboledit=1&saveimage=1&toolbarbg=F1F3F6&studies=[]&hideideas=1&theme=Light&style=1&timezone=Etc%2FUTC&locale=en&utm_source=localhost&utm_medium=widget&utm_campaign=chart&utm_term=${symbol}`;
            
            desktopFrame.src = newSrc;
            mobileFrame.src = newSrc;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('testModal'));
            modal.show();
        }
        
        function getCompanyName(symbol) {
            const names = {
                'AAPL': 'Apple Inc.',
                'NVDA': 'NVIDIA Corporation',
                'MSFT': 'Microsoft Corporation',
                'GOOGL': 'Alphabet Inc.'
            };
            return names[symbol] || symbol + ' Corporation';
        }
        
        function testResponsive() {
            alert('📱 Responsive Test:\n\n1. Ekranı küçültüp büyülterek test edin\n2. F12 > Device toolbar ile mobile simüle edin\n3. Tab\'ların çalıştığını kontrol edin');
        }
        
        // Page load actions
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🧪 Mobile Chart Test loaded');
            console.log('📊 TradingView widgets ready');
            
            // Auto-open modal for immediate testing
            setTimeout(() => {
                const testBtn = document.querySelector('[data-bs-target="#testModal"]');
                if (testBtn) {
                    testBtn.click();
                }
            }, 1000);
        });
        
        // Tab switching debug
        document.addEventListener('shown.bs.tab', function (e) {
            console.log('Tab switched:', e.target.textContent);
        });
    </script>
</body>
</html>
