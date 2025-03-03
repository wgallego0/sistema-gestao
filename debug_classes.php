<?php
// Arquivo de depuração para verificar carregamento de classes

// Configurações de erro para máxima visibilidade
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Diretórios para buscar classes
$directories = [
    __DIR__ . '/models/',
    __DIR__ . '/controllers/',
    'C:/wamp64/www/sistema-gestao/models/',
    'C:/wamp64/www/sistema-gestao/controllers/'
];

// Função para tentar carregar a classe
function tryLoadClass($className) {
    global $directories;
    
    // Remover namespace
    $className = str_replace(['Models\\', '\\'], ['', '/'], $className);
    
    echo "Tentando carregar classe: $className\n<br>";
    
    foreach ($directories as $directory) {
        $possibleFiles = [
            $directory . $className . '.php',
            $directory . $className . '.class.php',
            $directory . strtolower($className) . '.php'
        ];
        
        foreach ($possibleFiles as $path) {
            echo "Verificando caminho: $path\n<br>";
            
            if (file_exists($path)) {
                echo "Arquivo encontrado: $path\n<br>";
                require_once $path;
                
                if (class_exists($className, false)) {
                    echo "Classe $className carregada com sucesso!\n<br>";
                    return true;
                }
            }
        }
    }
    
    echo "Classe $className NÃO encontrada em nenhum dos caminhos.\n<br>";
    return false;
}

// Registrar autoloader personalizado
spl_autoload_register('tryLoadClass');

// Testes de carregamento
echo "Testando carregamento de classes:\n<br>";

// Verificar classes específicas
$classesToTest = ['Projeto', 'Liderado', 'Models\Projeto', 'Models\Liderado'];

foreach ($classesToTest as $className) {
    echo "\n<br>Tentando carregar $className:\n<br>";
    
    // Tentar carregar a classe
    if (!class_exists($className)) {
        echo "AVISO: Classe $className não existe antes da tentativa de carregamento.\n<br>";
    }
    
    // Tentar carregar
    $loaded = tryLoadClass($className);
    
    // Verificar se a classe existe após a tentativa de carregamento
    if (class_exists($className)) {
        echo "Sucesso: Classe $className carregada!\n<br>";
    } else {
        echo "ERRO: Não foi possível carregar a classe $className\n<br>";
    }
}

// Informações adicionais de depuração
echo "\n<br>Informações de depuração:\n<br>";
echo "Diretório atual: " . __DIR__ . "\n<br>";
echo "Caminho do script: " . $_SERVER['SCRIPT_FILENAME'] . "\n<br>";
echo "Caminho do arquivo: " . __FILE__ . "\n<br>";