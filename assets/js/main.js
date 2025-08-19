// Main JavaScript file for GlobalBorsa

// Global variables
let marketUpdateInterval;
let priceAlerts = [];

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Initialize tooltips
    initializeTooltips();
    
    // Start market data updates if on markets page
    if (window.location.pathname.includes('index.php') || window.location.pathname === '/') {
        startMarketUpdates();
    }
    
    // Initialize price alerts
    loadPriceAlerts();
    
    // Add click handlers for market rows
    addMarketRowHandlers();
}

function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

function startMarketUpdates() {
    // Update every 30 seconds
    marketUpdateInterval = setInterval(function() {
        refreshMarketData();
    }, 30000);
}

function refreshMarketData() {
    const urlParams = new URLSearchParams(window.location.search);
    const category = urlParams.get('group') || 'crypto_tl';
    
    fetch(`api/get_market_data.php?category=${category}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateMarketTable(data.markets);
                updateLastUpdateTime();
            }
        })
        .catch(error => {
            console.error('Error refreshing market data:', error);
        });
}

function updateMarketTable(markets) {
    markets.forEach(market => {
        const row = document.querySelector(`[data-symbol="${market.symbol}"]`);
        if (row) {
            updateMarketRow(row, market);
        }
    });
}

function updateMarketRow(row, market) {
    // Update price
    const priceCell = row.querySelector('.price-cell');
    if (priceCell) {
        const oldPrice = parseFloat(priceCell.dataset.price);
        const newPrice = parseFloat(market.price);
        
        priceCell.textContent = formatPrice(newPrice);
        priceCell.dataset.price = newPrice;
        
        // Animate price change
        if (newPrice !== oldPrice) {
            animatePriceChange(priceCell, newPrice > oldPrice);
        }
        
        // Check price alerts
        checkPriceAlert(market.symbol, newPrice);
    }
    
    // Update change percentage
    const changeCell = row.querySelector('.text-success, .text-danger');
    if (changeCell) {
        const sign = market.change_24h >= 0 ? '+' : '';
        const className = market.change_24h >= 0 ? 'text-success' : 'text-danger';
        changeCell.className = className;
        changeCell.innerHTML = `<span class="${className}">${sign} %${formatTurkishNumber(market.change_24h, 2)}</span>`;
    }
    
    // Update volume
    const volumeCell = row.querySelector('.text-muted');
    if (volumeCell && volumeCell.textContent.includes('B') || volumeCell.textContent.includes('M') || volumeCell.textContent.includes('K')) {
        volumeCell.textContent = formatVolume(market.volume_24h);
    }
}

function animatePriceChange(element, isIncrease) {
    element.style.transition = 'all 0.3s ease';
    element.style.backgroundColor = isIncrease ? '#d4edda' : '#f8d7da';
    element.style.transform = 'scale(1.05)';
    
    setTimeout(() => {
        element.style.backgroundColor = 'transparent';
        element.style.transform = 'scale(1)';
    }, 1000);
}

function formatPrice(price) {
    if (price >= 1000) {
        return formatTurkishNumber(price, 2);
    } else if (price >= 1) {
        return formatTurkishNumber(price, 4);
    } else {
        return formatTurkishNumber(price, 8);
    }
}

function formatTurkishNumber(number, decimals = 2) {
    return new Intl.NumberFormat('tr-TR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number);
}

function formatVolume(volume) {
    if (volume >= 1000000000) {
        return formatTurkishNumber(volume / 1000000000, 1) + 'B';
    } else if (volume >= 1000000) {
        return formatTurkishNumber(volume / 1000000, 1) + 'M';
    } else if (volume >= 1000) {
        return formatTurkishNumber(volume / 1000, 1) + 'K';
    } else {
        return formatTurkishNumber(volume, 0);
    }
}

function updateLastUpdateTime() {
    const updateElement = document.getElementById('lastUpdate');
    if (updateElement) {
        const now = new Date();
        const timeString = now.toLocaleTimeString('tr-TR');
        updateElement.textContent = `Son güncelleme: ${timeString}`;
    }
}

function addMarketRowHandlers() {
    const marketRows = document.querySelectorAll('.market-row');
    marketRows.forEach(row => {
        row.addEventListener('click', function() {
            const symbol = this.dataset.symbol;
            if (symbol) {
                window.location.href = `trading.php?pair=${symbol}`;
            }
        });
        
        // Add hover effect
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8f9fa';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
}

// Price Alert Functions
function loadPriceAlerts() {
    const stored = localStorage.getItem('priceAlerts');
    if (stored) {
        priceAlerts = JSON.parse(stored);
    }
}

function savePriceAlerts() {
    localStorage.setItem('priceAlerts', JSON.stringify(priceAlerts));
}

function addPriceAlert(symbol, targetPrice, type) {
    const alert = {
        id: Date.now(),
        symbol: symbol,
        targetPrice: targetPrice,
        type: type, // 'above' or 'below'
        created: new Date().toISOString()
    };
    
    priceAlerts.push(alert);
    savePriceAlerts();
    
    showNotification('Fiyat alarmı eklendi!', 'success');
}

function removePriceAlert(alertId) {
    priceAlerts = priceAlerts.filter(alert => alert.id !== alertId);
    savePriceAlerts();
}

function checkPriceAlert(symbol, currentPrice) {
    priceAlerts.forEach(alert => {
        if (alert.symbol === symbol) {
            let triggered = false;
            
            if (alert.type === 'above' && currentPrice >= alert.targetPrice) {
                triggered = true;
            } else if (alert.type === 'below' && currentPrice <= alert.targetPrice) {
                triggered = true;
            }
            
            if (triggered) {
                showPriceAlertNotification(alert, currentPrice);
                removePriceAlert(alert.id);
            }
        }
    });
}

function showPriceAlertNotification(alert, currentPrice) {
    const message = `${alert.symbol} fiyatı ${formatPrice(currentPrice)} TL'ye ${alert.type === 'above' ? 'yükseldi' : 'düştü'}!`;
    showNotification(message, 'warning', 10000);
    
    // Play notification sound if available
    playNotificationSound();
}

function playNotificationSound() {
    try {
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT');
        audio.play().catch(() => {
            // Ignore audio play errors
        });
    } catch (e) {
        // Ignore audio errors
    }
}

function showNotification(message, type = 'info', duration = 5000) {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed top-0 end-0 m-3`;
    toast.style.zIndex = '9999';
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    document.body.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    setTimeout(() => {
        if (document.body.contains(toast)) {
            document.body.removeChild(toast);
        }
    }, duration);
}

// Utility Functions
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showNotification('Panoya kopyalandı!', 'success');
    }).catch(function() {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showNotification('Panoya kopyalandı!', 'success');
    });
}

function formatCurrency(amount, currency = 'TL') {
    return `${formatTurkishNumber(amount, 2)} ${currency}`;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (marketUpdateInterval) {
        clearInterval(marketUpdateInterval);
    }
});

// Export functions for global use
window.GlobalBorsa = {
    refreshMarketData,
    addPriceAlert,
    showNotification,
    copyToClipboard,
    formatPrice,
    formatTurkishNumber,
    formatCurrency
};
