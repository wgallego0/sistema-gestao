<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/OPR.php';
require_once __DIR__ . '/../models/Liderado.php';
require_once __DIR__ . '/../models/Projeto.php';
require_once __DIR__ . '/../models/Atividade.php';
require_once __DIR__ . '/../models/Apontamento.php';

class OPRController {
    private $model;
    private $lideradoModel;
    private $projetoModel;
    private $atividadeModel;
    private $apontamentoModel;
    
    public function __construct() {
        $this->model = new OPR();
        $this->lideradoModel = new Liderado();
        $this->projetoModel = new Projeto();
        $this->atividadeModel = new Atividade();
        $this->apontamentoModel = new Apontamento();
    }
    
    /**
     * Listar todos os OPRs
     */
    public function index() {
        // Verificar permissão
        if (!isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
        
        // Obter filtragem por liderado, se houver
        $lideradoId = null;
        
        // Se for liderado comum, mostrar apenas seus OPRs
        if (!hasPermission('gestor') && !hasPermission('admin')) {
            $lideradoId = $_SESSION[SESSION_PREFIX . 'liderado_id'];
        } else if (isset($_GET['liderado_id']) && $_GET['liderado_id']) {
            // Se for gestor/admin e houver filtro, usar o filtro
            $lideradoId = (int) $_GET['liderado_id'];
        }
        
        $oprs = $this->model->getAll($lideradoId);
        
        // Obter lista de liderados para filtro (apenas para gestores/admin)
        $liderados = [];
        if (hasPermission('gestor') || hasPermission('admin')) {
            $liderados = $this->lideradoModel->getAll();
        }
        
        // Carregar a view
        include_once __DIR__ . '/../views/oprs/index.php';
    }
    
    /**
     * Exibir detalhes de um OPR
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
        
        $opr = $this->model->getById($id);
        
        if (!$opr) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'OPR não encontrado';
            header('Location: ' . BASE_URL . '/oprs.php');
            exit;
        }
        
        // Verificar se é o próprio liderado ou gestor/admin
        if (!hasPermission('gestor') && !hasPermission('admin') && 
            $_SESSION[SESSION_PREFIX . 'liderado_id'] != $opr['liderado_id']) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Você não tem permissão para visualizar este OPR';
            header('Location: ' . BASE_URL . '/oprs.php');
            exit;
        }
        
        // Formatar dados para exibição
        $relatorio = $this->model->gerarRelatorio($id);
        
        // Carregar a view
        include_once __DIR__ . '/../views/oprs/view.php';
    }
    
    /**
     * Exibir OPR em formato para impressão
     */
    public function imprimir($id = null) {
        // Verificar se ID foi fornecido
        if (!$id) {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        }
        
        // Verificar permissão
        if (!isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
        
        $opr = $this->model->getById($id);
        
        if (!$opr) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'OPR não encontrado';
            header('Location: ' . BASE_URL . '/oprs.php');
            exit;
        }
        
        // Verificar se é o próprio liderado ou gestor/admin
        if (!hasPermission('gestor') && !hasPermission('admin') && 
            $_SESSION[SESSION_PREFIX . 'liderado_id'] != $opr['liderado_id']) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Você não tem permissão para visualizar este OPR';
            header('Location: ' . BASE_URL . '/oprs.php');
            exit;
        }
        
        // Formatar dados para exibição
        $relatorio = $this->model->gerarRelatorio($id);
        
        // Carregar a view de impressão
        include_once __DIR__ . '/../views/oprs/print.php';
    }
    
