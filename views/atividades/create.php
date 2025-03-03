<?php 
// Add debugging code to verify file paths
echo "Current script path: " . __FILE__ . "\n";
echo "Current directory: " . __DIR__ . "\n";

// Full, absolute paths to the models
$modelPaths = [
    __DIR__ . '/../../models/Projeto.php',
    __DIR__ . '/../models/Projeto.php',
    __DIR__ . '/models/Projeto.php',
    'C:/wamp64/www/sistema-gestao/models/Projeto.php'
];

foreach ($modelPaths as $path) {
    echo "Checking path: $path\n";
    if (file_exists($path)) {
        echo "File exists: $path\n";
        require_once $path;
        break;
    } else {
        echo "File NOT found: $path\n";
    }
}

// Explicitly define the namespace if needed
use Models\Projeto;
?>
<!-- Rest of the sidebar content remains the same -->
<div class="sidebar">
    <!-- ... (previous sidebar content) ... -->
</div>