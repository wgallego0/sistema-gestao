<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Liderado.php';
require_once __DIR__ . '/../controllers/LideradosController.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Você precisa estar logado para acessar esta API'], 401);
}

// Instanciar controlador
$controller = new LideradosController();

// Determinar ação baseada no método HTTP e parâmetros
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : null;
$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : null);
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if ($id) {
            // Obter detalhes de um liderado
            $liderado = (new Liderado())->getById($id);
            
            if (!$liderado) {
                jsonResponse(['error' => 'Liderado não encontrado'], 404);
            }
            
            jsonResponse(['success' => true, 'data' => $liderado]);
        } else if ($action === 'all') {
            // Verificar permissão para listar todos os liderados
            if (!hasPermission('gestor') && !hasPermission('admin')) {
                jsonResponse(['error' => 'Você não tem permissão para acessar esta funcionalidade'], 403);
            }
            
            // Listar todos os liderados
            $liderados = (new Liderado())->getAll();
            jsonResponse(['success' => true, 'data' => $liderados]);
        } else if ($action === 'projetos' && $id) {
            // Listar projetos de um liderado
            $controller->projetos($id);
        } else if ($action === 'apontamentos' && $id) {
            // Listar apontamentos de um liderado
            $controller->apontamentos($id);
        } else if ($action === 'oprs' && $id) {
            // Listar OPRs de um liderado
            $controller->oprs($id);
        } else if ($action === 'estatisticas' && $id) {
            // Obter estatísticas de um liderado
            $controller->estatisticas($id);
        } else if ($action === 'buscar') {
            // Buscar liderados por termo
            $termo = sanitizeInput($_GET['q'] ?? '');
            
            if (strlen($termo) < 2) {
                jsonResponse(['success' => true, 'data' => []]);
            }
            
            $liderados = (new Liderado())->buscarPorTermo($termo);
            jsonResponse(['success' => true, 'data' => $liderados]);
        } else {
            jsonResponse(['error' => 'Ação não especificada'], 400);
        }
        break;
    
    case 'POST':
        // Verificar token CSRF
        verifyCsrfToken();
        
        if ($action === 'store') {
            // Verificar permissão
            if (!hasPermission('gestor') && !hasPermission('admin')) {
                jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
            }
            
            // Adicionar novo liderado
            $controller->store();
        } else if ($action === 'update' && $id) {
            // Verificar permissão
            if (!hasPermission('gestor') && !hasPermission('admin')) {
                jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
            }
            
            // Atualizar liderado existente
            $controller->update($id);
        } else if ($action === 'delete' && $id) {
            // Verificar permissão
            if (!hasPermission('admin')) {
                jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
            }
            
            // Excluir liderado
            $controller->delete($id);
        } else if ($action === 'associar_projeto') {
            // Verificar permissão
            if (!hasPermission('gestor') && !hasPermission('admin')) {
                jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
            }
            
            // Associar liderado a projeto
            $lideradoId = isset($_POST['liderado_id']) ? (int) $_POST['liderado_id'] : 0;
            $projetoId = isset($_POST['projeto_id']) ? (int) $_POST['projeto_id'] : 0;
            $percentual = isset($_POST['percentual']) ? (int) $_POST['percentual'] : 100;
            $dataInicio = sanitizeInput($_POST['data_inicio'] ?? date('Y-m-d'));
            
            if (!$lideradoId || !$projetoId) {
                jsonResponse(['error' => 'Liderado e Projeto são obrigatórios'], 400);
            }
            
            if ($percentual < 1 || $percentual > 100) {
                jsonResponse(['error' => 'Percentual de dedicação deve estar entre 1 e 100'], 400);
            }
            
            $result = (new Liderado())->associarProjeto($lideradoId, $projetoId, $percentual, $dataInicio);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Liderado associado ao projeto com sucesso']);
            } else {
                jsonResponse(['error' => 'Erro ao associar liderado ao projeto'], 500);
            }
        } else if ($action === 'remover_projeto') {
            // Verificar permissão
            if (!hasPermission('gestor') && !hasPermission('admin')) {
                jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
            }
            
            // Remover liderado de projeto
            $lideradoId = isset($_POST['liderado_id']) ? (int) $_POST['liderado_id'] : 0;
            $projetoId = isset($_POST['projeto_id']) ? (int) $_POST['projeto_id'] : 0;
            $dataFim = sanitizeInput($_POST['data_fim'] ?? date('Y-m-d'));
            
            if (!$lideradoId || !$projetoId) {
                jsonResponse(['error' => 'Liderado e Projeto são obrigatórios'], 400);
            }
            
            $result = (new Liderado())->removerDoProjeto($lideradoId, $projetoId, $dataFim);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Liderado removido do projeto com sucesso']);
            } else {
                jsonResponse(['error' => 'Erro ao remover liderado do projeto'], 500);
            }
        } else if ($action === 'set_cross_funcional') {
            // Verificar permissão
            if (!hasPermission('gestor') && !hasPermission('admin')) {
                jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
            }
            
            // Definir liderado como cross-funcional
            $lideradoId = isset($_POST['liderado_id']) ? (int) $_POST['liderado_id'] : 0;
            $isCross = isset($_POST['cross_funcional']) ? (bool) $_POST['cross_funcional'] : false;
            
            if (!$lideradoId) {
                jsonResponse(['error' => 'Liderado é obrigatório'], 400);
            }
            
            $result = (new Liderado())->setCrossFuncional($lideradoId, $isCross);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Status cross-funcional atualizado com sucesso']);
            } else {
                jsonResponse(['error' => 'Erro ao atualizar status cross-funcional'], 500);
            }
        } else {
            jsonResponse(['error' => 'Ação não especificada ou parâmetros inválidos'], 400);
        }
        break;
    
    default:
        jsonResponse(['error' => 'Método não suportado'], 405);
}