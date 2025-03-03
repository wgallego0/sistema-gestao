<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Projeto.php';
require_once __DIR__ . '/../models/Liderado.php';
require_once __DIR__ . '/../models/Atividade.php';

class ProjetosController {
    private $model;
    private $lideradoModel;
    private $atividadeModel;
    
    public function __construct() {
        $this->model = new Projeto();
        $this->lideradoModel = new Liderado();
        $this->atividadeModel = new Atividade();
    }
    
    /**
     * Listar todos os projetos
     */
    public function index() {
        // Verificar permissão
        if (!isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
        
        // Obter projetos
        $projetos = [];
        
        // Para gestores e admin, mostrar todos os projetos
        if (hasPermission('gestor') || hasPermission('admin')) {
            $projetos = $this->model->getAll();
        } 
        // Para liderados comuns, mostrar apenas seus projetos
        else if (isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
            $liderado = $this->lideradoModel->getById($_SESSION[SESSION_PREFIX . 'liderado_id']);
            
            if ($liderado && isset($liderado['projetos'])) {
                // Obter detalhes completos de cada projeto
                foreach ($liderado['projetos'] as $projetoBasico) {
                    $projeto = $this->model->getById($projetoBasico['projeto_id']);
                    if ($projeto) {
                        $projetos[] = $projeto;
                    }
                }
            }
        }
        
        // Carregar a view
        include_once __DIR__ . '/../views/projetos/index.php';
    }
    
    /**
     * Exibir detalhes de um projeto
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
        
        $projeto = $this->model->getById($id);
        
        if (!$projeto) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Projeto não encontrado';
            header('Location: ' . BASE_URL . '/projetos.php');
            exit;
        }
        
        // Para liderados comuns, verificar se pertence ao projeto
        if (!hasPermission('gestor') && !hasPermission('admin') && isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
            $liderado = $this->lideradoModel->getById($_SESSION[SESSION_PREFIX . 'liderado_id']);
            
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
                $_SESSION[SESSION_PREFIX . 'error'] = 'Você não tem permissão para acessar este projeto';
                header('Location: ' . BASE_URL . '/projetos.php');
                exit;
            }
        }
        
        // Obter dados adicionais para a view
        $membros = $projeto['membros'];
        $atividades = $projeto['atividades'];
        $estatisticas = $this->model->getEstatisticas($id);
        $apontamentosRecentes = $this->model->getApontamentosRecentes($id, 10);
        
        // Carregar a view
        include_once __DIR__ . '/../views/projetos/view.php';
    }
    
    /**
     * Exibir formulário para adicionar projeto
     */
    public function create() {
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin')) {
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        }
        
        // Carregar liderados para a view
        $liderados = $this->lideradoModel->getAll();
        
        // Carregar a view
        include_once __DIR__ . '/../views/projetos/create.php';
    }
    
    /**
     * Processar formulário de adição de projeto
     */
    public function store() {
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin')) {
            jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
        }
        
        // Verificar token CSRF
        verifyCsrfToken();
        
        // Validar e sanitizar dados
        $nome = sanitizeInput($_POST['nome'] ?? '');
        $descricao = sanitizeInput($_POST['descricao'] ?? '');
        $dataInicio = sanitizeInput($_POST['data_inicio'] ?? '');
        $dataFim = sanitizeInput($_POST['data_fim'] ?? null);
        $status = sanitizeInput($_POST['status'] ?? 'Não iniciado');
        
        if (empty($nome) || empty($dataInicio)) {
            jsonResponse(['error' => 'Nome e Data de Início são obrigatórios'], 400);
        }
        
        // Verificar se já existe projeto com o mesmo nome
        if ($this->model->nomeExiste($nome)) {
            jsonResponse(['error' => 'Já existe um projeto com este nome'], 400);
        }
        
        // Preparar dados
        $data = [
            'nome' => $nome,
            'descricao' => $descricao,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'status' => $status
        ];
        
        // Adicionar projeto
        $id = $this->model->add($data);
        
