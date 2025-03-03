<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Liderado.php';

class LideradosController {
    private $model;
    
    public function __construct() {
        $this->model = new Liderado();
    }
    
    /**
     * Listar todos os liderados
     */
    public function index() {
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin')) {
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        }
        
        $liderados = $this->model->getAll();
        
        // Carregar a view
        include_once __DIR__ . '/../views/liderados/index.php';
    }
    
    /**
     * Exibir detalhes de um liderado
     */
    public function view($id = null) {
        // Verificar se ID foi fornecido
        if (!$id) {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        }
        
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin') && 
            $_SESSION[SESSION_PREFIX . 'liderado_id'] != $id) {
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        }
        
        $liderado = $this->model->getById($id);
        
        if (!$liderado) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Liderado não encontrado';
            header('Location: ' . BASE_URL . '/liderados.php');
            exit;
        }
        
        // Obter estatísticas
        $estatisticas = $this->model->getEstatisticas($id);
        
        // Carregar a view
        include_once __DIR__ . '/../views/liderados/view.php';
    }
    
    /**
     * Exibir formulário para adicionar liderado
     */
    public function create() {
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin')) {
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        }
        
        // Carregar a view
        include_once __DIR__ . '/../views/liderados/create.php';
    }
    
    /**
     * Processar formulário de adição de liderado
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
        $email = sanitizeInput($_POST['email'] ?? '');
        $cargo = sanitizeInput($_POST['cargo'] ?? '');
        $crossFuncional = isset($_POST['cross_funcional']) ? 1 : 0;
        
        if (empty($nome) || empty($email) || empty($cargo)) {
            jsonResponse(['error' => 'Todos os campos obrigatórios devem ser preenchidos'], 400);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'Email inválido'], 400);
        }
        
        // Verificar se email já existe
        if ($this->model->emailExiste($email)) {
            jsonResponse(['error' => 'Este email já está cadastrado'], 400);
        }
        
        // Preparar dados
        $data = [
            'nome' => $nome,
            'email' => $email,
            'cargo' => $cargo,
            'cross_funcional' => $crossFuncional
        ];
        
        // Adicionar liderado
        $id = $this->model->add($data);
        
        if ($id) {
            jsonResponse(['success' => true, 'message' => 'Liderado adicionado com sucesso', 'id' => $id]);
        } else {
            jsonResponse(['error' => 'Erro ao adicionar liderado'], 500);
        }
    }
    
    /**
     * Exibir formulário para editar liderado
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
        
        $liderado = $this->model->getById($id);
        
        if (!$liderado) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Liderado não encontrado';
            header('Location: ' . BASE_URL . '/liderados.php');
            exit;
        }
        
        // Carregar a view
        include_once __DIR__ . '/../views/liderados/edit.php';
    }
    
    /**
     * Processar formulário de edição de liderado
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
        $email = sanitizeInput($_POST['email'] ?? '');
        $cargo = sanitizeInput($_POST['cargo'] ?? '');
        $crossFuncional = isset($_POST['cross_funcional']) ? 1 : 0;
        
        if (empty($nome) || empty($email) || empty($cargo)) {
            jsonResponse(['error' => 'Todos os campos obrigatórios devem ser preenchidos'], 400);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'Email inválido'], 400);
        }
        
        // Verificar se email já existe (excluindo o liderado atual)
        if ($this->model->emailExiste($email, $id)) {
            jsonResponse(['error' => 'Este email já está cadastrado para outro liderado'], 400);
        }
        
        // Preparar dados
        $data = [
            'nome' => $nome,
            'email' => $email,
            'cargo' => $cargo,
            'cross_funcional' => $crossFuncional
        ];
        
        // Atualizar liderado
        $result = $this->model->update($id, $data);
        
        if ($result) {
            jsonResponse(['success' => true, 'message' => 'Liderado atualizado com sucesso']);
        } else {
            jsonResponse(['error' => 'Erro ao atualizar liderado'], 500);
        }
    }
    
    /**
     * Processar exclusão de liderado
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
        
        // Excluir liderado
        $result = $this->model->delete($id);
        
        if ($result) {
            jsonResponse(['success' => true, 'message' => 'Liderado excluído com sucesso']);
        } else {
            jsonResponse(['error' => 'Erro ao excluir liderado'], 500);
        }
    }
    
    /**
     * Listar projetos de um liderado
     */
    public function projetos($id = null) {
        // Verificar se ID foi fornecido
        if (!$id) {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        }
        
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin') && 
            $_SESSION[SESSION_PREFIX . 'liderado_id'] != $id) {
            jsonResponse(['error' => 'Você não tem permissão para acessar estes dados'], 403);
        }
        
        // Validar ID
        if (!$id) {
            jsonResponse(['error' => 'ID inválido'], 400);
        }
        
        $projetos = $this->model->getProjetos($id);
        
        jsonResponse(['success' => true, 'data' => $projetos]);
    }
    
    /**
     * Associar liderado a um projeto
     */
    public function associarProjeto() {
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin')) {
            jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
        }
        
        // Verificar token CSRF
        verifyCsrfToken();
        
        // Validar e sanitizar dados
        $lideradoId = isset($_POST['liderado_id']) ? (int) $_POST['liderado_id'] : 0;
        $projetoId = isset($_POST['projeto_id']) ? (int) $_POST['projeto_id'] : 0;
        $percentual = isset($_POST['percentual']) ? (int) $_POST['percentual'] : 100;
        $dataInicio = sanitizeInput($_POST['data_inicio'] ?? date('Y-m-d'));
        
        if (!$lideradoId || !$projetoId) {
            jsonResponse(['error' => 'IDs inválidos'], 400);
        }
        
        if ($percentual < 1 || $percentual > 100) {
            jsonResponse(['error' => 'Percentual de dedicação deve estar entre 1 e 100'], 400);
        }
        
        // Associar ao projeto
        $result = $this->model->associarProjeto($lideradoId, $projetoId, $percentual, $dataInicio);
        
        if ($result) {
            jsonResponse(['success' => true, 'message' => 'Liderado associado ao projeto com sucesso']);
        } else {
            jsonResponse(['error' => 'Erro ao associar liderado ao projeto'], 500);
        }
    }
    
    /**
     * Remover liderado de um projeto
     */
    public function removerDoProjeto() {
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin')) {
            jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
        }
        
        // Verificar token CSRF
        verifyCsrfToken();
        
        // Validar e sanitizar dados
        $lideradoId = isset($_POST['liderado_id']) ? (int) $_POST['liderado_id'] : 0;
        $projetoId = isset($_POST['projeto_id']) ? (int) $_POST['projeto_id'] : 0;
        $dataFim = sanitizeInput($_POST['data_fim'] ?? date('Y-m-d'));
        
        if (!$lideradoId || !$projetoId) {
            jsonResponse(['error' => 'IDs inválidos'], 400);
        }
        
        // Remover do projeto
        $result = $this->model->removerDoProjeto($lideradoId, $projetoId, $dataFim);
        
        if ($result) {
            jsonResponse(['success' => true, 'message' => 'Liderado removido do projeto com sucesso']);
        } else {
            jsonResponse(['error' => 'Erro ao remover liderado do projeto'], 500);
        }
    }
    
    /**
     * Definir liderado como cross-funcional
     */
    public function setCrossFuncional() {
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin')) {
            jsonResponse(['error' => 'Você não tem permissão para realizar esta ação'], 403);
        }
        
        // Verificar token CSRF
        verifyCsrfToken();
        
        // Validar e sanitizar dados
        $lideradoId = isset($_POST['liderado_id']) ? (int) $_POST['liderado_id'] : 0;
        $isCross = isset($_POST['cross_funcional']) ? (bool) $_POST['cross_funcional'] : false;
        
        if (!$lideradoId) {
            jsonResponse(['error' => 'ID inválido'], 400);
        }
        
        // Definir status cross-funcional
        $result = $this->model->setCrossFuncional($lideradoId, $isCross);
        
        if ($result) {
            jsonResponse(['success' => true, 'message' => 'Status cross-funcional atualizado com sucesso']);
        } else {
            jsonResponse(['error' => 'Erro ao atualizar status cross-funcional'], 500);
        }
    }
    
    /**
     * Listar apontamentos de um liderado
     */
    public function apontamentos($id = null) {
        // Verificar se ID foi fornecido
        if (!$id) {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        }
        
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin') && 
            $_SESSION[SESSION_PREFIX . 'liderado_id'] != $id) {
            jsonResponse(['error' => 'Você não tem permissão para acessar estes dados'], 403);
        }
        
        // Validar ID
        if (!$id) {
            jsonResponse(['error' => 'ID inválido'], 400);
        }
        
        // Validar e sanitizar datas (se fornecidas)
        $dataInicio = sanitizeInput($_GET['data_inicio'] ?? null);
        $dataFim = sanitizeInput($_GET['data_fim'] ?? null);
        
        $apontamentos = $this->model->getApontamentos($id, $dataInicio, $dataFim);
        
        jsonResponse(['success' => true, 'data' => $apontamentos]);
    }
    
    /**
     * Listar OPRs de um liderado
     */
    public function oprs($id = null) {
        // Verificar se ID foi fornecido
        if (!$id) {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        }
        
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin') && 
            $_SESSION[SESSION_PREFIX . 'liderado_id'] != $id) {
            jsonResponse(['error' => 'Você não tem permissão para acessar estes dados'], 403);
        }
        
        // Validar ID
        if (!$id) {
            jsonResponse(['error' => 'ID inválido'], 400);
        }
        
        $oprs = $this->model->getOPRs($id);
        
        jsonResponse(['success' => true, 'data' => $oprs]);
    }
    
    /**
     * Buscar liderados por nome ou email
     */
    public function buscar() {
        // Verificar permissão
        if (!isLoggedIn()) {
            jsonResponse(['error' => 'Você não tem permissão para acessar estes dados'], 403);
        }
        
        $termo = sanitizeInput($_GET['q'] ?? '');
        
        if (strlen($termo) < 2) {
            jsonResponse(['success' => true, 'data' => []]);
        }
        
        // Implementar busca no modelo
        $liderados = $this->model->buscarPorTermo($termo);
        
        jsonResponse(['success' => true, 'data' => $liderados]);
    }
    
    /**
     * Obter estatísticas de um liderado
     */
    public function estatisticas($id = null) {
        // Verificar se ID foi fornecido
        if (!$id) {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        }
        
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin') && 
            $_SESSION[SESSION_PREFIX . 'liderado_id'] != $id) {
            jsonResponse(['error' => 'Você não tem permissão para acessar estes dados'], 403);
        }
        
        // Validar ID
        if (!$id) {
            jsonResponse(['error' => 'ID inválido'], 400);
        }
        
        $estatisticas = $this->model->getEstatisticas($id);
        
        jsonResponse(['success' => true, 'data' => $estatisticas]);
    }
}