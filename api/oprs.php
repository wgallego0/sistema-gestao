<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/OPR.php';
require_once __DIR__ . '/../controllers/OPRController.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Você precisa estar logado para acessar esta API'], 401);
}

// Instanciar controlador
$controller = new OPRController();

// Determinar ação baseada no método HTTP e parâmetros
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : null;
$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : null);
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if ($id) {
            // Obter detalhes de um OPR
            $opr = (new OPR())->getById($id);
            
            if (!$opr) {
                jsonResponse(['error' => 'OPR não encontrado'], 404);
            }
            
            // Verificar permissão para visualizar o OPR
            if (!hasPermission('gestor') && !hasPermission('admin') && 
                $_SESSION[SESSION_PREFIX . 'liderado_id'] != $opr['liderado_id']) {
                jsonResponse(['error' => 'Você não tem permissão para visualizar este OPR'], 403);
            }
            
            // Formatar dados para visualização
            $oprModel = new OPR();
            $relatorio = $oprModel->gerarRelatorio($id);
            
            jsonResponse(['success' => true, 'data' => $relatorio]);
        } else if ($action === 'all') {
            // Listar todos os OPRs (ou filtrados por liderado)
            $lideradoId = isset($_GET['liderado_id']) ? (int) $_GET['liderado_id'] : null;
            
            // Para liderados comuns, mostrar apenas seus OPRs
            if (!hasPermission('gestor') && !hasPermission('admin')) {
                $lideradoId = $_SESSION[SESSION_PREFIX . 'liderado_id'];
            }
            
            $oprs = (new OPR())->getAll($lideradoId);
            jsonResponse(['success' => true, 'data' => $oprs]);
        } else if ($action === 'pendentes') {
            // Verificar permissão
            if (!hasPermission('gestor') && !hasPermission('admin')) {
                jsonResponse(['error' => 'Você não tem permissão para acessar esta funcionalidade'], 403);
            }
            
            // Listar OPRs pendentes de aprovação
            $oprs = (new OPR())->getPendentesAprovacao();
            jsonResponse(['success' => true, 'data' => $oprs]);
        } else {
            jsonResponse(['error' => 'Ação não especificada'], 400);
        }
        break;
    
    case 'POST':
        // Verificar token CSRF
        verifyCsrfToken();
        
        if ($action === 'store') {
            // Adicionar novo OPR
            $controller->store();
        } else if ($action === 'update' && $id) {
            // Atualizar OPR existente
            $controller->update($id);
        } else if ($action === 'delete' && $id) {
            // Excluir OPR
            $controller->delete($id);
        } else if ($action === 'update_status' && $id) {
            // Atualizar status do OPR
            $status = sanitizeInput($_POST['status'] ?? '');
            
            if (empty($status)) {
                jsonResponse(['error' => 'Status não especificado'], 400);
            }
            
            // Verificar se status é válido
            $statusValidos = ['Rascunho', 'Enviado', 'Aprovado', 'Revisão'];
            if (!in_array($status, $statusValidos)) {
                jsonResponse(['error' => 'Status inválido'], 400);
            }
            
            // Obter OPR atual
            $opr = (new OPR())->getById($id);
            
            if (!$opr) {
                jsonResponse(['error' => 'OPR não encontrado'], 404);
            }
            
            // Verificar permissão para alterar status
            // - Liderado comum só pode alterar para Enviado se for o próprio OPR e estiver em Rascunho
            // - Gestor/admin pode alterar qualquer status, exceto para Aprovado (somente admin)
            if (!hasPermission('gestor') && !hasPermission('admin')) {
                if ($_SESSION[SESSION_PREFIX . 'liderado_id'] != $opr['liderado_id'] || 
                    $opr['status'] !== 'Rascunho' || 
                    $status !== 'Enviado') {
                    jsonResponse(['error' => 'Você não tem permissão para alterar o status deste OPR'], 403);
                }
            } else if (!hasPermission('admin') && $status === 'Aprovado') {
                jsonResponse(['error' => 'Apenas administradores podem aprovar OPRs'], 403);
            }
            
            // Atualizar status
            $result = (new OPR())->atualizarStatus($id, $status);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Status do OPR atualizado com sucesso']);
            } else {
                jsonResponse(['error' => 'Erro ao atualizar status do OPR'], 500);
            }
        } else {
            jsonResponse(['error' => 'Ação não especificada ou parâmetros inválidos'], 400);
        }
        break;
    
    default:
        jsonResponse(['error' => 'Método não suportado'], 405);
}