<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Atividade.php';
require_once __DIR__ . '/../models/Projeto.php';
require_once __DIR__ . '/../models/Liderado.php';

class AtividadesController {
    private $model;
    private $projetoModel;
    private $lideradoModel;
    
    public function __construct() {
        $this->model = new Atividade();
        $this->projetoModel = new Projeto();
        $this->lideradoModel = new Liderado();
    }
    
    /**
     * Listar todas as atividades
     */
    public function index() {
        // Verificar permissão
        if (!isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
        
        // Obter filtros
        $projetoId = isset($_GET['projeto_id']) ? (int) $_GET['projeto_id'] : null;
        $status = sanitizeInput($_GET['status'] ?? null);
        $apenasAtivas = isset($_GET['ativas']) ? (bool) $_GET['ativas'] : true;
        
        // Obter atividades conforme permissões
        $atividades = [];
        
        // Para gestores e admin, mostrar todas as atividades com os filtros aplicados
        if (hasPermission('gestor') || hasPermission('admin')) {
            $atividades = $this->model->getAll($projetoId, $status, $apenasAtivas);
        } 
        // Para liderados comuns, mostrar apenas suas atividades
        else if (isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
            $lideradoId = $_SESSION[SESSION_PREFIX . 'liderado_id'];
            
            // Se filtrar por projeto, verificar também se o liderado faz parte do projeto
            if ($projetoId) {
                $liderado = $this->lideradoModel->getById($lideradoId);
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
                    $todasAtividades = $this->model->getAll($projetoId, $status, $apenasAtivas);
                    foreach ($todasAtividades as $atividade) {
                        $responsaveis = $this->model->getResponsaveis($atividade['id']);
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
                $atividades = $this->model->getAtividadesPorLiderado($lideradoId, $apenasAtivas);
                
                // Aplicar filtro de status, se fornecido
                if ($status) {
                    $atividades = array_filter($atividades, function($atividade) use ($status) {
                        return $atividade['status'] === $status;
                    });
                }
            }
        }
        
        // Carregar projetos para o filtro
        $projetos = [];
        
        if (hasPermission('gestor') || hasPermission('admin')) {
            $projetos = $this->projetoModel->getAll();
        } else if (isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
            $liderado = $this->lideradoModel->getById($_SESSION[SESSION_PREFIX . 'liderado_id']);
            
            if ($liderado && isset($liderado['projetos'])) {
                foreach ($liderado['projetos'] as $projetoBasico) {
                    $projeto = $this->projetoModel->getById($projetoBasico['projeto_id']);
                    if ($projeto) {
                        $projetos[] = $projeto;
                    }
                }
            }
        }
        
        // Carregar a view
        include_once __DIR__ . '/../views/atividades/index.php';
    }
    
    /**
     * Exibir detalhes de uma atividade
     */
    public function view($id = null) {
        // Verificar se ID foi fornecido
        if (!$id) {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        }
        
        // Verificar permissão
        if (!isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
        
        $atividade = $this->model->getById($id);
        
        if (!$atividade) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Atividade não encontrada';
            header('Location: ' . BASE_URL . '/atividades.php');
            exit;
        }
        
        // Para liderados comuns, verificar se é responsável pela atividade
        if (!hasPermission('gestor') && !hasPermission('admin') && isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
            $lideradoId = $_SESSION[SESSION_PREFIX . 'liderado_id'];
            $responsaveis = $this->model->getResponsaveis($id);
            
            $ehResponsavel = false;
            foreach ($responsaveis as $responsavel) {
                if ($responsavel['id'] == $lideradoId) {
                    $ehResponsavel = true;
                    break;
                }
            }
            
            if (!$ehResponsavel) {
                // Verificar se faz parte do projeto
                if ($atividade['projeto_id']) {
                    $liderado = $this->lideradoModel->getById($lideradoId);
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
                        $_SESSION[SESSION_PREFIX . 'error'] = 'Você não tem permissão para acessar esta atividade';
                        header('Location: ' . BASE_URL . '/atividades.php');
                        exit;
                    }
                } else {
                    $_SESSION[SESSION_PREFIX . 'error'] = 'Você não tem permissão para acessar esta atividade';
                    header('Location: ' . BASE_URL . '/atividades.php');
                    exit;
                }
            }
        }
        
        // Obter dados adicionais
        $responsaveis = $atividade['responsaveis'];
        $apontamentos = $atividade['apontamentos'];
        
        // Obter projeto, se existir
        $projeto = null;
        if ($atividade['projeto_id']) {
            $projeto = $this->projetoModel->getById($atividade['projeto_id']);
        }
        
        // Carregar a view
        include_once __DIR__ . '/../views/atividades/view.php';
    }
    
    /**
     * Exibir formulário para adicionar atividade
     */
    public function create() {
        // Verificar permissão
        if (!isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
        
        // Pré-selecionar projeto, se fornecido na URL
        $projetoId = isset($_GET['projeto_id']) ? (int) $_GET['projeto_id'] : null;
        $projeto = null;
        
        if ($projetoId) {
            $projeto = $this->projetoModel->getById($projetoId);
        }
        
        // Carregar projetos para a view
        $projetos = [];
        
        if (hasPermission('gestor') || hasPermission('admin')) {
            $projetos = $this->projetoModel->getAll();
        } else if (isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
            $liderado = $this->lideradoModel->getById($_SESSION[SESSION_PREFIX . 'liderado_id']);
            
            if ($liderado && isset($liderado['projetos'])) {
                foreach ($liderado['projetos'] as $projetoBasico) {
                    $projeto = $this->projetoModel->getById($projetoBasico['projeto_id']);
                    if ($projeto) {
                        $projetos[] = $projeto;
                    }
                }
            }
        }
        
        // Carregar liderados para a view (para selecionar responsáveis)
        $liderados = [];
        
        if (hasPermission('gestor') || hasPermission('admin')) {
            $liderados = $this->lideradoModel->getAll();
        } else {
            // Para liderado comum, incluir apenas ele mesmo como responsável
            if (isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
                $liderado = $this->lideradoModel->getById($_SESSION[SESSION_PREFIX . 'liderado_id']);
                if ($liderado) {
                    $liderados[] = $liderado;
                }
            }
        }
        
        // Carregar a view
        include_once __DIR__ . '/../views/atividades/create.php';
    }
    
    /**
     * Processar formulário de adição de atividade
     */
    public function store() {
        // Verificar permissão
        if (!isLoggedIn()) {
            jsonResponse(['error' => 'Você precisa estar logado para realizar esta ação'], 401);
        }
        
        // Verificar token CSRF
        verifyCsrfToken();
        
        // Validar e sanitizar dados
        $titulo = sanitizeInput($_POST['titulo'] ?? '');
        $descricao = sanitizeInput($_POST['descricao'] ?? '');
        $projetoId = isset($_POST['projeto_id']) && !empty($_POST['projeto_id']) ? (int) $_POST['projeto_id'] : null;
        $prioridade = sanitizeInput($_POST['prioridade'] ?? 'Média');
        $status = sanitizeInput($_POST['status'] ?? 'Não iniciada');
        $dataInicio = sanitizeInput($_POST['data_inicio'] ?? null);
        $dataFim = sanitizeInput($_POST['data_fim'] ?? null);
        $horasEstimadas = isset($_POST['horas_estimadas']) ? (float) $_POST['horas_estimadas'] : 0;
        
        if (empty($titulo)) {
            jsonResponse(['error' => 'Título é obrigatório'], 400);
        }
        
        // Se projeto_id foi fornecido, validar acesso ao projeto
        if ($projetoId) {
            $projeto = $this->projetoModel->getById($projetoId);
            
            if (!$projeto) {
                jsonResponse(['error' => 'Projeto não encontrado'], 404);
            }
            
            // Para liderados comuns, verificar se faz parte do projeto
            if (!hasPermission('gestor') && !hasPermission('admin') && isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
                $lideradoId = $_SESSION[SESSION_PREFIX . 'liderado_id'];
                $liderado = $this->lideradoModel->getById($lideradoId);
                $temAcesso = false;
                
                if ($liderado) {
                    if ($liderado['cross_funcional']) {
                        $temAcesso = true;
                    } else {
                        foreach ($liderado['projetos'] as $projetoLiderado) {
                            if ($projetoLiderado['projeto_id'] == $projetoId) {
                                $temAcesso = true;
                                break;
                            }
                        }
                    }
                }
                
                if (!$temAcesso) {
                    jsonResponse(['error' => 'Você não tem permissão para criar atividades neste projeto'], 403);
                }
            }
        }
        
        // Obter responsáveis
        $responsaveis = isset($_POST['responsaveis']) && is_array($_POST['responsaveis']) ? 
                         array_map('intval', $_POST['responsaveis']) : [];
        
        // Para liderados comuns, incluir apenas ele mesmo como responsável
        if (!hasPermission('gestor') && !hasPermission('admin') && isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
            $lideradoId = $_SESSION[SESSION_PREFIX . 'liderado_id'];
            $responsaveis = [$lideradoId];
        }
        
        // Preparar dados
        $data = [
            'titulo' => $titulo,
            'descricao' => $descricao,
            'projeto_id' => $projetoId,
            'prioridade' => $prioridade,
            'status' => $status,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'horas_estimadas' => $horasEstimadas,
            'responsaveis' => $responsaveis
        ];
        
        // Adicionar atividade
        $id = $this->model->add($data);
        
        if ($id) {
            jsonResponse(['success' => true, 'message' => 'Atividade adicionada com sucesso', 'id' => $id]);
        } else {
            jsonResponse(['error' => 'Erro ao adicionar atividade'], 500);
        }
    }
    
    /**
     * Exibir formulário para editar atividade
     */
    public function edit($id = null) {
        // Verificar se ID foi fornecido
        if (!$id) {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        }
        
        // Verificar permissão
        if (!isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
        
        $atividade = $this->model->getById($id);
        
        if (!$atividade) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Atividade não encontrada';
            header('Location: ' . BASE_URL . '/atividades.php');
            exit;
        }
        
        // Para liderados comuns, verificar se é responsável pela atividade
        if (!hasPermission('gestor') && !hasPermission('admin') && isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
            $lideradoId = $_SESSION[SESSION_PREFIX . 'liderado_id'];
            $responsaveis = $this->model->getResponsaveis($id);
            
            $ehResponsavel = false;
            foreach ($responsaveis as $responsavel) {
                if ($responsavel['id'] == $lideradoId) {
                    $ehResponsavel = true;
                    break;
                }
            }
            
            if (!$ehResponsavel) {
                $_SESSION[SESSION_PREFIX . 'error'] = 'Você não tem permissão para editar esta atividade';
                header('Location: ' . BASE_URL . '/atividades.php');
                exit;
            }
        }
        
        // Carregar dados para a view
        $responsaveis = $atividade['responsaveis'];
        
        // Obter IDs dos responsáveis para o formulário
        $idsResponsaveis = array_map(function($responsavel) {
            return $responsavel['id'];
        }, $responsaveis);
        
        // Carregar projetos para a view
        $projetos = [];
        
        if (hasPermission('gestor') || hasPermission('admin')) {
            $projetos = $this->projetoModel->getAll();
        } else if (isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
            $liderado = $this->lideradoModel->getById($_SESSION[SESSION_PREFIX . 'liderado_id']);
            
            if ($liderado && isset($liderado['projetos'])) {
                foreach ($liderado['projetos'] as $projetoBasico) {
                    $projeto = $this->projetoModel->getById($projetoBasico['projeto_id']);
                    if ($projeto) {
                        $projetos[] = $projeto;
                    }
                }
            }
        }
        
        // Carregar liderados para a view (para selecionar responsáveis)
        $liderados = [];
        
        if (hasPermission('gestor') || hasPermission('admin')) {
            $liderados = $this->lideradoModel->getAll();
        } else {
            // Para liderado comum, incluir apenas ele mesmo como responsável
            if (isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
                $liderado = $this->lideradoModel->getById($_SESSION[SESSION_PREFIX . 'liderado_id']);
                if ($liderado) {
                    $liderados[] = $liderado;
                }
            }
        }
        
        // Carregar a view
        include_once __DIR__ . '/../views/atividades/edit.php';
    }
    
    /**
     * Processar formulário de edição de atividade
     */
    public function update($id = null) {
        // Verificar se ID foi fornecido
        if (!$id) {
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        }
        
        // Verificar permissão
        if (!isLoggedIn()) {
            jsonResponse(['error' => 'Você precisa estar logado para realizar esta ação'], 401);
        }
        
        // Verificar token CSRF
        verifyCsrfToken();
        
        // Validar ID
        if (!$id) {
            jsonResponse(['error' => 'ID inválido'], 400);
        }
        
        // Obter atividade atual
        $atividade = $this->model->getById($id);
        
        if (!$atividade) {
            jsonResponse(['error' => 'Atividade não encontrada'], 404);
        }
        
        // Para liderados comuns, verificar se é responsável pela atividade
        if (!hasPermission('gestor') && !hasPermission('admin') && isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
            $lideradoId = $_SESSION[SESSION_PREFIX . 'liderado_id'];
            $responsaveis = $this->model->getResponsaveis($id);
            
            $ehResponsavel = false;
            foreach ($responsaveis as $responsavel) {
                if ($responsavel['id'] == $lideradoId) {
                    $ehResponsavel = true;
                    break;
                }
            }
            
            if (!$ehResponsavel) {
                jsonResponse(['error' => 'Você não tem permissão para editar esta atividade'], 403);
            }
        }
        
        // Validar e sanitizar dados
        $titulo = sanitizeInput($_POST['titulo'] ?? '');
        $descricao = sanitizeInput($_POST['descricao'] ?? '');
        $projetoId = isset($_POST['projeto_id']) && !empty($_POST['projeto_id']) ? (int) $_POST['projeto_id'] : null;
        $prioridade = sanitizeInput($_POST['prioridade'] ?? 'Média');
        $status = sanitizeInput($_POST['status'] ?? 'Não iniciada');
        $dataInicio = sanitizeInput($_POST['data_inicio'] ?? null);
        $dataFim = sanitizeInput($_POST['data_fim'] ?? null);
        $horasEstimadas = isset($_POST['horas_estimadas']) ? (float) $_POST['horas_estimadas'] : 0;
        
        if (empty($titulo)) {
            jsonResponse(['error' => 'Título é obrigatório'], 400);
        }
        
        // Obter responsáveis
        $responsaveis = isset($_POST['responsaveis']) && is_array($_POST['responsaveis']) ? 
                         array_map('intval', $_POST['responsaveis']) : [];
        
        // Para liderados comuns, manter os responsáveis atuais
        if (!hasPermission('gestor') && !hasPermission('admin')) {
            $responsaveisAtuais = $this->model->getResponsaveis($id);
            $responsaveis = array_map(function($responsavel) {
                return $responsavel['id'];
            }, $responsaveisAtuais);
        }
        
        // Preparar dados
        $data = [
            'titulo' => $titulo,
            'descricao' => $descricao,
            'projeto_id' => $projetoId,
            'prioridade' => $prioridade,
            'status' => $status,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'horas_estimadas' => $horasEstimadas,
            'responsaveis' => $responsaveis
        ];
        
        // Atualizar atividade
        $result = $this->model->update($id, $data);
        
        if ($result) {
            jsonResponse(['success' => true, 'message' => 'Atividade atualizada com sucesso']);
        } else {
            jsonResponse(['error' => 'Erro ao atualizar atividade'], 500);
        }
    }
    
    /**
     * Processar exclusão de atividade
     */
    public function delete($id = null) {
        // Verificar se ID foi fornecido
        if (!$id) {
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        }
        
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin')) {
            jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
        }
        
        // Verificar token CSRF
        verifyCsrfToken();
        
        // Validar ID
        if (!$id) {
            jsonResponse(['error' => 'ID inválido'], 400);
        }
        
        // Excluir atividade
        $result = $this->model->delete($id);
        
        if ($result) {
            jsonResponse(['success' => true, 'message' => 'Atividade excluída com sucesso']);
        } else {
            jsonResponse(['error' => 'Erro ao excluir atividade'], 500);
        }
    }
    
    /**
     * Atualizar status da atividade
     */
    public function atualizarStatus() {
        // Verificar permissão
        if (!isLoggedIn()) {
            jsonResponse(['error' => 'Você precisa estar logado para realizar esta ação'], 401);
        }
        
        // Verificar token CSRF
        verifyCsrfToken();
        
        // Validar dados
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $status = sanitizeInput($_POST['status'] ?? '');
        
        if (!$id || empty($status)) {
            jsonResponse(['error' => 'Todos os campos obrigatórios devem ser preenchidos'], 400);
        }
        
        // Obter atividade atual
        $atividade = $this->model->getById($id);
        
        if (!$atividade) {
            jsonResponse(['error' => 'Atividade não encontrada'], 404);
        }
        
        // Para liderados comuns, verificar se é responsável pela atividade
        if (!hasPermission('gestor') && !hasPermission('admin') && isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
            $lideradoId = $_SESSION[SESSION_PREFIX . 'liderado_id'];
            $responsaveis = $this->model->getResponsaveis($id);
            
            $ehResponsavel = false;
            foreach ($responsaveis as $responsavel) {
                if ($responsavel['id'] == $lideradoId) {
                    $ehResponsavel = true;
                    break;
                }
            }
            
            if (!$ehResponsavel) {
                jsonResponse(['error' => 'Você não tem permissão para atualizar esta atividade'], 403);
            }
        }
        
        // Atualizar status
        $result = $this->model->atualizarStatus($id, $status);
        
        if ($result) {
            jsonResponse(['success' => true, 'message' => 'Status da atividade atualizado com sucesso']);
        } else {
            jsonResponse(['error' => 'Erro ao atualizar status da atividade'], 500);
        }
    }
}