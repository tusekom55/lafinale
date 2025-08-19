<?php
// Homepage Content Management Functions

function getHomepageContentBySection($db, $section, $language = 'tr') {
    $query = "SELECT content_key, content_value, content_type 
              FROM homepage_content 
              WHERE section_name = ? AND language = ? AND is_active = 1 
              ORDER BY display_order, content_key";
    $stmt = $db->prepare($query);
    $stmt->execute([$section, $language]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $content = [];
    foreach ($results as $row) {
        $content[$row['content_key']] = $row['content_value'];
    }
    
    return $content;
}

function getContentValue($content, $key, $default = '') {
    return isset($content[$key]) ? $content[$key] : $default;
}

function getHomepageImage($db, $image_key) {
    $query = "SELECT image_path FROM homepage_images WHERE image_key = ? AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$image_key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['image_path'] : '';
}

// Fallback function to get content with language fallback
function getContentWithFallback($db, $section, $key, $language = 'tr', $default = '') {
    // First try to get content in requested language
    $query = "SELECT content_value FROM homepage_content 
              WHERE section_name = ? AND content_key = ? AND language = ? AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$section, $key, $language]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        return $result['content_value'];
    }
    
    // If not found, try Turkish as fallback
    if ($language !== 'tr') {
        $stmt->execute([$section, $key, 'tr']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            return $result['content_value'];
        }
    }
    
    return $default;
}

// Initialize homepage content for a language
function initializeHomepageContent($db, $language = 'tr') {
    static $content_cache = [];
    
    if (!isset($content_cache[$language])) {
        $sections = ['hero', 'features', 'markets_ticker', 'education', 'cta'];
        $content_cache[$language] = [];
        
        foreach ($sections as $section) {
            $content_cache[$language][$section] = getHomepageContentBySection($db, $section, $language);
        }
    }
    
    return $content_cache[$language];
}

// Get all homepage content in a structured way
function getAllHomepageContent($db, $language = 'tr') {
    return initializeHomepageContent($db, $language);
}

// Content output helpers
function echoContent($content, $section, $key, $default = '', $escape = true) {
    $value = getContentValue($content[$section] ?? [], $key, $default);
    if ($escape) {
        echo htmlspecialchars($value);
    } else {
        echo $value; // For HTML content
    }
}

function getContent($content, $section, $key, $default = '') {
    return getContentValue($content[$section] ?? [], $key, $default);
}

// Check if content management system is available
function isCMSEnabled($db) {
    try {
        $query = "SHOW TABLES LIKE 'homepage_content'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Get site settings with content management integration
function getSiteSettings($db) {
    $settings = [];
    
    // Get from settings table
    try {
        $query = "SELECT setting_key, setting_value FROM settings";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as $setting) {
            $settings[$setting['setting_key']] = $setting['setting_value'];
        }
    } catch (Exception $e) {
        // Settings table might not exist, use defaults
    }
    
    // Add default values
    $defaults = [
        'site_name' => 'GlobalBorsa',
        'site_description' => 'Kripto Para Alım Satım Platformu',
        'contact_email' => 'info@globalborsa.com',
        'company_address' => 'İstanbul, Türkiye'
    ];
    
    return array_merge($defaults, $settings);
}
