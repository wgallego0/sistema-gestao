<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Projeto.php';
require_once __DIR__ . '/../controllers/ProjetosController.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Você precisa estar logado para acessar esta API'], 401);
}

// Instanciar controlador
$controller = new ProjetosController();

// Determinar ação baseada no método HTTP e parâmetros
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : null;
$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : null);
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if ($id) {
            // Obter detalhes de um projeto
            $projeto = (new Projeto())->getById($id);
            
            if (!$projeto) {
                jsonResponse(['error' => 'Projeto não encontrado'], 404);
            }
            
            // Para liderados comuns, verificar se pertence ao projeto
            if (!hasPermission('gestor') && !hasPermission('admin') && isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
                require_once __DIR__ . '/../models/Liderado.php';
                $liderado = (new Liderado())->getById($_SESSION[SESSION_PREFIX . 'liderado_id']);
                
                $temAcesso = false;
                if ($liderado) {
                    // Verificar se é cross-funcional
                    if ($liderado['cross_funcional']) {
                        $temAcesso = true;
                    } else {
                        // Verificar se está nos projetos do liderado
                        foreach ($liderado['projetos'] as $projetoDoLiderado) {
                            if ($projetoDoLiderado['projeto_id'] == $id) {
                                $temAcesso = true;
                                break;
                            }
                        }
                    }
                }
                
                if (!$temAcesso) {
                    jsonResponse(['error' => 'Você não tem permissão para acessar os detalhes deste projeto'], 403);
                }
            }
            
            jsonResponse(['success' => true, 'data' => $projeto]);
        } else if ($action === 'all') {
            // Listar todos os projetos
            $projetos = [];
            
            // Para gestores e admin, mostrar todos os projetos
            if (hasPermission('gestor') || hasPermission('admin')) {
                $projetos = (new Projeto())->getAll();
            }
            // Para liderados comuns, mostrar apenas seus projetos
            else if (isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
                require_once __DIR__ . '/../models/Liderado.php';
                $liderado = (new Liderado())->getById($_SESSION[SESSION_PREFIX . 'liderado_id']);
                
                if ($liderado && isset($liderado['projetos'])) {
                    // Obter detalhes completos de cada projeto
                    foreach ($liderado['projetos'] as $projetoBasico) {
                        $projeto = (new Projeto())->getById($projetoBasico['projeto_id']);
                        if ($projeto) {
                            $projetos[] = $projeto;
                        }
                    }
                }
            }
            
            jsonResponse(['success' => true, 'data' => $projetos]);
        } else if ($action === 'membros' && $id) {
            // Listar membros de um projeto
            $controller->membros($id);
        } else if ($action === 'atividades' && $id) {
            // Listar atividades de um projeto
            $controller->atividades($id);
        } else if ($action === 'progresso' && $id) {
            // Obter progresso de um projeto
            $controller->progresso($id);
        } else if ($action === 'estatisticas' && $id) {
            // Obter estatísticas de um projeto
            $projeto = (new Projeto())->getById($id);
            
            if (!$projeto) {
                jsonResponse(['error' => 'Projeto não encontrado'], 404);
            }
            
            $estatisticas = (new Projeto())->getEstatisticas($id);
            jsonResponse(['success' => true, 'data' => $estatisticas]);
        } else if ($action === 'buscar') {
            // Buscar projetos por termo
            $termo = sanitizeInput($_GET['q'] ?? '');
            
            if (strlen($termo) < 2) {
                jsonResponse(['success' => true, 'data' => []]);
            }
            
            // Filtrar resultados conforme permissões
            $projetos = [];
            $allProjetos = (new Projeto())->buscarPorTermo($termo);
            
            if (hasPermission('gestor') || hasPermission('admin')) {
                $projetos = $allProjetos;
            } else if (isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
                require_once __DIR__ . '/../models/Liderado.php';
                $liderado = (new Liderado())->getById($_SESSION[SESSION_PREFIX . 'liderado_id']);
                
                if ($liderado) {
                    if ($liderado['cross_funcional']) {
                        $projetos = $allProjetos;
                    } else {
                        // Filtrar apenas projetos do liderado
                        foreach ($allProjetos as $projeto) {
                            foreach ($liderado['projetos'] as $projetoLiderado) {
                                if ($projetoLiderado['projeto_id'] == $projeto['id']) {
                                    $projetos[] = $projeto;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            
            jsonResponse(['success' => true, 'data' => $projetos]);
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
            
            // Adicionar novo projeto
            $controller->store();
        } else if ($action === 'update' && $id) {
            // Verificar permissão
            if (!hasPermission('gestor') && !hasPermission('admin')) {
                jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
            }
            
            // Atualizar projeto existente
            $controller->update($id);
        } else if ($action === 'delete' && $id) {
            // Verificar permissão
            if (!hasPermission('admin')) {
                jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
            }
            
            // Excluir projeto
            $controller->delete($id);
        } else if ($action === 'atualizar_status' && $id) {
            // Verificar permissão
            if (!hasPermission('gestor') && !hasPermission('admin')) {
                jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
            }
            
            // Atualizar status do projeto
            $controller->atualizarStatus();
        } else {
            jsonResponse(['error' => 'Ação não especificada ou parâmetros inválidos'], 400);
        }
        break;
    
    default:
        jsonResponse(['error' => 'Método não suportado'], 405);
}