        if ($id) {
            // Associar liderados selecionados
            if (isset($_POST['liderados']) && is_array($_POST['liderados'])) {
                foreach ($_POST['liderados'] as $lideradoId) {
                    $percentual = isset($_POST['percentual'][$lideradoId]) ? (int) $_POST['percentual'][$lideradoId] : 100;
                    $liderado = $this->lideradoModel->getById($lideradoId);
                    
                    if ($liderado) {
                        $this->lideradoModel->associarProjeto($lideradoId, $id, $percentual, $dataInicio);
                    }
                }
            }
            
            jsonResponse(['success' => true, 'message' => 'Projeto adicionado com sucesso', 'id' => $id]);
        } else {
            jsonResponse(['error' => 'Erro ao adicionar projeto'], 500);
        }
    }
    
    /**
     * Exibir formulário para editar projeto
     */
    public function edit($id = null) {
        // Verificar se ID foi fornecido
        if (!$id) {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        }
        
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin')) {
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        }
        
        $projeto = $this->model->getById($id);
        
        if (!$projeto) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Projeto não encontrado';
            header('Location: ' . BASE_URL . '/projetos.php');
            exit;
        }
        
        // Carregar liderados para a view
        $liderados = $this->lideradoModel->getAll();
        
        // Carregar a view
        include_once __DIR__ . '/../views/projetos/edit.php';
    }
    
    /**
     * Processar formulário de edição de projeto
     */
    public function update($id = null) {
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
        
        // Validar e sanitizar dados
        $nome = sanitizeInput($_POST['nome'] ?? '');
        $descricao = sanitizeInput($_POST['descricao'] ?? '');
        $dataInicio = sanitizeInput($_POST['data_inicio'] ?? '');
        $dataFim = sanitizeInput($_POST['data_fim'] ?? null);
        $status = sanitizeInput($_POST['status'] ?? 'Não iniciado');
        
        if (empty($nome) || empty($dataInicio)) {
            jsonResponse(['error' => 'Nome e Data de Início são obrigatórios'], 400);
        }
        
        // Verificar se já existe projeto com o mesmo nome (excluindo o atual)
        if ($this->model->nomeExiste($nome, $id)) {
            jsonResponse(['error' => 'Já existe outro projeto com este nome'], 400);
        }
        
        // Preparar dados
        $data = [
            'nome' => $nome,
            'descricao' => $descricao,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'status' => $status
        ];
        
        // Atualizar projeto
        $result = $this->model->update($id, $data);
        
        if ($result) {
            jsonResponse(['success' => true, 'message' => 'Projeto atualizado com sucesso']);
        } else {
            jsonResponse(['error' => 'Erro ao atualizar projeto'], 500);
        }
    }
    
    /**
     * Processar exclusão de projeto
     */
    public function delete($id = null) {
        // Verificar se ID foi fornecido
        if (!$id) {
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        }
        
        // Verificar permissão
        if (!hasPermission('admin')) {
            jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
        }
        
        // Verificar token CSRF
        verifyCsrfToken();
        
        // Validar ID
        if (!$id) {
            jsonResponse(['error' => 'ID inválido'], 400);
        }
        
        // Verificar se tem OPRs associados
        if ($this->model->temOPRs($id)) {
            jsonResponse(['error' => 'Este projeto possui OPRs associados e não pode ser excluído'], 400);
        }
        
        // Excluir projeto
        $result = $this->model->delete($id);
        
        if ($result) {
            jsonResponse(['success' => true, 'message' => 'Projeto excluído com sucesso']);
        } else {
            jsonResponse(['error' => 'Erro ao excluir projeto'], 500);
        }
    }
    
    /**
     * Listar membros de um projeto
     */
    public function membros($id = null) {
        // Verificar se ID foi fornecido
        if (!$id) {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        }
        
        // Verificar permissão
        if (!isLoggedIn()) {
            jsonResponse(['error' => 'Você precisa estar logado para acessar estes dados'], 401);
        }
        
        // Validar ID
        if (!$id) {
            jsonResponse(['error' => 'ID inválido'], 400);
        }
        
        $membros = $this->model->getMembros($id);
        
        jsonResponse(['success' => true, 'data' => $membros]);
    }
    
    /**
     * Listar atividades de um projeto
     */
    public function atividades($id = null) {
        // Verificar se ID foi fornecido
        if (!$id) {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        }
        
        // Verificar permissão
        if (!isLoggedIn()) {
            jsonResponse(['error' => 'Você precisa estar logado para acessar estes dados'], 401);
        }
        
        // Validar ID
        if (!$id) {
            jsonResponse(['error' => 'ID inválido'], 400);
        }
        
        // Obter status para filtro (se fornecido)
        $status = sanitizeInput($_GET['status'] ?? null);
        
        $atividades = $this->model->getAtividades($id, $status);
        
        jsonResponse(['success' => true, 'data' => $atividades]);
    }
    
    /**
     * Obter progresso de um projeto
     */
    public function progresso($id = null) {
        // Verificar se ID foi fornecido
        if (!$id) {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        }
        
        // Verificar permissão
        if (!isLoggedIn()) {
            jsonResponse(['error' => 'Você precisa estar logado para acessar estes dados'], 401);
        }
        
        // Validar ID
        if (!$id) {
            jsonResponse(['error' => 'ID inválido'], 400);
        }
        
        $progresso = $this->model->getProgresso($id);
        
        jsonResponse(['success' => true, 'data' => $progresso]);
    }
    
    /**
     * Atualizar status do projeto
     */
    public function atualizarStatus() {
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin')) {
            jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
        }
        
        // Verificar token CSRF
        verifyCsrfToken();
        
        // Validar dados
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $status = sanitizeInput($_POST['status'] ?? '');
        
        if (!$id || empty($status)) {
            jsonResponse(['error' => 'Todos os campos obrigatórios devem ser preenchidos'], 400);
        }
        
        // Atualizar status
        $projeto = $this->model->getById($id);
        
        if (!$projeto) {
            jsonResponse(['error' => 'Projeto não encontrado'], 404);
        }
        
        $data = [
            'nome' => $projeto['nome'],
            'descricao' => $projeto['descricao'],
            'data_inicio' => $projeto['data_inicio'],
            'data_fim' => $projeto['data_fim'],
            'status' => $status
        ];
        
        $result = $this->model->update($id, $data);
        
        if ($result) {
            jsonResponse(['success' => true, 'message' => 'Status do projeto atualizado com sucesso']);
        } else {
            jsonResponse(['error' => 'Erro ao atualizar status do projeto'], 500);
        }
    }
    
    /**
     * Gerar relatório do projeto
     */
    public function relatorio($id = null) {
        // Verificar se ID foi fornecido
        if (!$id) {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        }
        
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin')) {
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        }
        
        $projeto = $this->model->getById($id);
        
        if (!$projeto) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Projeto não encontrado';
            header('Location: ' . BASE_URL . '/projetos.php');
            exit;
        }
        
        // Obter dados adicionais para o relatório
        $membros = $projeto['membros'];
        $atividades = $projeto['atividades'];
        $estatisticas = $this->model->getEstatisticas($id);
        $horasPorLiderado = $this->model->getHorasPorLiderado($id);
        $horasPorAtividade = $this->model->getHorasPorAtividade($id);
        $progresso = $this->model->getProgresso($id);
        
        // Carregar a view
        include_once __DIR__ . '/../views/projetos/relatorio.php';
    }
}