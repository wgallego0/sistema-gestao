<?php
// Autoloader para projeto
spl_autoload_register(function($className) {
    // Primeiro, tenta encontrar a classe sem namespace
    $baseClassName = basename(str_replace('\\', '/', $className));
    
    // Caminhos possíveis para buscar classes
    $paths = [
        __DIR__ . '/../models/',
        __DIR__ . '/../controllers/',
        __DIR__ . '/../'
    ];
    
    // Possíveis formatos de nome de arquivo
    $fileFormats = [
        '%s.php',
        '%s.class.php',
        strtolower('%s.php')
    ];
    
    foreach ($paths as $path) {
        foreach ($fileFormats as $format) {
            $file = $path . sprintf($format, $baseClassName);
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
    }
    
    // Se chegarmos aqui, a classe não foi encontrada
    return false;
});