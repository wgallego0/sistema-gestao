<?php
require_once __DIR__ . '/../config.php';

class Projeto {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Obter todos os projetos
     * 
     * @param bool $apenasAtivos Retornar apenas projetos ativos
     * @return array Lista de projetos
     */
    public function getAll($apenasAtivos = true) {
        try {
            $sql = "SELECT * FROM projetos";
            
            if ($apenasAtivos) {
                $sql .= " WHERE ativo = 1";
            }
            
            $sql .= " ORDER BY status, data_inicio DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            $projetos = $stmt->fetchAll();
            
            // Adicionar informações adicionais de cada projeto
            foreach ($projetos as &$projeto) {
                $projeto['total_membros'] = $this->getTotalMembros($projeto['id']);
                $projeto['total_atividades'] = $this->getTotalAtividades($projeto['id']);
                $projeto['total_horas'] = $this->getTotalHoras($projeto['id']);
            }
            
            return $projetos;
        } catch (PDOException $e) {
            logError('Erro ao obter projetos: ' . $e->getMessage());
            return [];
        }
    }
    /**
     * Buscar projetos por termo
     * 
     * @param string $termo Termo de busca
     * @return array Lista de projetos encontrados
     */
    public function buscarPorTermo($termo) {
        try {
            $termoBusca = '%' . $termo . '%';
            
            $stmt = $this->conn->prepare("
                SELECT * FROM projetos
                WHERE (nome LIKE ? OR descricao LIKE ?) AND ativo = 1
                ORDER BY nome
                LIMIT 20
            ");
            
            $stmt->execute([$termoBusca, $termoBusca]);
            
            $projetos = $stmt->fetchAll();
            
            // Adicionar informações adicionais de cada projeto
            foreach ($projetos as &$projeto) {
                $projeto['total_membros'] = $this->getTotalMembros($projeto['id']);
                $projeto['total_atividades'] = $this->getTotalAtividades($projeto['id']);
                $projeto['total_horas'] = $this->getTotalHoras($projeto['id']);
            }
            
            return $projetos;
        } catch (PDOException $e) {
            logError('Erro ao buscar projetos: ' . $e->getMessage());
            return [];
        }
    }
    /**
     * Obter projeto pelo ID
     * 
     * @param int $id ID do projeto
     * @return array|false Dados do projeto ou false se não encontrado
     */
    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM projetos WHERE id = ?");
            $stmt->execute([$id]);
            
            $projeto = $stmt->fetch();
            
            if ($projeto) {
                // Obter membros do projeto
                $projeto['membros'] = $this->getMembros($id);
                
                // Obter atividades do projeto
                $projeto['atividades'] = $this->getAtividades($id);
                
                // Calcular total de horas apontadas
                $projeto['total_horas'] = $this->getTotalHoras($id);
            }
            
            return $projeto;
        } catch (PDOException $e) {
            logError('Erro ao obter projeto por ID: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Adicionar novo projeto
     * 
     * @param array $data Dados do projeto
     * @return int|false ID do projeto inserido ou false em caso de erro
     */
    public function add($data) {
        try {
            $this->conn->beginTransaction();
            
            $stmt = $this->conn->prepare("
                INSERT INTO projetos (nome, descricao, data_inicio, data_fim, status)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['nome'],
                $data['descricao'],
                $data['data_inicio'],
                $data['data_fim'] ?? null,
                $data['status'] ?? 'Não iniciado'
            ]);
            
            $id = $this->conn->lastInsertId();
            
            // Registrar no log
            logActivity('projetos', $id, 'INSERT', null, $data);
            
            $this->conn->commit();
            
            return $id;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao adicionar projeto: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualizar projeto existente
     * 
     * @param int $id ID do projeto
     * @param array $data Novos dados do projeto
     * @return bool Sucesso ou falha na operação
     */
    public function update($id, $data) {
        try {
            $this->conn->beginTransaction();
            
            // Obter dados antigos para o log
            $oldData = $this->getById($id);
            
            if (!$oldData) {
                throw new Exception("Projeto não encontrado");
            }
            
            $stmt = $this->conn->prepare("
                UPDATE projetos 
                SET nome = ?, descricao = ?, data_inicio = ?, data_fim = ?, status = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $data['nome'],
                $data['descricao'],
                $data['data_inicio'],
                $data['data_fim'] ?? null,
                $data['status'],
                $id
            ]);
            
            // Registrar no log
            logActivity('projetos', $id, 'UPDATE', $oldData, $data);
            
            $this->conn->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao atualizar projeto: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Desativar um projeto
     * 
     * @param int $id ID do projeto
     * @return bool Sucesso ou falha na operação
     */
    public function delete($id) {
        try {
            $this->conn->beginTransaction();
            
            // Obter dados antigos para o log
            $oldData = $this->getById($id);
            
            if (!$oldData) {
                throw new Exception("Projeto não encontrado");
            }
            
            // Apenas desativar, não excluir definitivamente
            $stmt = $this->conn->prepare("UPDATE projetos SET ativo = 0 WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            // Registrar no log
            logActivity('projetos', $id, 'DELETE', $oldData, null);
            
            $this->conn->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao desativar projeto: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter membros de um projeto
     * 
     * @param int $projetoId ID do projeto
     * @return array Lista de membros do projeto
     */
    public function getMembros($projetoId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT lp.*, l.nome as liderado_nome, l.email as liderado_email, l.cargo as liderado_cargo
                FROM liderados_projetos lp
                JOIN liderados l ON lp.liderado_id = l.id
                WHERE lp.projeto_id = ? AND (lp.data_fim IS NULL OR lp.data_fim >= CURRENT_DATE())
                ORDER BY lp.percentual_dedicacao DESC
            ");
            
            $stmt->execute([$projetoId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter membros do projeto: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter quantidade total de membros de um projeto
     * 
     * @param int $projetoId ID do projeto
     * @return int Total de membros
     */
    public function getTotalMembros($projetoId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total
                FROM liderados_projetos
                WHERE projeto_id = ? AND (data_fim IS NULL OR data_fim >= CURRENT_DATE())
            ");
            
            $stmt->execute([$projetoId]);
            $result = $stmt->fetch();
            
            return $result ? (int) $result['total'] : 0;
        } catch (PDOException $e) {
            logError('Erro ao contar membros do projeto: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obter atividades de um projeto
     * 
     * @param int $projetoId ID do projeto
     * @param string $status Filtrar por status (opcional)
     * @return array Lista de atividades do projeto
     */
    public function getAtividades($projetoId, $status = null) {
        try {
            $sql = "
                SELECT a.*,
                    (SELECT GROUP_CONCAT(l.nome SEPARATOR ', ') 
                    FROM atividades_responsaveis ar
                    JOIN liderados l ON ar.liderado_id = l.id
                    WHERE ar.atividade_id = a.id) as responsaveis
                FROM atividades a
                WHERE a.projeto_id = ?
            ";
            
            $params = [$projetoId];
            
            if ($status) {
                $sql .= " AND a.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY a.prioridade, a.status, a.data_inicio";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter atividades do projeto: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter quantidade total de atividades de um projeto
     * 
     * @param int $projetoId ID do projeto
     * @return int Total de atividades
     */
    public function getTotalAtividades($projetoId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total
                FROM atividades
                WHERE projeto_id = ?
            ");
            
            $stmt->execute([$projetoId]);
            $result = $stmt->fetch();
            
            return $result ? (int) $result['total'] : 0;
        } catch (PDOException $e) {
            logError('Erro ao contar atividades do projeto: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obter total de horas apontadas em um projeto
     * 
     * @param int $projetoId ID do projeto
     * @param string $dataInicio Data inicial para filtro (opcional)
     * @param string $dataFim Data final para filtro (opcional)
     * @return float Total de horas
     */
    public function getTotalHoras($projetoId, $dataInicio = null, $dataFim = null) {
        try {
            $sql = "
                SELECT SUM(quantidade_horas) as total
                FROM apontamentos
                WHERE projeto_id = ?
            ";
            
            $params = [$projetoId];
            
            if ($dataInicio) {
                $sql .= " AND data >= ?";
                $params[] = $dataInicio;
            }
            
            if ($dataFim) {
                $sql .= " AND data <= ?";
                $params[] = $dataFim;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            
            return $result ? (float) $result['total'] : 0;
        } catch (PDOException $e) {
            logError('Erro ao calcular total de horas do projeto: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obter distribuição de horas por liderado no projeto
     * 
     * @param int $projetoId ID do projeto
     * @param string $dataInicio Data inicial para filtro (opcional)
     * @param string $dataFim Data final para filtro (opcional)
     * @return array Distribuição de horas por liderado
     */
    public function getHorasPorLiderado($projetoId, $dataInicio = null, $dataFim = null) {
        try {
            $sql = "
                SELECT l.nome as liderado, SUM(a.quantidade_horas) as horas
                FROM apontamentos a
                JOIN liderados l ON a.liderado_id = l.id
                WHERE a.projeto_id = ?
            ";
            
            $params = [$projetoId];
            
            if ($dataInicio) {
                $sql .= " AND a.data >= ?";
                $params[] = $dataInicio;
            }
            
            if ($dataFim) {
                $sql .= " AND a.data <= ?";
                $params[] = $dataFim;
            }
            
            $sql .= " GROUP BY a.liderado_id ORDER BY horas DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter horas por liderado: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter distribuição de horas por atividade no projeto
     * 
     * @param int $projetoId ID do projeto
     * @param string $dataInicio Data inicial para filtro (opcional)
     * @param string $dataFim Data final para filtro (opcional)
     * @return array Distribuição de horas por atividade
     */
    public function getHorasPorAtividade($projetoId, $dataInicio = null, $dataFim = null) {
        try {
            $sql = "
                SELECT COALESCE(at.titulo, 'Sem atividade específica') as atividade, 
                       SUM(a.quantidade_horas) as horas
                FROM apontamentos a
                LEFT JOIN atividades at ON a.atividade_id = at.id
                WHERE a.projeto_id = ?
            ";
            
            $params = [$projetoId];
            
            if ($dataInicio) {
                $sql .= " AND a.data >= ?";
                $params[] = $dataInicio;
            }
            
            if ($dataFim) {
                $sql .= " AND a.data <= ?";
                $params[] = $dataFim;
            }
            
            $sql .= " GROUP BY a.atividade_id ORDER BY horas DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter horas por atividade: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter progresso do projeto com base em atividades
     * 
     * @param int $projetoId ID do projeto
     * @return array Dados de progresso
     */
    public function getProgresso($projetoId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total_atividades,
                    SUM(CASE WHEN status = 'Concluída' THEN 1 ELSE 0 END) as atividades_concluidas,
                    SUM(CASE WHEN status = 'Em andamento' THEN 1 ELSE 0 END) as atividades_andamento,
                    SUM(CASE WHEN status = 'Não iniciada' THEN 1 ELSE 0 END) as atividades_nao_iniciadas,
                    SUM(CASE WHEN status = 'Bloqueada' THEN 1 ELSE 0 END) as atividades_bloqueadas
                FROM atividades
                WHERE projeto_id = ?
            ");
            
            $stmt->execute([$projetoId]);
            $result = $stmt->fetch();
            
            if ($result && $result['total_atividades'] > 0) {
                $result['percentual_concluido'] = round(($result['atividades_concluidas'] / $result['total_atividades']) * 100, 2);
            } else {
                $result['percentual_concluido'] = 0;
            }
            
            return $result;
        } catch (PDOException $e) {
            logError('Erro ao calcular progresso do projeto: ' . $e->getMessage());
            return [
                'total_atividades' => 0,
                'atividades_concluidas' => 0,
                'atividades_andamento' => 0,
                'atividades_nao_iniciadas' => 0,
                'atividades_bloqueadas' => 0,
                'percentual_concluido' => 0
            ];
        }
    }
    
    /**
     * Obter apontamentos recentes do projeto
     * 
     * @param int $projetoId ID do projeto
     * @param int $limite Limite de registros
     * @return array Lista de apontamentos
     */
    public function getApontamentosRecentes($projetoId, $limite = 10) {
        try {
            $stmt = $this->conn->prepare("
                SELECT a.*, 
                       l.nome as liderado_nome,
                       at.titulo as atividade_titulo,
                       at.status as atividade_status
                FROM apontamentos a
                JOIN liderados l ON a.liderado_id = l.id
                LEFT JOIN atividades at ON a.atividade_id = at.id
                WHERE a.projeto_id = ?
                ORDER BY a.data DESC, a.id DESC
                LIMIT ?
            ");
            
            $stmt->execute([$projetoId, $limite]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter apontamentos recentes: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verificar se o projeto tem OPRs associados
     * 
     * @param int $projetoId ID do projeto
     * @return bool Tem OPRs associados
     */
    public function temOPRs($projetoId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total
                FROM opr_mencoes_projetos
                WHERE projeto_id = ?
            ");
            
            $stmt->execute([$projetoId]);
            $result = $stmt->fetch();
            
            return $result && $result['total'] > 0;
        } catch (PDOException $e) {
            logError('Erro ao verificar OPRs do projeto: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter estatísticas do projeto
     * 
     * @param int $projetoId ID do projeto
     * @return array Estatísticas do projeto
     */
    public function getEstatisticas($projetoId) {
        try {
            $projeto = $this->getById($projetoId);
            
            if (!$projeto) {
                return [];
            }
            
            // Calcular duração em dias
            $dataInicio = new DateTime($projeto['data_inicio']);
            $dataFim = $projeto['data_fim'] ? new DateTime($projeto['data_fim']) : new DateTime();
            $duracao = $dataInicio->diff($dataFim)->days;
            
            // Calcular progresso geral
            $progresso = $this->getProgresso($projetoId);
            
            // Horas por liderado
            $horasPorLiderado = $this->getHorasPorLiderado($projetoId);
            
            // Horas por atividade
            $horasPorAtividade = $this->getHorasPorAtividade($projetoId);
            
            return [
                'duracao_dias' => $duracao,
                'progresso' => $progresso,
                'horas_por_liderado' => $horasPorLiderado,
                'horas_por_atividade' => $horasPorAtividade,
                'total_horas' => $projeto['total_horas'],
                'total_membros' => count($projeto['membros']),
                'total_atividades' => count($projeto['atividades'])
            ];
        } catch (PDOException $e) {
            logError('Erro ao obter estatísticas do projeto: ' . $e->getMessage());
            return [];
        }
    }
    public function getDistribuicaoPorStatus() {
        try {
            // Ajuste se o nome da tabela for diferente de "projetos"
            // ou se a coluna de status tiver outro nome.
            $sql = "SELECT status, COUNT(*) AS total
                    FROM projetos
                    GROUP BY status";
    
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter distribuição por status: ' . $e->getMessage());
            return [];
        }
    }
    /**
     * Verificar se um projeto com mesmo nome já existe
     * 
     * @param string $nome Nome do projeto
     * @param int $excluirId ID a excluir da verificação (para edição)
     * @return bool True se já existir um projeto com o mesmo nome
     */
    public function nomeExiste($nome, $excluirId = null) {
        try {
            $sql = "SELECT id FROM projetos WHERE nome = ? AND ativo = 1";
            $params = [$nome];
            
            if ($excluirId) {
                $sql .= " AND id != ?";
                $params[] = $excluirId;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            logError('Erro ao verificar nome do projeto: ' . $e->getMessage());
            return false;
        }
    }
}