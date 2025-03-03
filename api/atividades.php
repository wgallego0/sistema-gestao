<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Atividade.php';
require_once __DIR__ . '/../controllers/AtividadesController.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Você precisa estar logado para acessar esta API'], 401);
}

// Instanciar controlador
$controller = new AtividadesController();

// Determinar ação baseada no método HTTP e parâmetros
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : null;
$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : null);
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if ($id) {
            // Obter detalhes de uma atividade
            $atividade = (new Atividade())->getById($id);
            
            if (!$atividade) {
                jsonResponse(['error' => 'Atividade não encontrada'], 404);
            }
            
            // Para liderados comuns, verificar se é responsável pela atividade
            if (!hasPermission('gestor') && !hasPermission('admin') && isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
                $lideradoId = $_SESSION[SESSION_PREFIX . 'liderado_id'];
                $responsaveis = (new Atividade())->getResponsaveis($id);
                
                $ehResponsavel = false;
                foreach ($responsaveis as $responsavel) {
                    if ($responsavel['id'] == $lideradoId) {
                        $ehResponsavel = true;
                        break;
                    }
                }
                
                // Se não for responsável e a atividade pertence a um projeto, verificar se faz parte do projeto
                if (!$ehResponsavel && $atividade['projeto_id']) {
                    require_once __DIR__ . '/../models/Liderado.php';
                    $liderado = (new Liderado())->getById($lideradoId);
                    
                    $temAcessoProjeto = false;
                    if ($liderado) {
                        if ($liderado['cross_funcional']) {
                            $temAcessoProjeto = true;
                        } else {
                            foreach ($liderado['projetos'] as $projeto) {
                                if ($projeto['projeto_id'] == $atividade['projeto_id']) {
                                    $temAcessoProjeto = true;
                                    break;
                                }
                            }
                        }
                    }
                    
                    if (!$temAcessoProjeto) {
                        jsonResponse(['error' => 'Você não tem permissão para acessar esta atividade'], 403);
                    }
                } else if (!$ehResponsavel) {
                    jsonResponse(['error' => 'Você não tem permissão para acessar esta atividade'], 403);
                }
            }
            
            jsonResponse(['success' => true, 'data' => $atividade]);
        } else if ($action === 'all') {
            // Obter filtros
            $projetoId = isset($_GET['projeto_id']) ? (int) $_GET['projeto_id'] : null;
            $status = sanitizeInput($_GET['status'] ?? null);
            $apenasAtivas = isset($_GET['ativas']) ? (bool) $_GET['ativas'] : true;
            
            // Obter atividades conforme permissões
            $atividades = [];
            
            // Para gestores e admin, mostrar todas as atividades com os filtros aplicados
            if (hasPermission('gestor') || hasPermission('admin')) {
                $atividades = (new Atividade())->getAll($projetoId, $status, $apenasAtivas);
            } 
            // Para liderados comuns, mostrar apenas suas atividades
            else if (isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
                $lideradoId = $_SESSION[SESSION_PREFIX . 'liderado_id'];
                
                // Se filtrar por projeto, verificar também se o liderado faz parte do projeto
                if ($projetoId) {
                    require_once __DIR__ . '/../models/Liderado.php';
                    $liderado = (new Liderado())->getById($lideradoId);
                    $temAcesso = false;
                    
                    if ($liderado) {
                        // Verificar se é cross-funcional
                        if ($liderado['cross_funcional']) {
                            $temAcesso = true;
                        } else {
                            // Verificar se está nos projetos do liderado
                            foreach ($liderado['projetos'] as $projetoDoLiderado) {
                                if ($projetoDoLiderado['projeto_id'] == $projetoId) {
                                    $temAcesso = true;
                                    break;
                                }
                            }
                        }
                    }
                    
                    if ($temAcesso) {
                        // Obter atividades do projeto que o liderado é responsável
                        $todasAtividades = (new Atividade())->getAll($projetoId, $status, $apenasAtivas);
                        foreach ($todasAtividades as $atividade) {
                            $responsaveis = (new Atividade())->getResponsaveis($atividade['id']);
                            foreach ($responsaveis as $responsavel) {
                                if ($responsavel['id'] == $lideradoId) {
                                    $atividades[] = $atividade;
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    // Sem filtro de projeto, mostrar todas as atividades do liderado
                    $atividades = (new Atividade())->getAtividadesPorLiderado($lideradoId, $apenasAtivas);
                    
                    // Aplicar filtro de status, se fornecido
                    if ($status) {
                        $atividades = array_filter($atividades, function($atividade) use ($status) {
                            return $atividade['status'] === $status;
                        });
                    }
                }
            }
            
            jsonResponse(['success' => true, 'data' => $atividades]);
        } else if ($action === 'responsaveis' && $id) {
            // Listar responsáveis de uma atividade
            $responsaveis = (new Atividade())->getResponsaveis($id);
            jsonResponse(['success' => true, 'data' => $responsaveis]);
        } else if ($action === 'apontamentos' && $id) {
            // Listar apontamentos de uma atividade
            $apontamentos = (new Atividade())->getApontamentos($id);
            jsonResponse(['success' => true, 'data' => $apontamentos]);
        } else if ($action === 'recentes') {
            // Listar atividades recentes
            $limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 10;
            $atividades = (new Atividade())->getRecentes($limite);
            jsonResponse(['success' => true, 'data' => $atividades]);
        } else if ($action === 'buscar') {
            // Buscar atividades por termo
            $termo = sanitizeInput($_GET['q'] ?? '');
            
            if (strlen($termo) < 2) {
                jsonResponse(['success' => true, 'data' => []]);
            }
            
            $atividades = (new Atividade())->buscarPorTermo($termo);
            jsonResponse(['success' => true, 'data' => $atividades]);
        } else if ($action === 'horas_disponiveis') {
            // Verificar horas disponíveis para apontamento
            $data = sanitizeInput($_GET['data'] ?? date('Y-m-d'));
            $lideradoId = $_SESSION[SESSION_PREFIX . 'liderado_id'] ?? 0;
            
            if (!$lideradoId) {
                jsonResponse(['error' => 'Liderado não identificado'], 400);
            }
            
            require_once __DIR__ . '/../models/Apontamento.php';
            $horasDisponiveis = (new Apontamento())->getHorasDisponiveis($lideradoId, $data);
            
            jsonResponse(['success' => true, 'data' => ['horas_disponiveis' => $horasDisponiveis]]);
        } else {
            jsonResponse(['error' => 'Ação não especificada'], 400);
        }
        break;
    
    case 'POST':
        // Verificar token CSRF
        verifyCsrfToken();
        
        if ($action === 'store') {
            // Adicionar nova atividade
            $controller->store();
        } else if ($action === 'update' && $id) {
            // Atualizar atividade existente
            $controller->update($id);
        } else if ($action === 'delete' && $id) {
            // Verificar permissão
            if (!hasPermission('gestor') && !hasPermission('admin')) {
                jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
            }
            
            // Excluir atividade
            $controller->delete($id);
        } else if ($action === 'update_status' && $id) {
            // Atualizar status da atividade
            $controller->atualizarStatus();
        } else if ($action === 'adicionar_responsavel') {
            // Verificar permissão
            if (!hasPermission('gestor') && !hasPermission('admin')) {
                jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
            }
            
            // Validar dados
            $atividadeId = isset($_POST['atividade_id']) ? (int) $_POST['atividade_id'] : 0;
            $lideradoId = isset($_POST['liderado_id']) ? (int) $_POST['liderado_id'] : 0;
            
            if (!$atividadeId || !$lideradoId) {
                jsonResponse(['error' => 'IDs de atividade e liderado são obrigatórios'], 400);
            }
            
            // Adicionar responsável
            $result = (new Atividade())->adicionarResponsavel($atividadeId, $lideradoId);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Responsável adicionado com sucesso']);
            } else {
                jsonResponse(['error' => 'Erro ao adicionar responsável'], 500);
            }
        } else if ($action === 'remover_responsavel') {
            // Verificar permissão
            if (!hasPermission('gestor') && !hasPermission('admin')) {
                jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
            }
            
            // Validar dados
            $atividadeId = isset($_POST['atividade_id']) ? (int) $_POST['atividade_id'] : 0;
            $lideradoId = isset($_POST['liderado_id']) ? (int) $_POST['liderado_id'] : 0;
            
            if (!$atividadeId || !$lideradoId) {
                jsonResponse(['error' => 'IDs de atividade e liderado são obrigatórios'], 400);
            }
            
            // Obter todos os responsáveis atuais
            $responsaveis = (new Atividade())->getResponsaveis($atividadeId);
            
            // Verificar se há mais de um responsável (não pode deixar a atividade sem responsáveis)
            if (count($responsaveis) <= 1) {
                jsonResponse(['error' => 'Não é possível remover o único responsável da atividade'], 400);
            }
            
            // Remover responsável
            require_once __DIR__ . '/../models/Atividade.php';
            $stmt = getConnection()->prepare("DELETE FROM atividades_responsaveis WHERE atividade_id = ? AND liderado_id = ?");
            $result = $stmt->execute([$atividadeId, $lideradoId]);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Responsável removido com sucesso']);
            } else {
                jsonResponse(['error' => 'Erro ao remover responsável'], 500);
            }
        } else {
            jsonResponse(['error' => 'Ação não especificada ou parâmetros inválidos'], 400);
        }
        break;
    
    default:
        jsonResponse(['error' => 'Método não suportado'], 405);
}