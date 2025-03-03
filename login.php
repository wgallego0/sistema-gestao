<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/controllers/AuthController.php';

// Se já estiver logado, redireciona para o dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Processar formulário de login se for um POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new AuthController();
    $controller->login();
    exit;
} else {
    // Exibir formulário de login
    $controller = new AuthController();
    $controller->loginForm();
}