    /**
     * Exibir formulário para adicionar OPR
     */
    public function create() {
        // Verificar permissão
        if (!isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
        
        // Definir liderado padrão
        $lideradoId = $_SESSION[SESSION_PREFIX . 'liderado_id'];
        
        // Para gestores/admin, permitir escolher o liderado
        $liderados = [];
        if (hasPermission('gestor') || hasPermission('admin')) {
            $liderados = $this->lideradoModel->getAll();
            
            // Se o parâmetro liderado_id for passado, usá-lo
            if (isset($_GET['liderado_id']) && $_GET['liderado_id']) {
                $lideradoId = (int) $_GET['liderado_id'];
            }
        }
        
        // Obter liderado
        $liderado = $this->lideradoModel->getById($lideradoId);
        
        if (!$liderado) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Liderado não encontrado';
            header('Location: ' . BASE_URL . '/oprs.php');
            exit;
        }
        
        // Obter projetos do liderado
        $projetos = $liderado['projetos'];
        
        // Se for cross-funcional, obter todos os projetos ativos
        if ($liderado['cross_funcional']) {
            $todosProjetos = $this->projetoModel->getAll();
            // Adicionar projetos que ainda não estão na lista
            foreach ($todosProjetos as $projeto) {
                $encontrado = false;
                foreach ($projetos as $p) {
                    if ($p['projeto_id'] == $projeto['id']) {
                        $encontrado = true;
                        break;
                    }
                }
                if (!$encontrado) {
                    $projetos[] = [
                        'projeto_id' => $projeto['id'],
                        'projeto_nome' => $projeto['nome'],
                        'percentual_dedicacao' => 0
                    ];
                }
            }
        }
        
        // Obter apontamentos da semana atual
        $apontamentosSemana = $this->apontamentoModel->getApontamentosSemana($lideradoId);
        
        // Sugerir semana
        $semana = $this->model->getSugestaoSemana();
        
        // Carregar a view
        include_once __DIR__ . '/../views/oprs/create.php';
    }
    
    /**
     * Processar formulário de adição de OPR
     */
    public function store() {
        // Verificar permissão
        if (!isLoggedIn()) {
            jsonResponse(['error' => 'Você precisa estar logado para realizar esta ação'], 401);
        }
        
        // Verificar token CSRF
        verifyCsrfToken();
        
        // Validar e sanitizar dados
        $lideradoId = isset($_POST['liderado_id']) ? (int) $_POST['liderado_id'] : 0;
        $semana = sanitizeInput($_POST['semana'] ?? '');
        $status = sanitizeInput($_POST['status'] ?? 'Rascunho');
        
        // Verificar se é o próprio liderado ou gestor/admin
        if (!hasPermission('gestor') && !hasPermission('admin') && 
            $_SESSION[SESSION_PREFIX . 'liderado_id'] != $lideradoId) {
            jsonResponse(['error' => 'Você não tem permissão para criar OPR para este liderado'], 403);
        }
        
        if (!$lideradoId || empty($semana)) {
            jsonResponse(['error' => 'Todos os campos obrigatórios devem ser preenchidos'], 400);
        }
        
        // Verificar se já existe OPR para o liderado na semana
        if ($this->model->existeNaSemana($lideradoId, $semana)) {
            jsonResponse(['error' => 'Já existe um OPR para este liderado nesta semana'], 400);
        }
        
        // Preparar dados do OPR
        $oprData = [
            'liderado_id' => $lideradoId,
            'semana' => $semana,
            'status' => $status
        ];
        
        // Processar clientes atendidos
        $oprData['clientes'] = [];
        if (isset($_POST['clientes']) && is_array($_POST['clientes'])) {
            foreach ($_POST['clientes'] as $cliente) {
                if (!empty($cliente['cliente'])) {
                    $oprData['clientes'][] = [
                        'cliente' => sanitizeInput($cliente['cliente']),
                        'descricao' => sanitizeInput($cliente['descricao'] ?? '')
                    ];
                }
            }
        }
        
        // Processar atividades realizadas
        $oprData['atividades_realizadas'] = [];
        if (isset($_POST['atividades']) && is_array($_POST['atividades'])) {
            foreach ($_POST['atividades'] as $atividade) {
                if (!empty($atividade['descricao'])) {
                    $oprData['atividades_realizadas'][] = [
                        'atividade_id' => !empty($atividade['atividade_id']) ? (int) $atividade['atividade_id'] : null,
                        'descricao' => sanitizeInput($atividade['descricao']),
                        'resultado' => sanitizeInput($atividade['resultado'] ?? '')
                    ];
                }
            }
        }
        
        // Processar próximas atividades
        $oprData['proximas_atividades'] = [];
        if (isset($_POST['proximas']) && is_array($_POST['proximas'])) {
            foreach ($_POST['proximas'] as $proxima) {
                if (!empty($proxima['descricao'])) {
                    $oprData['proximas_atividades'][] = [
                        'descricao' => sanitizeInput($proxima['descricao']),
                        'data_limite' => sanitizeInput($proxima['data_limite'] ?? null),
                        'prioridade' => sanitizeInput($proxima['prioridade'] ?? 'Média')
                    ];
                }
            }
        }
        
        // Processar riscos
        $oprData['riscos'] = [];
        if (isset($_POST['riscos']) && is_array($_POST['riscos'])) {
            foreach ($_POST['riscos'] as $risco) {
                if (!empty($risco['descricao'])) {
                    $oprData['riscos'][] = [
                        'descricao' => sanitizeInput($risco['descricao']),
                        'impacto' => sanitizeInput($risco['impacto'] ?? 'Médio'),
                        'probabilidade' => sanitizeInput($risco['probabilidade'] ?? 'Média'),
                        'mitigacao' => sanitizeInput($risco['mitigacao'] ?? '')
                    ];
                }
            }
        }
        
        // Processar menções de projetos
        $oprData['mencoes_projetos'] = [];
        if (isset($_POST['mencoes']) && is_array($_POST['mencoes'])) {
            foreach ($_POST['mencoes'] as $mencao) {
                if (!empty($mencao['descricao'])) {
                    $oprData['mencoes_projetos'][] = [
                        'projeto_id' => !empty($mencao['projeto_id']) ? (int) $mencao['projeto_id'] : null,
                        'descricao' => sanitizeInput($mencao['descricao']),
                        'destaque' => isset($mencao['destaque']) ? 1 : 0
                    ];
                }
            }
        }
        
        // Processar apontamentos
        $oprData['apontamentos'] = [];
        if (isset($_POST['apontamentos']) && is_array($_POST['apontamentos'])) {
            foreach ($_POST['apontamentos'] as $apontamento) {
                if (!empty($apontamento['data']) && !empty($apontamento['quantidade_horas'])) {
                    $oprData['apontamentos'][] = [
                        'id' => !empty($apontamento['id']) ? (int) $apontamento['id'] : null,
                        'data' => sanitizeInput($apontamento['data']),
                        'projeto_id' => !empty($apontamento['projeto_id']) ? (int) $apontamento['projeto_id'] : null,
                        'atividade_id' => !empty($apontamento['atividade_id']) ? (int) $apontamento['atividade_id'] : null,
                        'quantidade_horas' => (float) $apontamento['quantidade_horas'],
                        'descricao' => sanitizeInput($apontamento['descricao'] ?? '')
                    ];
                }
            }
        }
        
        // Adicionar OPR
        $id = $this->model->add($oprData);
        
        if ($id) {
            jsonResponse(['success' => true, 'message' => 'OPR criado com sucesso', 'id' => $id]);
        } else {
            jsonResponse(['error' => 'Erro ao criar OPR'], 500);
        }
    }
    
