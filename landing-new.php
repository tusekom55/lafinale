<?php
require_once 'includes/functions.php';

$page_title = 'GlobalBorsa - Türkiye\'nin En Güvenilir Kripto Borsası';

// Get some sample market data for display  
$markets = getMarketData('us_stocks', 6);
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="Türkiye'nin en güvenilir kripto borsası. 7/24 Türkçe destek, güvenli altyapı, düşük komisyonlar.">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <link href="assets/css/landing-new.css" rel="stylesheet">
    
    <style>
        /* ===== RESET & BASE STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ===== HERO SLIDER ===== */
        .hero-slider {
            position: relative !important;
            height: 100vh !important;
            overflow: hidden !important;
            background: linear-gradient(135deg, #0f1419 0%, #1a2332 50%, #0f1419 100%) !important;
        }

        .slider-container {
            position: relative !important;
            width: 100% !important;
            height: 100% !important;
        }

        .slide {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            opacity: 0 !important;
            transition: opacity 1s ease-in-out !important;
        }

        .slide.active {
            opacity: 1 !important;
        }

        .slide-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0f1419 0%, #1a2332 50%, #0f1419 100%);
            overflow: hidden;
        }

        .slide-bg-2 {
            background: linear-gradient(135deg, #1a2332 0%, #0f1419 50%, #1a2332 100%);
        }

        .slide-bg-3 {
            background: linear-gradient(135deg, #0f1419 0%, #1a2332 50%, #0f1419 100%);
        }

        .slide-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                url("data:image/svg+xml,%3Csvg width='100' height='100' xmlns='http://www.w3.org/2000/svg'%3E%3Cdefs%3E%3Cpattern id='grid' width='50' height='50' patternUnits='userSpaceOnUse'%3E%3Cpath d='M 50 0 L 0 0 0 50' fill='none' stroke='%23ffffff' stroke-width='0.5' opacity='0.1'/%3E%3C/pattern%3E%3C/defs%3E%3Crect width='100%25' height='100%25' fill='url(%23grid)'/%3E%3C/svg%3E");
            animation: grid-move 20s linear infinite;
        }

        .slide-background::after {
            content: '';
            position: absolute;
            top: 20%;
            right: 10%;
            width: 600px;
            height: 400px;
            background: linear-gradient(45deg, transparent 40%, rgba(59, 130, 246, 0.1) 50%, transparent 60%);
            transform: perspective(800px) rotateX(25deg) rotateY(-15deg);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 8px;
            animation: chart-float 6s ease-in-out infinite;
        }

        @keyframes grid-move {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        @keyframes chart-float {
            0%, 100% { transform: perspective(800px) rotateX(25deg) rotateY(-15deg) translateY(0); }
            50% { transform: perspective(800px) rotateX(25deg) rotateY(-15deg) translateY(-20px); }
        }

        .slide-content {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            z-index: 10;
        }

        .slide-content .container {
            position: relative;
            z-index: 10;
            text-align: center;
            max-width: 900px;
            padding: 0 20px;
        }

        .hero-subtitle {
            font-size: 1.1rem;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 24px;
            letter-spacing: 0.5px;
        }

        .hero-title {
            font-size: 3.8rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 28px;
            line-height: 1.1;
            position: relative;
            z-index: 10;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            letter-spacing: -0.02em;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-title .highlight {
            color: #3b82f6;
            font-weight: 700;
        }

        .hero-description {
            font-size: 1.2rem;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 40px;
            max-width: 600px;
            line-height: 1.5;
            position: relative;
            z-index: 10;
            margin-left: auto;
            margin-right: auto;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .btn-cta {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #ffffff;
            border: none;
            padding: 18px 48px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: none;
            letter-spacing: 0.5px;
            position: relative;
            z-index: 10;
            box-shadow: 
                0 12px 30px rgba(59, 130, 246, 0.4),
                0 6px 15px rgba(59, 130, 246, 0.2);
            display: inline-block;
            text-decoration: none;
        }

        .btn-cta:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-3px);
            box-shadow: 
                0 18px 40px rgba(59, 130, 246, 0.5),
                0 8px 20px rgba(59, 130, 246, 0.3);
            color: #ffffff;
            text-decoration: none;
        }

        .btn-cta:active {
            transform: translateY(-1px);
            box-shadow: 
                0 8px 20px rgba(59, 130, 246, 0.4),
                0 4px 10px rgba(59, 130, 246, 0.2);
        }

        .hero-disclaimer {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 16px;
            font-weight: 400;
        }

        /* Auto-play Progress Bar */
        .slider-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
            transition: width 0.1s linear;
            border-radius: 4px 4px 0 0;
            z-index: 5;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.5);
        }

        /* ===== COIN TICKER SECTION ===== */
        .coin-ticker {
            background: #1a1a1a;
            padding: 40px 0;
            overflow: hidden;
            position: relative;
        }

        .ticker-header {
            text-align: center;
            margin-bottom: 30px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            padding: 0 20px;
        }

        .ticker-header h2 {
            color: #fff;
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
        }

        .ticker-container {
            width: 100% !important;
            max-width: 1400px !important;
            margin: 0 auto !important;
            overflow: hidden !important;
            position: relative !important;
            padding: 0 20px !important;
            mask: linear-gradient(90deg, transparent, white 10%, white 90%, transparent) !important;
            -webkit-mask: linear-gradient(90deg, transparent, white 10%, white 90%, transparent) !important;
        }

        .ticker-track {
            display: flex !important;
            animation: simple-scroll 20s linear infinite !important;
            white-space: nowrap !important;
            width: 4000px !important;
            animation-delay: 0s !important;
            will-change: transform !important;
        }

        .coin-item {
            display: inline-flex !important;
            align-items: center !important;
            background: rgba(255, 255, 255, 0.95) !important;
            border-radius: 12px !important;
            padding: 15px 20px !important;
            margin-right: 20px !important;
            min-width: 250px !important;
            flex-shrink: 0 !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
            transition: all 0.3s ease !important;
            cursor: pointer !important;
            backdrop-filter: blur(10px) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
        }

        .coin-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 1);
        }

        .coin-flag {
            font-size: 2rem;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: rgba(37, 99, 235, 0.1);
            border-radius: 50%;
            background-size: 30px 30px;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        /* Company Logos - Real Company Logos */
        .logo-aapl {
            background-image: url('https://logo.clearbit.com/apple.com');
            background-color: #f5f5f7;
        }
        
        .logo-msft {
            background-image: url('https://logo.clearbit.com/microsoft.com');
            background-color: #f5f5f5;
        }
        
        .logo-googl {
            background-image: url('https://logo.clearbit.com/google.com');
            background-color: #f5f5f5;
        }
        
        .logo-amzn {
            background-image: url('https://logo.clearbit.com/amazon.com');
            background-color: #f5f5f5;
        }
        
        .logo-tsla {
            background-image: url('https://logo.clearbit.com/tesla.com');
            background-color: #e31937;
        }
        
        .logo-meta {
            background-image: url('https://logo.clearbit.com/meta.com');
            background-color: #1877f2;
        }
        
        .logo-nflx {
            background-image: url('https://logo.clearbit.com/netflix.com');
            background-color: #e50914;
        }
        
        .logo-v {
            background-image: url('https://logo.clearbit.com/visa.com');
            background-color: #1418af;
        }
        
        .logo-ko {
            background-image: url('https://logo.clearbit.com/coca-cola.com');
            background-color: #f4010a;
        }
        
        .logo-jpm {
            background-image: url('https://logo.clearbit.com/jpmorganchase.com');
            background-color: #0048a0;
        }

        .coin-info {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .coin-symbol {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0d1b4c;
            margin-bottom: 3px;
            letter-spacing: 0.5px;
        }

        .coin-name {
            font-size: 0.9rem;
            color: #666;
            font-weight: 400;
        }

        @keyframes simple-scroll {
            0% {
                transform: translateX(100%);
            }
            100% {
                transform: translateX(-100%);
            }
        }

        /* Pause animation on hover */
        .coin-ticker:hover .ticker-track {
            animation-play-state: paused !important;
        }

        /* ===== SERVICES SECTION ===== */
        .services {
            padding: 80px 0;
            background: #f4f5f7;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 40px;
            margin-top: 20px;
        }

        .service-card {
            background: #fff;
            padding: 40px 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border-top: 3px solid #2563eb;
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .service-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 50%;
            color: #fff;
            font-size: 2rem;
        }

        .service-card h3 {
            font-size: 1.4rem;
            font-weight: 600;
            color: #0d1b4c;
            margin-bottom: 15px;
        }

        .service-card p {
            color: #666;
            line-height: 1.6;
        }

        /* ===== MARKET INDICATORS ===== */
        .market-indicators {
            padding: 80px 0;
            background: #0d1b4c;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 50px;
            color: #fff;
        }

        .indicators-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
        }

        .indicator-item {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .indicator-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-3px);
        }

        .indicator-item .pair {
            display: block;
            font-weight: 600;
            color: #f4f5f7;
            margin-bottom: 5px;
        }

        .indicator-item .price {
            display: block;
            font-size: 1.3rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 5px;
        }

        .indicator-item .change {
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.9rem;
        }

        .change.positive {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }

        .change.negative {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        /* ===== PROMO CARDS SECTION ===== */
        .promo-cards {
            padding: 100px 0;
            background: #f4f5f7;
        }

        .promo-cards .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 20px;
            color: #0d1b4c;
        }

        .promo-cards .section-title .highlight {
            color: #3b82f6;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: #666;
            text-align: center;
            max-width: 600px;
            margin: 0 auto 60px;
            line-height: 1.6;
        }

        .promo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .promo-card {
            position: relative;
            border-radius: 20px;
            padding: 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            cursor: pointer;
            min-height: 200px;
        }

        .promo-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }

        /* Dark Card (App) */
        .dark-card {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f172a 100%);
            color: #fff;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .dark-card:hover {
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }

        /* Blue Card (Bonus) */
        .blue-card {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 50%, #1e40af 100%);
            color: #fff;
            box-shadow: 0 15px 35px rgba(59, 130, 246, 0.3);
        }

        .blue-card:hover {
            box-shadow: 0 25px 50px rgba(59, 130, 246, 0.4);
        }

        /* Green Card (Competition) */
        .green-card {
            background: linear-gradient(135deg, #059669 0%, #047857 50%, #065f46 100%);
            color: #fff;
            box-shadow: 0 15px 35px rgba(5, 150, 105, 0.3);
        }

        .green-card:hover {
            box-shadow: 0 25px 50px rgba(5, 150, 105, 0.4);
        }

        /* Light Card (Copy Trade) */
        .light-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #cbd5e1 100%);
            color: #0f172a;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .light-card:hover {
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }

        .promo-content {
            flex: 1;
            padding-right: 20px;
        }

        .promo-header {
            margin-bottom: 20px;
        }

        .promo-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .app-ratings {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .rating i {
            font-size: 1.1rem;
        }

        .promo-card p {
            font-size: 1rem;
            line-height: 1.5;
            margin-bottom: 25px;
            opacity: 0.9;
        }

        .bonus-amount {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 20px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            display: inline-block;
        }

        .promo-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.15);
            color: inherit;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .light-card .promo-btn {
            background: rgba(59, 130, 246, 0.1);
            color: #1d4ed8;
            border-color: rgba(59, 130, 246, 0.2);
        }

        .promo-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateX(4px);
            text-decoration: none;
            color: inherit;
        }

        .light-card .promo-btn:hover {
            background: rgba(59, 130, 246, 0.15);
            color: #1d4ed8;
            text-decoration: none;
        }

        .promo-visual {
            flex-shrink: 0;
            width: 120px;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Phone Mockup */
        .phone-mockup {
            width: 80px;
            height: 120px;
            background: linear-gradient(145deg, #2d3748, #4a5568);
            border-radius: 12px;
            padding: 8px;
            position: relative;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .phone-screen {
            width: 100%;
            height: 100%;
            background: linear-gradient(145deg, #667eea, #764ba2);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .app-icon {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .app-name {
            font-size: 0.7rem;
            font-weight: 600;
            color: #fff;
        }

        /* Bonus Visual */
        .bonus-visual {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .gift-box {
            font-size: 3rem;
            animation: bounce 2s infinite;
        }

        .bonus-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Trophy Visual */
        .trophy-visual {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .trophy {
            font-size: 3rem;
            animation: glow 2s ease-in-out infinite alternate;
        }

        .prize-text {
            font-size: 1.2rem;
            font-weight: 700;
            color: #fbbf24;
        }

        /* Copy Visual */
        .copy-visual {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            font-size: 2.5rem;
            background: rgba(59, 130, 246, 0.2);
            padding: 15px;
            border-radius: 50%;
        }

        .copy-arrows {
            font-size: 1.5rem;
            animation: pulse 1.5s infinite;
        }

        /* Animations */
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        @keyframes glow {
            from { filter: drop-shadow(0 0 10px #fbbf24); }
            to { filter: drop-shadow(0 0 20px #f59e0b); }
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        /* ===== EDUCATION SECTION ===== */
        .education {
            padding: 80px 0;
            background: #f4f5f7;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title {
            color: #0d1b4c;
        }

        .section-description {
            font-size: 1.1rem;
            color: #666;
            max-width: 600px;
            margin: 20px auto 0;
        }

        .education-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 40px;
        }

        .education-card {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .education-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .card-image {
            height: 200px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #fff;
        }

        .card-content {
            padding: 30px;
        }

        .card-content h3 {
            font-size: 1.4rem;
            font-weight: 600;
            color: #0d1b4c;
            margin-bottom: 15px;
        }

        .card-content p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .card-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .card-btn:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.4);
        }

        /* ===== CONTACT/CTA SECTION ===== */
        .contact-cta {
            padding: 80px 0;
            background: linear-gradient(135deg, #0d1b4c 0%, #1e40af 100%);
        }

        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .contact-info h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 20px;
        }

        .contact-info p {
            font-size: 1.1rem;
            color: #f4f5f7;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .contact-features {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #f4f5f7;
        }

        .feature i {
            color: #22c55e;
            font-size: 1.2rem;
        }

        .contact-form {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2563eb;
            background: rgba(255, 255, 255, 0.15);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .submit-btn {
            width: 100%;
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.4);
        }

        /* ===== LIVE SUPPORT BUTTON ===== */
        .live-support {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }

        .support-btn {
            background: #ff3f34;
            color: #fff;
            border: none;
            padding: 15px 20px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 5px 20px rgba(255, 63, 52, 0.4);
            transition: all 0.3s ease;
        }

        .support-btn:hover {
            background: #dc2626;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 63, 52, 0.6);
        }

        .support-btn i {
            font-size: 1.2rem;
            animation: pulse-support 2s infinite;
        }

        @keyframes pulse-support {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.8rem;
            }
            
            .hero-description {
                font-size: 1.05rem;
            }
            
            .btn-cta {
                padding: 16px 36px;
                font-size: 1rem;
            }
            
            .ticker-header h2 {
                font-size: 1.4rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .promo-card {
                flex-direction: column;
                text-align: center;
                padding: 30px;
            }
            
            .promo-content {
                padding-right: 0;
                margin-bottom: 20px;
            }
            
            .contact-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .services-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .indicators-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .education-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 2.2rem;
            }
            
            .container {
                padding: 0 15px;
            }
            
            .coin-item {
                min-width: 180px;
                padding: 10px 12px;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            .indicators-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Include header -->
    <?php include 'includes/header.php'; ?>

    <!-- Hero Slider -->
    <section class="hero-slider" id="hero">
        <div class="slider-container">
            <!-- Slide 1 -->
            <div class="slide active">
                <div class="slide-background"></div>
                <div class="slide-content">
                    <div class="container">
                        <p class="hero-subtitle"><?php echo getCurrentLang() == 'tr' ? 'Binlerce yatırımcı bize güveniyor' : 'Thousands of investors trust us'; ?></p>
                        <h1 class="hero-title">
                            <?php echo getCurrentLang() == 'tr' ? 
                                'Türkiye\'nin en güvenilir <span class="highlight">kripto borsası</span> olmamız tesadüf değil' : 
                                'Being Turkey\'s most trusted <span class="highlight">crypto exchange</span> is no coincidence'; ?>
                        </h1>
                        <p class="hero-description">
                            <?php echo getCurrentLang() == 'tr' ? 
                                'Yatırımcılara rahatça kâr edebilecekleri seçkin bir yatırım ortamı sağlıyoruz.' : 
                                'We provide an exclusive investment environment where investors can easily profit.'; ?>
                        </p>
                        <a href="register.php" class="btn-cta">
                            <?php echo getCurrentLang() == 'tr' ? '1.000 TL\'ye varan %100 bonus alın' : 'Get up to 1,000 TL 100% bonus'; ?>
                        </a>
                        <p class="hero-disclaimer">
                            *<?php echo getCurrentLang() == 'tr' ? 'Sınırlı süreli teklif' : 'Limited time offer'; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Slide 2 -->
            <div class="slide">
                <div class="slide-background slide-bg-2"></div>
                <div class="slide-content">
                    <div class="container">
                        <p class="hero-subtitle"><?php echo getCurrentLang() == 'tr' ? 'Kripto pazarında lider pozisyon' : 'Leading position in crypto market'; ?></p>
                        <h1 class="hero-title">
                            <?php echo getCurrentLang() == 'tr' ? 
                                'Bitcoin ve Altcoin Ticaretinde <span class="highlight">Lider Platform</span>' : 
                                'Leading Platform in <span class="highlight">Bitcoin & Altcoin Trading</span>'; ?>
                        </h1>
                        <p class="hero-description">
                            <?php echo getCurrentLang() == 'tr' ? 
                                'Türkiye\'de 100.000\'den fazla yatırımcının tercih ettiği güvenilir platform. Düşük komisyonlar ve hızlı işlem garantisi.' : 
                                'Trusted platform preferred by over 100,000 investors in Turkey. Low commissions and fast transaction guarantee.'; ?>
                        </p>
                        <a href="register.php" class="btn-cta">
                            <?php echo getCurrentLang() == 'tr' ? 'Canlı Hesap Aç' : 'Open Live Account'; ?>
                        </a>
                        <p class="hero-disclaimer">
                            *<?php echo getCurrentLang() == 'tr' ? 'Risk uyarısı geçerlidir' : 'Risk warning applies'; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Slide 3 -->
            <div class="slide">
                <div class="slide-background slide-bg-3"></div>
                <div class="slide-content">
                    <div class="container">
                        <p class="hero-subtitle"><?php echo getCurrentLang() == 'tr' ? 'Profesyonel analist desteği' : 'Professional analyst support'; ?></p>
                        <h1 class="hero-title">
                            <?php echo getCurrentLang() == 'tr' ? 
                                'Uzman Analist Desteği ile <span class="highlight">Kazanmaya Başlayın</span>' : 
                                'Start Winning with <span class="highlight">Expert Analyst Support</span>'; ?>
                        </h1>
                        <p class="hero-description">
                            <?php echo getCurrentLang() == 'tr' ? 
                                'Günlük kripto analizleri, webinarlar ve eğitim materyalleri ile yatırım bilginizi artırın. Başarılı trader\'ların sırlarını öğrenin.' : 
                                'Increase your investment knowledge with daily crypto analysis, webinars and training materials. Learn the secrets of successful traders.'; ?>
                        </p>
                        <a href="#" class="btn-cta">
                            <?php echo getCurrentLang() == 'tr' ? 'Ücretsiz Eğitime Başla' : 'Start Free Training'; ?>
                        </a>
                        <p class="hero-disclaimer">
                            *<?php echo getCurrentLang() == 'tr' ? 'Eğitim materyalleri ücretsizdir' : 'Training materials are free'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Auto-play Progress Bar -->
        <div class="slider-progress" id="sliderProgress"></div>
    </section>

    <!-- US Stocks Ticker -->
    <section class="coin-ticker" id="coin-ticker">
        <div class="ticker-header">
            <h2><?php echo getCurrentLang() == 'tr' ? 'Amerika\'nın en büyük şirketlerine yatırım yapın' : 'Invest in America\'s largest companies'; ?></h2>
        </div>
        <div class="ticker-container">
            <div class="ticker-track">
                <!-- First set of US Stocks -->
                <div class="coin-item">
                    <div class="coin-flag logo-aapl"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">AAPL</div>
                        <div class="coin-name">Apple Inc.</div>
                    </div>
                </div>
                
                <div class="coin-item">
                    <div class="coin-flag logo-msft"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">MSFT</div>
                        <div class="coin-name">Microsoft</div>
                    </div>
                </div>
                
                <div class="coin-item">
                    <div class="coin-flag logo-googl"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">GOOGL</div>
                        <div class="coin-name">Alphabet Inc.</div>
                    </div>
                </div>
                
                <div class="coin-item">
                    <div class="coin-flag logo-amzn"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">AMZN</div>
                        <div class="coin-name">Amazon</div>
                    </div>
                </div>
                
                <div class="coin-item">
                    <div class="coin-flag logo-tsla"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">TSLA</div>
                        <div class="coin-name">Tesla Inc.</div>
                    </div>
                </div>
                
                <div class="coin-item">
                    <div class="coin-flag logo-meta"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">META</div>
                        <div class="coin-name">Meta Platforms</div>
                    </div>
                </div>
                
                <div class="coin-item">
                    <div class="coin-flag logo-nflx"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">NFLX</div>
                        <div class="coin-name">Netflix</div>
                    </div>
                </div>
                
                <div class="coin-item">
                    <div class="coin-flag logo-v"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">V</div>
                        <div class="coin-name">Visa Inc.</div>
                    </div>
                </div>
                
                <div class="coin-item">
                    <div class="coin-flag logo-ko"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">KO</div>
                        <div class="coin-name">Coca-Cola</div>
                    </div>
                </div>
                
                <div class="coin-item">
                    <div class="coin-flag logo-jpm"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">JPM</div>
                        <div class="coin-name">JPMorgan Chase</div>
                    </div>
                </div>
                
                <!-- Duplicate set for seamless loop -->
                <div class="coin-item">
                    <div class="coin-flag logo-aapl"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">AAPL</div>
                        <div class="coin-name">Apple Inc.</div>
                    </div>
                </div>
                
                <div class="coin-item">
                    <div class="coin-flag logo-msft"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">MSFT</div>
                        <div class="coin-name">Microsoft</div>
                    </div>
                </div>
                
                <div class="coin-item">
                    <div class="coin-flag logo-googl"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">GOOGL</div>
                        <div class="coin-name">Alphabet Inc.</div>
                    </div>
                </div>
                
                <div class="coin-item">
                    <div class="coin-flag logo-amzn"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">AMZN</div>
                        <div class="coin-name">Amazon</div>
                    </div>
                </div>
                
                <div class="coin-item">
                    <div class="coin-flag logo-tsla"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">TSLA</div>
                        <div class="coin-name">Tesla Inc.</div>
                    </div>
                </div>
                
                <div class="coin-item">
                    <div class="coin-flag logo-meta"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">META</div>
                        <div class="coin-name">Meta Platforms</div>
                    </div>
                </div>
                
                <div class="coin-item">
                    <div class="coin-flag logo-nflx"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">NFLX</div>
                        <div class="coin-name">Netflix</div>
                    </div>
                </div>
                
                <div class="coin-item">
                    <div class="coin-flag logo-v"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">V</div>
                        <div class="coin-name">Visa Inc.</div>
                    </div>
                </div>
                
                <div class="coin-item">
                    <div class="coin-flag logo-ko"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">KO</div>
                        <div class="coin-name">Coca-Cola</div>
                    </div>
                </div>
                
                <div class="coin-item">
                    <div class="coin-flag logo-jpm"></div>
                    <div class="coin-info">
                        <div class="coin-symbol">JPM</div>
                        <div class="coin-name">JPMorgan Chase</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Service Cards -->
    <section class="services" id="services">
        <div class="container">
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3><?php echo getCurrentLang() == 'tr' ? 'Güvenli ve Şifreli' : 'Secure and Encrypted'; ?></h3>
                    <p><?php echo getCurrentLang() == 'tr' ? 'SSL şifreleme ve çoklu güvenlik katmanları ile paranız her zaman güvende.' : 'Your money is always safe with SSL encryption and multiple security layers.'; ?></p>
                </div>

                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3><?php echo getCurrentLang() == 'tr' ? 'Gelişmiş Ticaret Araçları' : 'Advanced Trading Tools'; ?></h3>
                    <p><?php echo getCurrentLang() == 'tr' ? 'Profesyonel grafik araçları ve teknik analiz göstergeleri ile ticaret yapın.' : 'Trade with professional charting tools and technical analysis indicators.'; ?></p>
                </div>

                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3><?php echo getCurrentLang() == 'tr' ? '7/24 Türkçe Destek' : '24/7 Turkish Support'; ?></h3>
                    <p><?php echo getCurrentLang() == 'tr' ? 'Uzman ekibimiz 7 gün 24 saat Türkçe destek hizmeti sunmaktadır.' : 'Our expert team provides 24/7 Turkish support service.'; ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Market Indicators -->
    <section class="market-indicators" id="indicators">
        <div class="container">
            <h2 class="section-title"><?php echo getCurrentLang() == 'tr' ? 'Canlı Piyasa Göstergeleri' : 'Live Market Indicators'; ?></h2>
            <div class="indicators-grid">
                <?php foreach (array_slice($markets, 0, 6) as $market): ?>
                <div class="indicator-item">
                    <span class="pair"><?php echo $market['symbol']; ?></span>
                    <span class="price"><?php echo formatPrice($market['price']); ?> TL</span>
                    <span class="change <?php echo $market['change_24h'] >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo ($market['change_24h'] >= 0 ? '+' : '') . number_format($market['change_24h'], 2); ?>%
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Promo Cards Section -->
    <section class="promo-cards" id="promo-cards">
        <div class="container">
            <h2 class="section-title animate-on-scroll">
                <?php echo getCurrentLang() == 'tr' ? 'Yatırımcılarımızın' : 'Take a look at our investors\''; ?>
                <span class="highlight"><?php echo getCurrentLang() == 'tr' ? 'favorilerine' : 'favorites'; ?></span> 
                <?php echo getCurrentLang() == 'tr' ? 'göz atın' : ''; ?>
            </h2>
            <p class="section-subtitle animate-on-scroll">
                <?php echo getCurrentLang() == 'tr' ? 
                    'Yatırımda herkesin ilk tercihi olmamızı sağlayan bazı vazgeçilmez ürünlerimiz hakkında bilgi edinin.' : 
                    'Learn about some of our indispensable products that make us everyone\'s first choice in investment.'; ?>
            </p>
            
            <div class="promo-grid">
                <!-- App Card -->
                <div class="promo-card dark-card">
                    <div class="promo-content">
                        <div class="promo-header">
                            <h3><?php echo getCurrentLang() == 'tr' ? 'GlobalBorsa uygulaması' : 'GlobalBorsa app'; ?></h3>
                            <div class="app-ratings">
                                <div class="rating">
                                    <i class="fab fa-apple"></i>
                                    <span>★★★★★</span>
                                </div>
                                <div class="rating">
                                    <i class="fab fa-google-play"></i>
                                    <span>★★★★★</span>
                                </div>
                            </div>
                        </div>
                        <p><?php echo getCurrentLang() == 'tr' ? 'Yüksek puanlı, ödüllü GlobalBorsa uygulamasıyla hizmetlerine eksiksiz erişin.' : 'Get complete access to services with the highly-rated, award-winning GlobalBorsa app.'; ?></p>
                        <a href="#" class="promo-btn">
                            <?php echo getCurrentLang() == 'tr' ? 'Uygulamayı Edinin' : 'Get the App'; ?> 
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="promo-visual">
                        <div class="phone-mockup">
                            <div class="phone-screen">
                                <div class="app-icon">📱</div>
                                <div class="app-name">GlobalBorsa</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bonus Card -->
                <div class="promo-card blue-card">
                    <div class="promo-content">
                        <h3><?php echo getCurrentLang() == 'tr' ? '%100 bonus' : '100% bonus'; ?></h3>
                        <p><?php echo getCurrentLang() == 'tr' ? 'Daha fazla yatırım, daha az risk ve daha çok getiri için fonlarınızı kullanın.' : 'Use your funds for more investment, less risk and more returns.'; ?></p>
                        <div class="bonus-amount">
                            <?php echo getCurrentLang() == 'tr' ? '1.000 TL\'ye varan %100 bonus alın' : 'Get up to 1,000 TL 100% bonus'; ?>
                        </div>
                        <a href="register.php" class="promo-btn">
                            <?php echo getCurrentLang() == 'tr' ? 'Bonusunuzu alın' : 'Get your bonus'; ?> 
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="promo-visual">
                        <div class="bonus-visual">
                            <div class="gift-box">🎁</div>
                            <div class="bonus-text">%100</div>
                        </div>
                    </div>
                </div>

                <!-- Competition Card -->
                <div class="promo-card green-card">
                    <div class="promo-content">
                        <h3><?php echo getCurrentLang() == 'tr' ? 'GlobalBorsa yarışmaları' : 'GlobalBorsa competitions'; ?></h3>
                        <p><?php echo getCurrentLang() == 'tr' ? 'Yatırımlarınızla zirveye ilerleyin ve toplam 50.000 TL çekilebilir nakit ödülden payınızı alın.' : 'Advance to the top with your investments and get your share of 50,000 TL total withdrawable cash prizes.'; ?></p>
                        <a href="#" class="promo-btn">
                            <?php echo getCurrentLang() == 'tr' ? 'Hemen katılın' : 'Join now'; ?> 
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="promo-visual">
                        <div class="trophy-visual">
                            <div class="trophy">🏆</div>
                            <div class="prize-text">50.000 TL</div>
                        </div>
                    </div>
                </div>

                <!-- Copy Trade Card -->
                <div class="promo-card light-card">
                    <div class="promo-content">
                        <h3><?php echo getCurrentLang() == 'tr' ? 'GlobalBorsa copy trade' : 'GlobalBorsa copy trade'; ?></h3>
                        <p><?php echo getCurrentLang() == 'tr' ? 'Kazançlı yatırım stratejilerini kopyalayan 1.000\'den fazla yatırımcıya katılın ya da işlemlerinizi paylaşıp komisyon kazanın.' : 'Join over 1,000 investors copying profitable investment strategies or share your trades and earn commissions.'; ?></p>
                        <a href="#" class="promo-btn">
                            <?php echo getCurrentLang() == 'tr' ? 'Copy trade\'e başlayın' : 'Start copy trading'; ?> 
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="promo-visual">
                        <div class="copy-visual">
                            <div class="user-avatar">👤</div>
                            <div class="copy-arrows">📈</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Education Section -->
    <section class="education" id="egitim">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><?php echo getCurrentLang() == 'tr' ? 'Eğitim ve Analiz Merkezi' : 'Education and Analysis Center'; ?></h2>
                <p class="section-description"><?php echo getCurrentLang() == 'tr' ? 'Başarılı yatırımcı olmak için gereken tüm bilgileri uzman ekibimizden öğrenin.' : 'Learn everything you need to become a successful investor from our expert team.'; ?></p>
            </div>

            <div class="education-grid">
                <div class="education-card">
                    <div class="card-image">
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo getCurrentLang() == 'tr' ? 'Canlı Webinarlar' : 'Live Webinars'; ?></h3>
                        <p><?php echo getCurrentLang() == 'tr' ? 'Uzman analistlerden canlı kripto piyasa analizleri ve ticaret stratejileri öğrenin.' : 'Learn live crypto market analysis and trading strategies from expert analysts.'; ?></p>
                        <button class="card-btn"><?php echo getCurrentLang() == 'tr' ? 'Katıl' : 'Join'; ?></button>
                    </div>
                </div>

                <div class="education-card">
                    <div class="card-image">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo getCurrentLang() == 'tr' ? 'Kripto Sözlüğü' : 'Crypto Dictionary'; ?></h3>
                        <p><?php echo getCurrentLang() == 'tr' ? 'Kripto para ticaretinde kullanılan tüm terimleri detaylı açıklamalarıyla öğrenin.' : 'Learn all terms used in cryptocurrency trading with detailed explanations.'; ?></p>
                        <button class="card-btn"><?php echo getCurrentLang() == 'tr' ? 'Keşfet' : 'Explore'; ?></button>
                    </div>
                </div>

                <div class="education-card">
                    <div class="card-image">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="card-content">
                        <h3><?php echo getCurrentLang() == 'tr' ? 'Temel Teknik Analiz' : 'Basic Technical Analysis'; ?></h3>
                        <p><?php echo getCurrentLang() == 'tr' ? 'Grafik okuma, indikatörler ve ticaret sinyalleri hakkında temel bilgileri edinin.' : 'Get basic information about chart reading, indicators and trading signals.'; ?></p>
                        <button class="card-btn"><?php echo getCurrentLang() == 'tr' ? 'Başla' : 'Start'; ?></button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact/CTA Section -->
    <section class="contact-cta" id="iletisim">
        <div class="container">
            <div class="contact-content">
                <div class="contact-info">
                    <h2><?php echo getCurrentLang() == 'tr' ? 'Sizi Arayalım' : 'Let Us Call You'; ?></h2>
                    <p><?php echo getCurrentLang() == 'tr' ? 'Yatırım danışmanlarımız size en uygun hesap türünü ve yatırım stratejisini belirlemek için iletişime geçsin.' : 'Let our investment advisors contact you to determine the most suitable account type and investment strategy for you.'; ?></p>
                    <div class="contact-features">
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo getCurrentLang() == 'tr' ? 'Ücretsiz danışmanlık' : 'Free consultation'; ?></span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo getCurrentLang() == 'tr' ? 'Kişiselleştirilmiş strateji' : 'Personalized strategy'; ?></span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo getCurrentLang() == 'tr' ? 'Risk yönetimi' : 'Risk management'; ?></span>
                        </div>
                    </div>
                </div>

                <div class="contact-form">
                    <form id="callbackForm">
                        <div class="form-group">
                            <input type="text" id="name" name="name" placeholder="<?php echo getCurrentLang() == 'tr' ? 'Adınız Soyadınız' : 'Your Name Surname'; ?>" required>
                        </div>
                        <div class="form-group">
                            <input type="tel" id="phone" name="phone" placeholder="<?php echo getCurrentLang() == 'tr' ? 'Telefon Numaranız' : 'Your Phone Number'; ?>" required>
                        </div>
                        <div class="form-group">
                            <input type="email" id="email" name="email" placeholder="<?php echo getCurrentLang() == 'tr' ? 'E-posta Adresiniz' : 'Your Email Address'; ?>" required>
                        </div>
                        <div class="form-group">
                            <select id="experience" name="experience" required>
                                <option value=""><?php echo getCurrentLang() == 'tr' ? 'Yatırım Deneyiminiz' : 'Your Investment Experience'; ?></option>
                                <option value="beginner"><?php echo getCurrentLang() == 'tr' ? 'Yeni başlıyorum' : 'Just starting'; ?></option>
                                <option value="intermediate"><?php echo getCurrentLang() == 'tr' ? 'Orta seviye' : 'Intermediate'; ?></option>
                                <option value="advanced"><?php echo getCurrentLang() == 'tr' ? 'İleri seviye' : 'Advanced'; ?></option>
                            </select>
                        </div>
                        <button type="submit" class="submit-btn"><?php echo getCurrentLang() == 'tr' ? 'Beni Arayın' : 'Call Me'; ?></button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Include existing footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Live Support Button -->
    <div class="live-support" id="liveSupport">
        <button class="support-btn">
            <i class="fas fa-comments"></i>
            <span><?php echo getCurrentLang() == 'tr' ? 'Canlı Destek' : 'Live Support'; ?></span>
        </button>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="assets/js/landing-new.js"></script>
    
    <!-- Inline JavaScript for Slider -->
    <script>
        // Hero Slider - Inline to ensure it works
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing slider...');
            
            const slides = document.querySelectorAll('.slide');
            const progressBar = document.getElementById('sliderProgress');
            let currentSlide = 0;
            let slideInterval;
            
            console.log('Found slides:', slides.length);
            
            if (slides.length === 0) {
                console.log('No slides found!');
                return;
            }
            
            // Function to show specific slide
            function showSlide(index) {
                console.log('Showing slide:', index);
                
                // Remove active class from all slides
                slides.forEach((slide, i) => {
                    slide.classList.remove('active');
                    console.log('Removed active from slide', i);
                });
                
                // Add active class to current slide
                slides[index].classList.add('active');
                console.log('Added active to slide', index);
                
                currentSlide = index;
                
                // Update progress bar
                if (progressBar) {
                    progressBar.style.width = '0%';
                    setTimeout(() => {
                        progressBar.style.width = '100%';
                    }, 100);
                }
            }
            
            // Auto-slide functionality
            function startAutoSlide() {
                slideInterval = setInterval(() => {
                    console.log('Auto advancing from slide', currentSlide);
                    currentSlide = (currentSlide + 1) % slides.length;
                    showSlide(currentSlide);
                }, 5000); // 5 seconds
            }
            
            // Initialize progress bar
            if (progressBar) {
                progressBar.style.width = '0%';
                progressBar.style.transition = 'width 5s linear';
                setTimeout(() => {
                    progressBar.style.width = '100%';
                }, 100);
            }
            
            // Start auto-slide
            startAutoSlide();
            console.log('Auto-slide started');
            
            // Manual controls for testing
            window.nextSlide = function() {
                clearInterval(slideInterval);
                currentSlide = (currentSlide + 1) % slides.length;
                showSlide(currentSlide);
                setTimeout(startAutoSlide, 1000);
            };
            
            window.prevSlide = function() {
                clearInterval(slideInterval);
                currentSlide = currentSlide === 0 ? slides.length - 1 : currentSlide - 1;
                showSlide(currentSlide);
                setTimeout(startAutoSlide, 1000);
            };
            
            // Pause on hover
            const heroSlider = document.querySelector('.hero-slider');
            if (heroSlider) {
                heroSlider.addEventListener('mouseenter', () => {
                    clearInterval(slideInterval);
                    console.log('Paused on hover');
                });
                
                heroSlider.addEventListener('mouseleave', () => {
                    startAutoSlide();
                    console.log('Resumed on leave');
                });
            }
            
            // Keyboard controls
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') {
                    window.prevSlide();
                } else if (e.key === 'ArrowRight') {
                    window.nextSlide();
                }
            });
            
            console.log('Slider initialization complete');
        });
        
        // Test function - you can call this in browser console
        window.testSlider = function() {
            console.log('Testing slider...');
            const slides = document.querySelectorAll('.slide');
            console.log('Slides found:', slides.length);
            slides.forEach((slide, i) => {
                console.log('Slide', i, 'classes:', slide.className);
            });
        };
    </script>
</body>
</html>
