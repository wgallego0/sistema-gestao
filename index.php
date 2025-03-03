<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/controllers/DashboardController.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Instanciar controlador
$controller = new DashboardController();

// Carregar dashboard
$controller->index();