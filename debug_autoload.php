<?php
// Configurações de erro
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Diretórios e caminhos
echo "Diretórios e caminhos:\n<br>";
echo "Diretório atual: " . __DIR__ . "\n<br>";
echo "Caminho do script: " . $_SERVER['SCRIPT_FILENAME'] . "\n<br>";

// Tentar carregar classes manualmente
echo "\n<br>Tentando carregar classes manualmente:\n<br>";

// Incluir configurações e modelos
require_once __DIR__ . '/config.php';

// Adicionar diretórios de modelos
$modelDirs = [
    __DIR__ . '/models/',
    __DIR__ . '/controllers/',
    'C:/wamp64/www/sistema-gestao/models/',
    'C:/wamp64/www/sistema-gestao/controllers/'
];

// Função para tentar carregar classe
function debugLoadClass($className) {
    global $modelDirs;
    
    echo "Tentando carregar classe: $className\n<br>";
    
    // Remover namespace
    $className = str_replace(['Models\\', 'Controllers\\', '\\'], ['', '', '/'], $className);
    
    $possibleFiles = [
        $className . '.php',
        strtolower($className) . '.php',
        $className . '.class.php'
    ];
    
    foreach ($modelDirs as $dir) {
        foreach ($possibleFiles as $file) {
            $path = $dir . $file;
            echo "Verificando: $path\n<br>";
            
            if (file_exists($path)) {
                echo "Arquivo encontrado: $path\n<br>";
                
                // Tentar carregar o arquivo
                try {
                    require_once $path;
                    
                    // Verificar se a classe existe
                    if (class_exists($className, false)) {
                        echo "Classe $className carregada com sucesso!\n<br>";
                        return true;
                    }
                } catch (Exception $e) {
                    echo "Erro ao carregar $path: " . $e->getMessage() . "\n<br>";
                }
            }
        }
    }
    
    echo "ERRO: Não foi possível carregar $className\n<br>";
    return false;
}

// Registrar autoloader personalizado
spl_autoload_register('debugLoadClass');

// Testar classes específicas
$classesToTest = [
    'Projeto', 
    'Liderado', 
    'Models\Projeto', 
    'Models\Liderado',
    'ProjetosController',
    'LideradosController'
];

echo "\n<br>Testando carregamento de classes:\n<br>";
foreach ($classesToTest as $className) {
    echo "\n<br>Verificando $className:\n<br>";
    
    // Tentar carregar a classe
    try {
        if (class_exists($className)) {
            echo "Classe $className já existe!\n<br>";
        } else {
            $loaded = debugLoadClass($className);
            
            if (class_exists($className)) {
                echo "Sucesso: Classe $className carregada!\n<br>";
            } else {
                echo "FALHA: Não foi possível carregar $className\n<br>";
            }
        }
    } catch (Exception $e) {
        echo "Exceção ao carregar $className: " . $e->getMessage() . "\n<br>";
    }
}

// Informações adicionais de PHP
echo "\n<br>Configurações do PHP:\n<br>";
echo "Versão do PHP: " . phpversion() . "\n<br>";
echo "Include Path: " . get_include_path() . "\n<br>";

// Listar caminhos de inclusão
echo "\n<br>Caminhos de inclusão:\n<br>";
$includePaths = explode(PATH_SEPARATOR, get_include_path());
foreach ($includePaths as $path) {
    echo "$path\n<br>";
}

// Listar arquivos carregados
echo "\n<br>Arquivos já carregados:\n<br>";
$loadedFiles = get_included_files();
foreach ($loadedFiles as $file) {
    echo "$file\n<br>";
}