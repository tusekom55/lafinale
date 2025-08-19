# GlobalBorsa - Crypto & Forex Trading Platform

A modern, Paribu-style cryptocurrency and forex trading platform built with PHP, MySQL, and Bootstrap. Designed for Hostinger shared hosting compatibility.

## 🚀 Features

### Core Features
- **Real-time Market Data** - Live cryptocurrency prices from CoinGecko API
- **Paribu-style Design** - Clean, professional trading interface
- **Multi-language Support** - Turkish and English
- **User Management** - Simple registration/login system (no email verification)
- **Trading System** - Buy/sell cryptocurrencies with real money
- **Wallet System** - Multi-currency balance management
- **Payment Integration** - IBAN and Papara support
- **Admin Panel** - Manage users, deposits, withdrawals
- **Responsive Design** - Mobile-friendly interface

### Technical Features
- **Hostinger Optimized** - Works on shared hosting
- **Real API Integration** - CoinGecko for market data
- **Secure Trading** - Transaction management with fees
- **Auto-refresh** - Real-time price updates every 30 seconds
- **Search & Filter** - Find markets quickly
- **Price Alerts** - Browser-based notifications
- **Activity Logging** - Track user actions

## 📋 Requirements

- **PHP 8.0+**
- **MySQL 5.7+**
- **Web Server** (Apache/Nginx)
- **Internet Connection** (for API calls)

## 🛠️ Installation

### 1. Download & Upload
```bash
# Upload all files to your hosting public_html folder
```

### 2. Database Setup
1. Create a MySQL database in your hosting control panel
2. Update database credentials in `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'your_database_name';
private $username = 'your_username';
private $password = 'your_password';
```

### 3. Run Setup
1. Visit `https://yourdomain.com/setup.php`
2. This will create all necessary database tables
3. **Delete setup.php after running it**

### 4. Configure APIs (Optional)
Update `config/api_keys.php` with your API keys:
- CoinGecko API (free tier works)
- Alpha Vantage for forex data
- Papara API for payments

### 5. Set Up Cron Job
Add this cron job to update market data every 5 minutes:
```bash
*/5 * * * * php /path/to/your/site/api/update_markets.php
```

## 🎯 Default Login

**Admin Account:**
- Username: `admin`
- Password: `admin123`

**New User Bonus:**
- All new registrations get 1,000 TL demo balance

## 📁 Project Structure

```
/
├── config/
│   ├── database.php          # Database configuration
│   ├── api_keys.php          # API keys and settings
│   └── languages.php         # Multi-language support
├── includes/
│   ├── functions.php         # Core functions
│   ├── header.php           # Page header
│   └── footer.php           # Page footer
├── api/
│   ├── get_market_data.php  # Market data API endpoint
│   └── update_markets.php   # Cron job for data updates
├── assets/
│   ├── css/style.css        # Custom styles
│   └── js/main.js           # JavaScript functionality
├── admin/                   # Admin panel (to be created)
├── logs/                    # Log files
├── index.php               # Main markets page
├── login.php               # User login
├── register.php            # User registration
├── logout.php              # Logout handler
└── setup.php               # Initial setup (delete after use)
```

## 🎨 Design Features

### Paribu-inspired Interface
- Clean white background
- Professional market table
- Real-time price updates with animations
- Responsive design for all devices
- Modern Bootstrap 5 components

### Color Scheme
- Primary: #007bff (Blue)
- Success: #28a745 (Green for gains)
- Danger: #dc3545 (Red for losses)
- Background: #f8f9fa (Light gray)

## 💰 Trading Features

### Supported Markets
- **Crypto/TL** - Bitcoin, Ethereum, etc. vs Turkish Lira
- **Crypto/USDT** - Cryptocurrencies vs Tether
- **Forex** - Major currency pairs

### Trading System
- **Market Orders** - Instant buy/sell at current price
- **Real-time Pricing** - Live market data
- **Trading Fees** - 0.25% per transaction
- **Balance Management** - Multi-currency wallets

### Payment Methods
- **IBAN** - Bank transfer (manual approval)
- **Papara** - Turkish payment system
- **Crypto Deposits** - Direct wallet transfers

## 🔧 Configuration

### Database Tables
- `users` - User accounts and balances
- `markets` - Market data and prices
- `transactions` - Trading history
- `deposits` - Money deposits
- `withdrawals` - Money withdrawals

### API Configuration
```php
// CoinGecko API (Free)
define('COINGECKO_API_URL', 'https://api.coingecko.com/api/v3');

// Trading Fees
define('TRADING_FEE', 0.25); // 0.25%
define('MIN_TRADE_AMOUNT', 10.00); // 10 TL minimum
```

## 🚀 Deployment on Hostinger

### Step-by-Step Guide
1. **Upload Files** - Use File Manager or FTP
2. **Create Database** - In hPanel > MySQL Databases
3. **Update Config** - Edit database credentials
4. **Run Setup** - Visit setup.php once
5. **Set Cron Job** - In hPanel > Cron Jobs
6. **Test Site** - Visit your domain

### Hostinger-Specific Settings
- Uses file-based caching (no Redis needed)
- Optimized for shared hosting limits
- Compatible with PHP 8.x
- MySQL 8.0 support

## 🔒 Security Features

### Basic Security
- Password hashing with PHP's password_hash()
- SQL injection protection with prepared statements
- XSS protection with input sanitization
- Session management
- CSRF token support (in functions)

### Note on Security
This is a demo/educational project. For production use, implement:
- SSL/HTTPS
- Two-factor authentication
- Rate limiting
- Advanced fraud detection
- KYC/AML compliance
- Professional security audit

## 🌐 Multi-language Support

### Supported Languages
- **Turkish (TR)** - Default
- **English (EN)**

### Adding New Languages
1. Edit `config/languages.php`
2. Add new language array
3. Update language switcher in header

## 📊 API Endpoints

### Market Data
```
GET /api/get_market_data.php?category=crypto_tl&limit=50
```

### Response Format
```json
{
  "success": true,
  "markets": [...],
  "count": 50,
  "category": "crypto_tl",
  "timestamp": 1640995200
}
```

## 🐛 Troubleshooting

### Common Issues

**Market data not loading:**
- Check internet connection
- Verify CoinGecko API is accessible
- Check error logs in `/logs/`

**Database connection errors:**
- Verify database credentials
- Check if database exists
- Ensure MySQL service is running

**Cron job not working:**
- Check file permissions
- Verify cron job syntax
- Check hosting provider's cron job settings

## 📝 License

This project is for educational purposes. Use at your own risk for production environments.

## 🤝 Contributing

This is a demo project. Feel free to fork and modify for your needs.

## ⚠️ Disclaimer

- This is a demo trading platform
- Not suitable for real money trading without proper security measures
- Always implement proper KYC/AML compliance for real trading
- Consult legal experts for financial service regulations

## 📞 Support

For issues related to this demo:
1. Check the troubleshooting section
2. Review the code comments
3. Test with the demo admin account

---

**Built with ❤️ for educational purposes**
