<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Liderado.php';
require_once __DIR__ . '/../models/Projeto.php';
require_once __DIR__ . '/../models/Atividade.php';
require_once __DIR__ . '/../models/Apontamento.php';
require_once __DIR__ . '/../models/OPR.php';

class DashboardController {
    private $lideradoModel;
    private $projetoModel;
    private $atividadeModel;
    private $apontamentoModel;
    private $oprModel;
    
    public function __construct() {
        $this->lideradoModel = new Liderado();
        $this->projetoModel = new Projeto();
        $this->atividadeModel = new Atividade();
        $this->apontamentoModel = new Apontamento();
        $this->oprModel = new OPR();
    }
    
    /**
     * Exibir dashboard principal
     */
    public function index() {
        // Verificar permissão
        if (!isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
        
        // Obter dados para o dashboard
        $dados = $this->getDadosDashboard();
        
        // Carregar a view
        include_once __DIR__ . '/../views/dashboard/index.php';
    }
    
    /**
     * Obter dados para o dashboard
     * Diferentes visões para liderados, gestores e admin
     */
    private function getDadosDashboard() {
        $dados = [];
        
        // Dados básicos para todos os tipos de usuário
        $dados['total_liderados'] = 0;
        $dados['total_projetos'] = 0;
        $dados['total_atividades'] = 0;
        $dados['atividades_recentes'] = [];
        $dados['apontamentos_recentes'] = [];
        $dados['atividades_pendentes'] = [];
        $dados['horas_por_projeto'] = [];
        $dados['horas_por_liderado'] = [];
        $dados['projetos_por_status'] = [];
        $dados['oprs_recentes'] = [];
        
        // Visão específica para liderado comum
        if (!hasPermission('gestor') && !hasPermission('admin')) {
            $lideradoId = $_SESSION[SESSION_PREFIX . 'liderado_id'];
            $liderado = $this->lideradoModel->getById($lideradoId);
            
            if ($liderado) {
                $dados['liderado'] = $liderado;
                $dados['projetos'] = $liderado['projetos'];
                $dados['total_projetos'] = count($liderado['projetos']);
                
                // Obter atividades atribuídas ao liderado
                $dados['atividades'] = $this->atividadeModel->getAtividadesPorLiderado($lideradoId);
                $dados['total_atividades'] = count($dados['atividades']);
                
                // Obter atividades pendentes (não concluídas)
                $dados['atividades_pendentes'] = array_filter($dados['atividades'], function($atividade) {
                    return $atividade['status'] !== 'Concluída';
                });
                
                // Obter apontamentos recentes
                $dados['apontamentos_recentes'] = $this->apontamentoModel->getRecentes(10, $lideradoId);
                
                // Obter horas por projeto
                $dados['horas_por_projeto'] = $this->apontamentoModel->getHorasPorProjeto(
                    date('Y-m-d', strtotime('-30 days')),
                    date('Y-m-d'),
                    $lideradoId
                );
                
                // Obter OPRs do liderado
                $dados['oprs'] = $this->oprModel->getAll($lideradoId);
                
                // Verificar se há OPR para a semana atual
                $semanaAtual = $this->oprModel->getSugestaoSemana();
                $dados['tem_opr_semana_atual'] = $this->oprModel->existeNaSemana($lideradoId, $semanaAtual);
                
                // Obter estatísticas do liderado
                $dados['estatisticas'] = $this->lideradoModel->getEstatisticas($lideradoId);
            }
        }
        // Visão para gestor e admin
        else {
            // Obter totais
            $dados['total_liderados'] = count($this->lideradoModel->getAll());
            $dados['total_projetos'] = count($this->projetoModel->getAll());
            $dados['total_atividades'] = $this->atividadeModel->getTotalAtividades();
            
            // Obter projetos ativos
            $dados['projetos'] = $this->projetoModel->getAll();
            
            // Obter distribuição de projetos por status
            $dados['projetos_por_status'] = $this->projetoModel->getDistribuicaoPorStatus();
            
            // Obter atividades recentes
            $dados['atividades_recentes'] = $this->atividadeModel->getRecentes(10);
            
            // Obter apontamentos recentes
            $dados['apontamentos_recentes'] = $this->apontamentoModel->getRecentes(10);
            
            // Obter horas por projeto
            $dados['horas_por_projeto'] = $this->apontamentoModel->getHorasPorProjeto(
                date('Y-m-d', strtotime('-30 days')),
                date('Y-m-d')
            );
            
            // Obter horas por liderado
            $dados['horas_por_liderado'] = $this->apontamentoModel->getHorasPorLiderado(
                date('Y-m-d', strtotime('-30 days')),
                date('Y-m-d')
            );
            
            // Obter OPRs recentes
            $dados['oprs_recentes'] = $this->oprModel->getAll(null, 10);
            
            // Obter OPRs pendentes de aprovação
            $dados['oprs_pendentes'] = $this->oprModel->getPendentesAprovacao();
            
            // Obtém estatísticas gerais
            $dados['estatisticas'] = [
                'apontamentos' => $this->apontamentoModel->getEstatisticas(
                    date('Y-m-d', strtotime('-30 days')),
                    date('Y-m-d')
                ),
                'oprs' => $this->oprModel->getEstatisticas(
                    date('Y-m-d', strtotime('-30 days')),
                    date('Y-m-d')
                )
            ];
        }
        
        return $dados;
    }
    
    /**
     * Carregar dados para o gráfico de horas
     */
    public function graficoHoras() {
        // Verificar permissão
        if (!isLoggedIn()) {
            jsonResponse(['error' => 'Você precisa estar logado para acessar estes dados'], 401);
        }
        
        // Validar e sanitizar datas (se fornecidas)
        $dataInicio = sanitizeInput($_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days')));
        $dataFim = sanitizeInput($_GET['data_fim'] ?? date('Y-m-d'));
        
        // Obter dados específicos com base no tipo de usuário
        if (!hasPermission('gestor') && !hasPermission('admin')) {
            $lideradoId = $_SESSION[SESSION_PREFIX . 'liderado_id'];
            $horasPorProjeto = $this->apontamentoModel->getHorasPorProjeto($dataInicio, $dataFim, $lideradoId);
            $horasPorDia = $this->apontamentoModel->getHorasPorDia($dataInicio, $dataFim, $lideradoId);
        } else {
            $horasPorProjeto = $this->apontamentoModel->getHorasPorProjeto($dataInicio, $dataFim);
            $horasPorDia = $this->apontamentoModel->getHorasPorDia($dataInicio, $dataFim);
            $horasPorLiderado = $this->apontamentoModel->getHorasPorLiderado($dataInicio, $dataFim);
        }
        
        $dados = [
            'horas_por_projeto' => $horasPorProjeto,
            'horas_por_dia' => $horasPorDia
        ];
        
        if (hasPermission('gestor') || hasPermission('admin')) {
            $dados['horas_por_liderado'] = $horasPorLiderado;
        }
        
        jsonResponse(['success' => true, 'data' => $dados]);
    }
    
    /**
     * Carregar estatísticas gerais
     */
    public function estatisticas() {
        // Verificar permissão
        if (!hasPermission('gestor') && !hasPermission('admin')) {
            jsonResponse(['error' => 'Você não tem permissão para acessar estes dados'], 403);
        }
        
        // Validar e sanitizar datas (se fornecidas)
        $dataInicio = sanitizeInput($_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days')));
        $dataFim = sanitizeInput($_GET['data_fim'] ?? date('Y-m-d'));
        
        $estatisticas = [
            'apontamentos' => $this->apontamentoModel->getEstatisticas($dataInicio, $dataFim),
            'oprs' => $this->oprModel->getEstatisticas($dataInicio, $dataFim),
            'projetos' => [
                'distribuicao_status' => $this->projetoModel->getDistribuicaoPorStatus(),
                'total' => count($this->projetoModel->getAll())
            ],
            'liderados' => [
                'total' => count($this->lideradoModel->getAll()),
                'distribuicao_projetos' => $this->lideradoModel->getDistribuicaoPorProjeto()
            ]
        ];
        
        jsonResponse(['success' => true, 'data' => $estatisticas]);
    }
}