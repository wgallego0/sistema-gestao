<?php
// Autoloader for project models
spl_autoload_register(function ($class) {
    // Base directory for models
    $base_dir = __DIR__ . '/../models/';
    
    // Convert namespace to file path
    $file = $base_dir . str_replace('\\', '/', $class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    return false;
});