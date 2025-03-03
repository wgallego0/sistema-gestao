<?php
namespace Models;
require_once __DIR__ . '/../config.php';

class Liderado {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Obter todos os liderados
     * 
     * @param bool $apenasAtivos Retornar apenas liderados ativos
     * @return array Lista de liderados
     */
    public function getAll($apenasAtivos = true) {
        try {
            $sql = "SELECT * FROM liderados";
            
            if ($apenasAtivos) {
                $sql .= " WHERE ativo = 1";
            }
            
            $sql .= " ORDER BY nome";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter liderados: ' . $e->getMessage());
            return [];
        }
    }
    /**
     * Buscar liderados por termo
     * 
     * @param string $termo Termo de busca
     * @return array Lista de liderados encontrados
     */
    public function buscarPorTermo($termo) {
        try {
            $termoBusca = '%' . $termo . '%';
            
            $stmt = $this->conn->prepare("
                SELECT * FROM liderados
                WHERE (nome LIKE ? OR email LIKE ? OR cargo LIKE ?) AND ativo = 1
                ORDER BY nome
                LIMIT 20
            ");
            
            $stmt->execute([$termoBusca, $termoBusca, $termoBusca]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao buscar liderados: ' . $e->getMessage());
            return [];
        }
    }
    /**
     * Obter liderado pelo ID
     * 
     * @param int $id ID do liderado
     * @return array|false Dados do liderado ou false se não encontrado
     */
    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM liderados WHERE id = ?");
            $stmt->execute([$id]);
            
            $liderado = $stmt->fetch();
            
            if ($liderado) {
                // Obter projetos do liderado
                $liderado['projetos'] = $this->getProjetos($id);
                
                // Verificar se está cross-funcional em todos os projetos
                $liderado['cross_funcional'] = (bool) $liderado['cross_funcional'];
            }
            
            return $liderado;
        } catch (PDOException $e) {
            logError('Erro ao obter liderado por ID: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter liderado pelo email
     * 
     * @param string $email Email do liderado
     * @return array|false Dados do liderado ou false se não encontrado
     */
    public function getByEmail($email) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM liderados WHERE email = ?");
            $stmt->execute([$email]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            logError('Erro ao obter liderado por email: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Adicionar novo liderado
     * 
     * @param array $data Dados do liderado
     * @return int|false ID do liderado inserido ou false em caso de erro
     */
    public function add($data) {
        try {
            $this->conn->beginTransaction();
            
            $stmt = $this->conn->prepare("
                INSERT INTO liderados (nome, email, cargo, cross_funcional)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['nome'],
                $data['email'],
                $data['cargo'],
                isset($data['cross_funcional']) ? $data['cross_funcional'] : 0
            ]);
            
            $id = $this->conn->lastInsertId();
            
            // Registrar no log
            logActivity('liderados', $id, 'INSERT', null, $data);
            
            $this->conn->commit();
            
            return $id;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao adicionar liderado: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualizar liderado existente
     * 
     * @param int $id ID do liderado
     * @param array $data Novos dados do liderado
     * @return bool Sucesso ou falha na operação
     */
    public function update($id, $data) {
        try {
            $this->conn->beginTransaction();
            
            // Obter dados antigos para o log
            $oldData = $this->getById($id);
            
            if (!$oldData) {
                throw new Exception("Liderado não encontrado");
            }
            
            $stmt = $this->conn->prepare("
                UPDATE liderados 
                SET nome = ?, email = ?, cargo = ?, cross_funcional = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $data['nome'],
                $data['email'],
                $data['cargo'],
                isset($data['cross_funcional']) ? $data['cross_funcional'] : 0,
                $id
            ]);
            
            // Registrar no log
            logActivity('liderados', $id, 'UPDATE', $oldData, $data);
            
            $this->conn->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao atualizar liderado: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Desativar um liderado
     * 
     * @param int $id ID do liderado
     * @return bool Sucesso ou falha na operação
     */
    public function delete($id) {
        try {
            $this->conn->beginTransaction();
            
            // Obter dados antigos para o log
            $oldData = $this->getById($id);
            
            if (!$oldData) {
                throw new Exception("Liderado não encontrado");
            }
            
            // Apenas desativar, não excluir definitivamente
            $stmt = $this->conn->prepare("UPDATE liderados SET ativo = 0 WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            // Registrar no log
            logActivity('liderados', $id, 'DELETE', $oldData, null);
            
            $this->conn->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao desativar liderado: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter projetos de um liderado
     * 
     * @param int $lideradoId ID do liderado
     * @return array Lista de projetos do liderado
     */
    public function getProjetos($lideradoId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT lp.*, p.nome as projeto_nome, p.status as projeto_status
                FROM liderados_projetos lp
                JOIN projetos p ON lp.projeto_id = p.id
                WHERE lp.liderado_id = ? AND (lp.data_fim IS NULL OR lp.data_fim >= CURRENT_DATE())
                ORDER BY lp.percentual_dedicacao DESC
            ");
            
            $stmt->execute([$lideradoId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter projetos do liderado: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Associar liderado a um projeto
     * 
     * @param int $lideradoId ID do liderado
     * @param int $projetoId ID do projeto
     * @param int $percentual Percentual de dedicação ao projeto
     * @param string $dataInicio Data de início no projeto
     * @return bool Sucesso ou falha na operação
     */
    public function associarProjeto($lideradoId, $projetoId, $percentual, $dataInicio) {
        try {
            $this->conn->beginTransaction();
            
            // Verificar se já existe associação
            $stmt = $this->conn->prepare("
                SELECT id FROM liderados_projetos
                WHERE liderado_id = ? AND projeto_id = ? AND (data_fim IS NULL OR data_fim >= CURRENT_DATE())
            ");
            
            $stmt->execute([$lideradoId, $projetoId]);
            
            if ($stmt->fetch()) {
                // Atualizar associação existente
                $stmt = $this->conn->prepare("
                    UPDATE liderados_projetos
                    SET percentual_dedicacao = ?, data_inicio = ?, data_fim = NULL
                    WHERE liderado_id = ? AND projeto_id = ?
                ");
                
                $result = $stmt->execute([$percentual, $dataInicio, $lideradoId, $projetoId]);
            } else {
                // Criar nova associação
                $stmt = $this->conn->prepare("
                    INSERT INTO liderados_projetos (liderado_id, projeto_id, percentual_dedicacao, data_inicio)
                    VALUES (?, ?, ?, ?)
                ");
                
                $result = $stmt->execute([$lideradoId, $projetoId, $percentual, $dataInicio]);
            }
            
            $this->conn->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao associar liderado ao projeto: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remover liderado de um projeto
     * 
     * @param int $lideradoId ID do liderado
     * @param int $projetoId ID do projeto
     * @param string $dataFim Data de saída do projeto
     * @return bool Sucesso ou falha na operação
     */
    public function removerDoProjeto($lideradoId, $projetoId, $dataFim) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE liderados_projetos
                SET data_fim = ?
                WHERE liderado_id = ? AND projeto_id = ? AND (data_fim IS NULL OR data_fim > ?)
            ");
            
            return $stmt->execute([$dataFim, $lideradoId, $projetoId, $dataFim]);
        } catch (PDOException $e) {
            logError('Erro ao remover liderado do projeto: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Definir liderado como cross-funcional
     * 
     * @param int $lideradoId ID do liderado
     * @param bool $isCross Status cross-funcional
     * @return bool Sucesso ou falha na operação
     */
    public function setCrossFuncional($lideradoId, $isCross) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE liderados
                SET cross_funcional = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([$isCross ? 1 : 0, $lideradoId]);
        } catch (PDOException $e) {
            logError('Erro ao definir status cross-funcional: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter apontamentos de horas de um liderado
     * 
     * @param int $lideradoId ID do liderado
     * @param string $dataInicio Data inicial para filtro (opcional)
     * @param string $dataFim Data final para filtro (opcional)
     * @return array Lista de apontamentos
     */
    public function getApontamentos($lideradoId, $dataInicio = null, $dataFim = null) {
        try {
            $sql = "
                SELECT a.*, p.nome as projeto_nome, at.titulo as atividade_titulo
                FROM apontamentos a
                LEFT JOIN projetos p ON a.projeto_id = p.id
                LEFT JOIN atividades at ON a.atividade_id = at.id
                WHERE a.liderado_id = ?
            ";
            
            $params = [$lideradoId];
            
            if ($dataInicio) {
                $sql .= " AND a.data >= ?";
                $params[] = $dataInicio;
            }
            
            if ($dataFim) {
                $sql .= " AND a.data <= ?";
                $params[] = $dataFim;
            }
            
            $sql .= " ORDER BY a.data DESC, a.id DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter apontamentos: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter total de horas trabalhadas por um liderado
     * 
     * @param int $lideradoId ID do liderado
     * @param string $dataInicio Data inicial para filtro (opcional)
     * @param string $dataFim Data final para filtro (opcional)
     * @return float Total de horas
     */
    public function getTotalHoras($lideradoId, $dataInicio = null, $dataFim = null) {
        try {
            $sql = "
                SELECT SUM(quantidade_horas) as total
                FROM apontamentos
                WHERE liderado_id = ?
            ";
            
            $params = [$lideradoId];
            
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
            logError('Erro ao calcular total de horas: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obter OPRs de um liderado
     * 
     * @param int $lideradoId ID do liderado
     * @return array Lista de OPRs
     */
    public function getOPRs($lideradoId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT o.*, 
                       (SELECT COUNT(*) FROM opr_clientes WHERE opr_id = o.id) as total_clientes,
                       (SELECT COUNT(*) FROM opr_atividades_realizadas WHERE opr_id = o.id) as total_atividades,
                       (SELECT COUNT(*) FROM opr_proximas_atividades WHERE opr_id = o.id) as total_proximas,
                       (SELECT COUNT(*) FROM opr_riscos WHERE opr_id = o.id) as total_riscos
                FROM oprs o
                WHERE o.liderado_id = ?
                ORDER BY o.data_geracao DESC
            ");
            
            $stmt->execute([$lideradoId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter OPRs: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter estatísticas do liderado
     * 
     * @param int $lideradoId ID do liderado
     * @return array Estatísticas do liderado
     */
    public function getEstatisticas($lideradoId) {
        try {
            // Total de horas no último mês
            $ultimoMes = date('Y-m-d', strtotime('-1 month'));
            $horasUltimoMes = $this->getTotalHoras($lideradoId, $ultimoMes);
            
            // Total de projetos ativos
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total FROM liderados_projetos
                WHERE liderado_id = ? AND (data_fim IS NULL OR data_fim >= CURRENT_DATE())
            ");
            $stmt->execute([$lideradoId]);
            $totalProjetos = $stmt->fetch()['total'];
            
            // Total de atividades atribuídas
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total FROM atividades_responsaveis ar
                JOIN atividades a ON ar.atividade_id = a.id
                WHERE ar.liderado_id = ? AND a.status != 'Concluída'
            ");
            $stmt->execute([$lideradoId]);
            $totalAtividades = $stmt->fetch()['total'];
            
            // Percentual de horas por projeto
            $stmt = $this->conn->prepare("
                SELECT p.nome as projeto, SUM(a.quantidade_horas) as horas
                FROM apontamentos a
                JOIN projetos p ON a.projeto_id = p.id
                WHERE a.liderado_id = ? AND a.data >= ?
                GROUP BY a.projeto_id
                ORDER BY horas DESC
            ");
            $stmt->execute([$lideradoId, $ultimoMes]);
            $horasPorProjeto = $stmt->fetchAll();
            
            return [
                'horas_ultimo_mes' => $horasUltimoMes,
                'total_projetos' => $totalProjetos,
                'total_atividades' => $totalAtividades,
                'horas_por_projeto' => $horasPorProjeto
            ];
        } catch (PDOException $e) {
            logError('Erro ao obter estatísticas do liderado: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verificar se um email já está em uso
     * 
     * @param string $email Email a verificar
     * @param int $excluirId ID a excluir da verificação (para edição)
     * @return bool True se o email já estiver em uso
     */
    public function emailExiste($email, $excluirId = null) {
        try {
            $sql = "SELECT id FROM liderados WHERE email = ?";
            $params = [$email];
            
            if ($excluirId) {
                $sql .= " AND id != ?";
                $params[] = $excluirId;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            logError('Erro ao verificar email: ' . $e->getMessage());
            return false;
        }
    }
}