    /**
     * Exibir formulário para editar OPR
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
        
        $opr = $this->model->getById($id);
        
        if (!$opr) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'OPR não encontrado';
            header('Location: ' . BASE_URL . '/oprs.php');
            exit;
        }
        
        // Verificar se é o próprio liderado ou gestor/admin
        if (!hasPermission('gestor') && !hasPermission('admin') && 
            $_SESSION[SESSION_PREFIX . 'liderado_id'] != $opr['liderado_id']) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Você não tem permissão para editar este OPR';
            header('Location: ' . BASE_URL . '/oprs.php');
            exit;
        }
        
        // Verificar se o OPR ainda pode ser editado (não está aprovado)
        if ($opr['status'] === 'Aprovado' && !hasPermission('admin')) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Este OPR já foi aprovado e não pode ser editado';
            header('Location: ' . BASE_URL . '/oprs.php?action=view&id=' . $id);
            exit;
        }
        
        // Obter liderado
        $liderado = $this->lideradoModel->getById($opr['liderado_id']);
        
        // Obter projetos do liderado
        $projetos = $liderado['projetos'];
        
        // Se for cross-funcional, obter todos os projetos ativos
        if ($liderado['cross_funcional']) {
            $todosProjetos = $this->projetoModel->getAll();
            // Adicionar projetos que ainda não estão na lista
            foreach ($todosProjetos as $projeto) {
                $encontrado = false;
                foreach ($projetos as $p) {
                    if ($p['projeto_id'] == $projeto['id']) {
                        $encontrado = true;
                        break;
                    }
                }
                if (!$encontrado) {
                    $projetos[] = [
                        'projeto_id' => $projeto['id'],
                        'projeto_nome' => $projeto['nome'],
                        'percentual_dedicacao' => 0
                    ];
                }
            }
        }
        
        // Carregar a view
        include_once __DIR__ . '/../views/oprs/edit.php';
    }
    
    /**
     * Processar formulário de edição de OPR
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
        
        // Obter OPR atual
        $opr = $this->model->getById($id);
        
        if (!$opr) {
            jsonResponse(['error' => 'OPR não encontrado'], 404);
        }
        
        // Verificar se é o próprio liderado ou gestor/admin
        if (!hasPermission('gestor') && !hasPermission('admin') && 
            $_SESSION[SESSION_PREFIX . 'liderado_id'] != $opr['liderado_id']) {
            jsonResponse(['error' => 'Você não tem permissão para editar este OPR'], 403);
        }
        
        // Verificar se o OPR ainda pode ser editado (não está aprovado)
        if ($opr['status'] === 'Aprovado' && !hasPermission('admin')) {
            jsonResponse(['error' => 'Este OPR já foi aprovado e não pode ser editado'], 403);
        }
        
        // Validar e sanitizar dados
        $semana = sanitizeInput($_POST['semana'] ?? '');
        $status = sanitizeInput($_POST['status'] ?? 'Rascunho');
        
        if (empty($semana)) {
            jsonResponse(['error' => 'Todos os campos obrigatórios devem ser preenchidos'], 400);
        }
        
        // Verificar se já existe OPR para o liderado na semana (excluindo o atual)
        if ($this->model->existeNaSemana($opr['liderado_id'], $semana, $id)) {
            jsonResponse(['error' => 'Já existe outro OPR para este liderado nesta semana'], 400);
        }
        
        // Preparar dados do OPR
        $oprData = [
            'semana' => $semana,
            'status' => $status
        ];
        
        // Processar clientes atendidos
        $oprData['clientes'] = [];
        if (isset($_POST['clientes']) && is_array($_POST['clientes'])) {
            foreach ($_POST['clientes'] as $cliente) {
                if (!empty($cliente['cliente'])) {
                    $oprData['clientes'][] = [
                        'cliente' => sanitizeInput($cliente['cliente']),
                        'descricao' => sanitizeInput($cliente['descricao'] ?? '')
                    ];
                }
            }
        }
        
        // Processar atividades realizadas
        $oprData['atividades_realizadas'] = [];
        if (isset($_POST['atividades']) && is_array($_POST['atividades'])) {
            foreach ($_POST['atividades'] as $atividade) {
                if (!empty($atividade['descricao'])) {
                    $oprData['atividades_realizadas'][] = [
                        'atividade_id' => !empty($atividade['atividade_id']) ? (int) $atividade['atividade_id'] : null,
                        'descricao' => sanitizeInput($atividade['descricao']),
                        'resultado' => sanitizeInput($atividade['resultado'] ?? '')
                    ];
                }
            }
        }
        
        // Processar próximas atividades
        $oprData['proximas_atividades'] = [];
        if (isset($_POST['proximas']) && is_array($_POST['proximas'])) {
            foreach ($_POST['proximas'] as $proxima) {
                if (!empty($proxima['descricao'])) {
                    $oprData['proximas_atividades'][] = [
                        'descricao' => sanitizeInput($proxima['descricao']),
                        'data_limite' => sanitizeInput($proxima['data_limite'] ?? null),
                        'prioridade' => sanitizeInput($proxima['prioridade'] ?? 'Média')
                    ];
                }
            }
        }
        
        // Processar riscos
        $oprData['riscos'] = [];
        if (isset($_POST['riscos']) && is_array($_POST['riscos'])) {
            foreach ($_POST['riscos'] as $risco) {
                if (!empty($risco['descricao'])) {
                    $oprData['riscos'][] = [
                        'descricao' => sanitizeInput($risco['descricao']),
                        'impacto' => sanitizeInput($risco['impacto'] ?? 'Médio'),
                        'probabilidade' => sanitizeInput($risco['probabilidade'] ?? 'Média'),
                        'mitigacao' => sanitizeInput($risco['mitigacao'] ?? '')
                    ];
                }
            }
        }
        
        // Processar menções de projetos
        $oprData['mencoes_projetos'] = [];
        if (isset($_POST['mencoes']) && is_array($_POST['mencoes'])) {
            foreach ($_POST['mencoes'] as $mencao) {
                if (!empty($mencao['descricao'])) {
                    $oprData['mencoes_projetos'][] = [
                        'projeto_id' => !empty($mencao['projeto_id']) ? (int) $mencao['projeto_id'] : null,
                        'descricao' => sanitizeInput($mencao['descricao']),
                        'destaque' => isset($mencao['destaque']) ? 1 : 0
                    ];
                }
            }
        }
        
        // Processar apontamentos
        $oprData['apontamentos'] = [];
        if (isset($_POST['apontamentos']) && is_array($_POST['apontamentos'])) {
            foreach ($_POST['apontamentos'] as $apontamento) {
                if (!empty($apontamento['data']) && !empty($apontamento['quantidade_horas'])) {
                    $oprData['apontamentos'][] = [
                        'id' => !empty($apontamento['id']) ? (int) $apontamento['id'] : null,
                        'data' => sanitizeInput($apontamento['data']),
                        'projeto_id' => !empty($apontamento['projeto_id']) ? (int) $apontamento['projeto_id'] : null,
                        'atividade_id' => !empty($apontamento['atividade_id']) ? (int) $apontamento['atividade_id'] : null,
                        'quantidade_horas' => (float) $apontamento['quantidade_horas'],
                        'descricao' => sanitizeInput($apontamento['descricao'] ?? '')
                    ];
                }
            }
        }
        
        // Atualizar OPR
        $result = $this->model->update($id, $oprData);
        
        if ($result) {
            jsonResponse(['success' => true, 'message' => 'OPR atualizado com sucesso']);
        } else {
            jsonResponse(['error' => 'Erro ao atualizar OPR'], 500);
        }
    }
    
    /**
     * Processar exclusão de OPR
     */
    public function delete($id = null) {
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
        
        // Obter OPR atual
        $opr = $this->model->getById($id);
        
        if (!$opr) {
            jsonResponse(['error' => 'OPR não encontrado'], 404);
        }
        
        // Verificar permissão (apenas admin pode excluir, ou o próprio liderado se estiver em rascunho)
        if (!hasPermission('admin') && 
            ($_SESSION[SESSION_PREFIX . 'liderado_id'] != $opr['liderado_id'] || $opr['status'] !== 'Rascunho')) {
            jsonResponse(['error' => 'Você não tem permissão para excluir este OPR'], 403);
        }
        
        // Excluir OPR
        $result = $this->model->delete($id);
        
        if ($result) {
            jsonResponse(['success' => true, 'message' => 'OPR excluído com sucesso']);
        } else {
            jsonResponse(['error' => 'Erro ao excluir OPR'], 500);
        }
    }
    
