<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/controllers/AuthController.php';

// Se já estiver logado, redireciona para o dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Verificar se tem token
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $_SESSION[SESSION_PREFIX . 'error'] = 'Token inválido ou expirado';
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Processar formulário de redefinição se for um POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new AuthController();
    $controller->redefinirSenha();
    exit;
} else {
    // Exibir formulário de redefinição
    $controller = new AuthController();
    $controller->redefinirSenhaForm();
}