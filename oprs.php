<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/controllers/OPRController.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Instanciar controlador
$controller = new OPRController();

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
    case 'print':
        $controller->imprimir($id);
        break;
    case 'pendentes':
        $controller->pendentes();
        break;
    default:
        if (isset($_GET['pendentes']) && $_GET['pendentes']) {
            $controller->pendentes();
        } else {
            $controller->index();
        }
}