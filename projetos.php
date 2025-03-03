<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/controllers/ProjetosController.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Instanciar controlador
$controller = new ProjetosController();

// Determinar ação baseada nos parâmetros
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : 'index';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

// Executar ação
switch ($action) {
    case 'view':
        $controller->view($id);
        break;
    case 'create':
        $controller->create();
        break;
    case 'edit':
        $controller->edit($id);
        break;
    case 'relatorio':
        $controller->relatorio($id);
        break;
    default:
        $controller->index();
}