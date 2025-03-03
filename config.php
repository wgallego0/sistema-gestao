<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Configurações de conexão com o banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_gestao_equipe');

// Configurações de diretórios
define('ROOT_DIR', dirname(__FILE__));
define('BASE_URL', 'http://localhost/sistema-gestao'); // Altere conforme seu ambiente

// Configurações de sessão
session_start();
define('SESSION_PREFIX', 'sge_');
define('SESSION_LIFETIME', 86400); // 24 horas em segundos

// Configurações de timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de logs
define('LOG_ENABLED', true);
define('LOG_FILE', ROOT_DIR . '/logs/sistema.log');

// Configurações de segurança
define('SALT', 'sge_2025_secret_salt'); // Para hash de senhas
define('TOKEN_EXPIRATION', 3600); // 1 hora em segundos

// Add autoloader
require_once __DIR__ . '/config/autoload.php';



spl_autoload_register(function ($className) {
    // Mapear possíveis localizações de classes
    $baseDirectories = [
        __DIR__ . '/models/',
        __DIR__ . '/controllers/',
        __DIR__ . '/',
        'C:/wamp64/www/sistema-gestao/models/',
        'C:/wamp64/www/sistema-gestao/controllers/'
    ];

    // Remover namespace
    $className = str_replace(['Models\\', 'Controllers\\', '\\'], ['', '', '/'], $className);

    // Possíveis nomes de arquivo
    $fileNames = [
        $className . '.php',
        strtolower($className) . '.php',
        $className . '.class.php'
    ];

    // Tentar carregar a classe
    foreach ($baseDirectories as $directory) {
        foreach ($fileNames as $fileName) {
            $path = $directory . $fileName;
            
            if (file_exists($path)) {
                require_once $path;
                
                // Verificar se a classe foi carregada
                if (class_exists($className, false)) {
                    return true;
                }
            }
        }
    }

    // Registrar erro se a classe não for encontrada
    error_log("Classe não encontrada: $className");
    return false;
});


// Função para conectar com o banco de dados
function getConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            )
        );
        return $conn;
    } catch (PDOException $e) {
        logError('Erro de conexão com o banco de dados: ' . $e->getMessage());
        die('Erro de conexão com o banco de dados. Por favor, tente novamente mais tarde.');
    }
}

// Função para registrar erros
function logError($message) {
    if (LOG_ENABLED) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        
        if (!is_dir(dirname(LOG_FILE))) {
            mkdir(dirname(LOG_FILE), 0755, true);
        }
        
        file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
    }
}

// Função para verificar se o usuário está logado
function isLoggedIn() {
    return isset($_SESSION[SESSION_PREFIX . 'user_id']);
}

// Função para verificar permissões de usuário
function hasPermission($requiredType) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userType = $_SESSION[SESSION_PREFIX . 'user_type'];
    
    // Administrador tem acesso a tudo
    if ($userType === 'admin') {
        return true;
    }
    
    // Gestor tem acesso às funcionalidades de gestor e liderado
    if ($userType === 'gestor' && $requiredType === 'liderado') {
        return true;
    }
    
    // Verificação direta do tipo
    return $userType === $requiredType;
}

// Função para sanitizar input de usuário
function sanitizeInput($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitizeInput($value);
        }
    } else {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    return $input;
}

// Função para gerar resposta JSON
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Função para registrar atividade no log
function logActivity($table, $recordId, $action, $oldData = null, $newData = null) {
    if (!LOG_ENABLED) {
        return;
    }
    
    try {
        $conn = getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO logs (tabela, registro_id, acao, dados_antigos, dados_novos, ip, usuario)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $user = $_SESSION[SESSION_PREFIX . 'user_email'] ?? null;
        
        $stmt->execute([
            $table,
            $recordId,
            $action,
            $oldData ? json_encode($oldData) : null,
            $newData ? json_encode($newData) : null,
            $ip,
            $user
        ]);
    } catch (PDOException $e) {
        logError('Erro ao registrar log: ' . $e->getMessage());
    }
}

// Função para verificar CSRF token
function verifyCsrfToken() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION[SESSION_PREFIX . 'csrf_token']) {
        logError('Tentativa de CSRF detectada');
        jsonResponse(['error' => 'Erro de validação de token. Por favor, tente novamente.'], 403);
    }
}

// Gerar CSRF token se não existir
if (!isset($_SESSION[SESSION_PREFIX . 'csrf_token'])) {
    $_SESSION[SESSION_PREFIX . 'csrf_token'] = bin2hex(random_bytes(32));
}

// Função auxiliar para formatar data
function formatDate($date, $format = 'd/m/Y') {
    if (!$date) return '';
    $dt = new DateTime($date);
    return $dt->format($format);
}

// Função para obter configuração do sistema
function getConfig($key, $default = null) {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT valor FROM configuracoes WHERE chave = ?");
        $stmt->execute([$key]);
        
        $result = $stmt->fetch();
        
        if ($result) {
            return $result['valor'];
        }
        
        return $default;
    } catch (PDOException $e) {
        logError('Erro ao obter configuração: ' . $e->getMessage());
        return $default;
    }
}

// Função para definir configuração do sistema
function setConfig($key, $value, $description = null) {
    try {
        $conn = getConnection();
        
        // Verificar se a configuração já existe
        $stmt = $conn->prepare("SELECT id FROM configuracoes WHERE chave = ?");
        $stmt->execute([$key]);
        
        if ($stmt->fetch()) {
            // Atualizar configuração existente
            $stmt = $conn->prepare("UPDATE configuracoes SET valor = ? WHERE chave = ?");
            $stmt->execute([$value, $key]);
        } else {
            // Inserir nova configuração
            $stmt = $conn->prepare("INSERT INTO configuracoes (chave, valor, descricao) VALUES (?, ?, ?)");
            $stmt->execute([$key, $value, $description]);
        }
        
        return true;
    } catch (PDOException $e) {
        logError('Erro ao definir configuração: ' . $e->getMessage());
        return false;
    }
}

function custom_error_handler($errno, $errstr, $errfile, $errline) {
    error_log("Erro PHP [$errno]: $errstr em $errfile na linha $errline");
    return false; // Deixar o tratador de erros padrão lidar com o erro
}

set_error_handler('custom_error_handler');