    /**
     * Atualizar status do OPR
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
        
        // Obter OPR atual
        $opr = $this->model->getById($id);
        
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
        $result = $this->model->atualizarStatus($id, $status);
        
        if ($result) {
            jsonResponse(['success' => true, 'message' => 'Status do OPR atualizado com sucesso']);
        } else {
            jsonResponse(['error' => 'Erro ao atualizar status do OPR'], 500);
        }
    }
    
    /**
     * Listar OPRs pendentes de aprovação
     */
    public function pendentes() {
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin')) {
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        }
        
        $pendentes = $this->model->getPendentesAprovacao();
        
        // Carregar a view
        include_once __DIR__ . '/../views/oprs/pendentes.php';
    }
    
    /**
     * Obter estatísticas de OPRs
     */
    public function estatisticas() {
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin')) {
            jsonResponse(['error' => 'Você não tem permissão para acessar estes dados'], 403);
        }
        
        // Validar e sanitizar datas (se fornecidas)
        $dataInicio = sanitizeInput($_GET['data_inicio'] ?? null);
        $dataFim = sanitizeInput($_GET['data_fim'] ?? null);
        
        $estatisticas = $this->model->getEstatisticas($dataInicio, $dataFim);
        
        jsonResponse(['success' => true, 'data' => $estatisticas]);
    }
}