<?php
require_once __DIR__ . '/../config.php';

class Atividade {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Obter todas as atividades
     * 
     * @param int $projetoId ID do projeto para filtro (opcional)
     * @param string $status Status para filtro (opcional)
     * @param bool $apenasAtivas Incluir apenas atividades não concluídas
     * @return array Lista de atividades
     */
    public function getAll($projetoId = null, $status = null, $apenasAtivas = false) {
        try {
            $sql = "
                SELECT a.*,
                       p.nome as projeto_nome,
                       (SELECT GROUP_CONCAT(l.nome SEPARATOR ', ') 
                        FROM atividades_responsaveis ar
                        JOIN liderados l ON ar.liderado_id = l.id
                        WHERE ar.atividade_id = a.id) as responsaveis
                FROM atividades a
                LEFT JOIN projetos p ON a.projeto_id = p.id
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($projetoId) {
                $sql .= " AND a.projeto_id = ?";
                $params[] = $projetoId;
            }
            
            if ($status) {
                $sql .= " AND a.status = ?";
                $params[] = $status;
            }
            
            if ($apenasAtivas) {
                $sql .= " AND a.status != 'Concluída'";
            }
            
            $sql .= " ORDER BY a.status, a.prioridade, a.id DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter atividades: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter atividade pelo ID
     * 
     * @param int $id ID da atividade
     * @return array|false Dados da atividade ou false se não encontrada
     */
    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT a.*,
                       p.nome as projeto_nome,
                       p.status as projeto_status
                FROM atividades a
                LEFT JOIN projetos p ON a.projeto_id = p.id
                WHERE a.id = ?
            ");
            
            $stmt->execute([$id]);
            
            $atividade = $stmt->fetch();
            
            if ($atividade) {
                // Obter responsáveis
                $atividade['responsaveis'] = $this->getResponsaveis($id);
                
                // Obter apontamentos
                $atividade['apontamentos'] = $this->getApontamentos($id);
            }
            
            return $atividade;
        } catch (PDOException $e) {
            logError('Erro ao obter atividade por ID: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Adicionar nova atividade
     * 
     * @param array $data Dados da atividade
     * @return int|false ID da atividade inserida ou false em caso de erro
     */
    public function add($data) {
        try {
            $this->conn->beginTransaction();
            
            $stmt = $this->conn->prepare("
                INSERT INTO atividades (titulo, descricao, projeto_id, prioridade, status,
                                        data_inicio, data_fim, horas_estimadas)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['titulo'],
                $data['descricao'] ?? null,
                $data['projeto_id'] ?? null,
                $data['prioridade'] ?? 'Média',
                $data['status'] ?? 'Não iniciada',
                $data['data_inicio'] ?? null,
                $data['data_fim'] ?? null,
                $data['horas_estimadas'] ?? 0
            ]);
            
            $id = $this->conn->lastInsertId();
            
            // Adicionar responsáveis
            if (isset($data['responsaveis']) && is_array($data['responsaveis'])) {
                foreach ($data['responsaveis'] as $lideradoId) {
                    $this->adicionarResponsavel($id, $lideradoId);
                }
            }
            
            // Registrar no log
            logActivity('atividades', $id, 'INSERT', null, $data);
            
            $this->conn->commit();
            
            return $id;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao adicionar atividade: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualizar atividade existente
     * 
     * @param int $id ID da atividade
     * @param array $data Novos dados da atividade
     * @return bool Sucesso ou falha na operação
     */
    public function update($id, $data) {
        try {
            $this->conn->beginTransaction();
            
            // Obter dados antigos para o log
            $oldData = $this->getById($id);
            
            if (!$oldData) {
                throw new Exception("Atividade não encontrada");
            }
            
            $stmt = $this->conn->prepare("
                UPDATE atividades 
                SET titulo = ?, descricao = ?, projeto_id = ?, prioridade = ?, status = ?,
                    data_inicio = ?, data_fim = ?, horas_estimadas = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $data['titulo'],
                $data['descricao'] ?? null,
                $data['projeto_id'] ?? null,
                $data['prioridade'] ?? 'Média',
                $data['status'] ?? 'Não iniciada',
                $data['data_inicio'] ?? null,
                $data['data_fim'] ?? null,
                $data['horas_estimadas'] ?? 0,
                $id
            ]);
            
            // Atualizar responsáveis
            if (isset($data['responsaveis'])) {
                // Remover responsáveis atuais
                $this->removerResponsaveis($id);
                
                // Adicionar novos responsáveis
                if (is_array($data['responsaveis'])) {
                    foreach ($data['responsaveis'] as $lideradoId) {
                        $this->adicionarResponsavel($id, $lideradoId);
                    }
                }
            }
            
            // Registrar no log
            logActivity('atividades', $id, 'UPDATE', $oldData, $data);
            
            $this->conn->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao atualizar atividade: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Excluir uma atividade
     * 
     * @param int $id ID da atividade
     * @return bool Sucesso ou falha na operação
     */
    public function delete($id) {
        try {
            $this->conn->beginTransaction();
            
            // Obter dados antigos para o log
            $oldData = $this->getById($id);
            
            if (!$oldData) {
                throw new Exception("Atividade não encontrada");
            }
            
            // Remover responsáveis
            $this->removerResponsaveis($id);
            
            // Remover referências em apontamentos
            $stmt = $this->conn->prepare("UPDATE apontamentos SET atividade_id = NULL WHERE atividade_id = ?");
            $stmt->execute([$id]);
            
            // Remover referências em OPRs
            $stmt = $this->conn->prepare("UPDATE opr_atividades_realizadas SET atividade_id = NULL WHERE atividade_id = ?");
            $stmt->execute([$id]);
            
            // Excluir atividade
            $stmt = $this->conn->prepare("DELETE FROM atividades WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            // Registrar no log
            logActivity('atividades', $id, 'DELETE', $oldData, null);
            
            $this->conn->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao excluir atividade: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter responsáveis de uma atividade
     * 
     * @param int $atividadeId ID da atividade
     * @return array Lista de responsáveis
     */
    public function getResponsaveis($atividadeId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT l.*
                FROM atividades_responsaveis ar
                JOIN liderados l ON ar.liderado_id = l.id
                WHERE ar.atividade_id = ?
            ");
            
            $stmt->execute([$atividadeId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter responsáveis da atividade: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Adicionar responsável à atividade
     * 
     * @param int $atividadeId ID da atividade
     * @param int $lideradoId ID do liderado
     * @return bool Sucesso ou falha na operação
     */
    public function adicionarResponsavel($atividadeId, $lideradoId) {
        try {
            // Verificar se já existe
            $stmt = $this->conn->prepare("
                SELECT id FROM atividades_responsaveis
                WHERE atividade_id = ? AND liderado_id = ?
            ");
            
            $stmt->execute([$atividadeId, $lideradoId]);
            
            if ($stmt->fetch()) {
                return true; // Já existe, não precisa adicionar
            }
            
            // Inserir novo
            $stmt = $this->conn->prepare("
                INSERT INTO atividades_responsaveis (atividade_id, liderado_id)
                VALUES (?, ?)
            ");
            
            return $stmt->execute([$atividadeId, $lideradoId]);
        } catch (PDOException $e) {
            logError('Erro ao adicionar responsável: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remover todos os responsáveis de uma atividade
     * 
     * @param int $atividadeId ID da atividade
     * @return bool Sucesso ou falha na operação
     */
    public function removerResponsaveis($atividadeId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM atividades_responsaveis WHERE atividade_id = ?");
            return $stmt->execute([$atividadeId]);
        } catch (PDOException $e) {
            logError('Erro ao remover responsáveis: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter apontamentos de uma atividade
     * 
     * @param int $atividadeId ID da atividade
     * @return array Lista de apontamentos
     */
    public function getApontamentos($atividadeId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT a.*, l.nome as liderado_nome
                FROM apontamentos a
                JOIN liderados l ON a.liderado_id = l.id
                WHERE a.atividade_id = ?
                ORDER BY a.data DESC
            ");
            
            $stmt->execute([$atividadeId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter apontamentos da atividade: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Atualizar status da atividade
     * 
     * @param int $id ID da atividade
     * @param string $status Novo status
     * @return bool Sucesso ou falha na operação
     */
    public function atualizarStatus($id, $status) {
        try {
            $stmt = $this->conn->prepare("UPDATE atividades SET status = ? WHERE id = ?");
            return $stmt->execute([$status, $id]);
        } catch (PDOException $e) {
            logError('Erro ao atualizar status: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter total de atividades
     * 
     * @param string $status Status para filtro (opcional)
     * @return int Total de atividades
     */
    public function getTotalAtividades($status = null) {
        try {
            $sql = "SELECT COUNT(*) as total FROM atividades";
            $params = [];
            
            if ($status) {
                $sql .= " WHERE status = ?";
                $params[] = $status;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            
            return $result ? (int) $result['total'] : 0;
        } catch (PDOException $e) {
            logError('Erro ao contar atividades: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obter atividades por liderado
     * 
     * @param int $lideradoId ID do liderado
     * @param bool $apenasAtivas Incluir apenas atividades não concluídas
     * @return array Lista de atividades
     */
    public function getAtividadesPorLiderado($lideradoId, $apenasAtivas = true) {
        try {
            $sql = "
                SELECT a.*, p.nome as projeto_nome
                FROM atividades a
                JOIN atividades_responsaveis ar ON a.id = ar.atividade_id
                LEFT JOIN projetos p ON a.projeto_id = p.id
                WHERE ar.liderado_id = ?
            ";
            
            if ($apenasAtivas) {
                $sql .= " AND a.status != 'Concluída'";
            }
            
            $sql .= " ORDER BY a.status, a.prioridade";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$lideradoId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter atividades por liderado: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter atividades recentes
     * 
     * @param int $limite Limite de registros
     * @return array Lista de atividades recentes
     */
    public function getRecentes($limite = 10) {
        try {
            $stmt = $this->conn->prepare("
                SELECT a.*, p.nome as projeto_nome,
                       (SELECT GROUP_CONCAT(l.nome SEPARATOR ', ') 
                        FROM atividades_responsaveis ar
                        JOIN liderados l ON ar.liderado_id = l.id
                        WHERE ar.atividade_id = a.id) as responsaveis
                FROM atividades a
                LEFT JOIN projetos p ON a.projeto_id = p.id
                ORDER BY a.id DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limite]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter atividades recentes: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar atividades por termo
     * 
     * @param string $termo Termo de busca
     * @return array Lista de atividades
     */
    public function buscarPorTermo($termo) {
        try {
            $termoBusca = '%' . $termo . '%';
            
            $stmt = $this->conn->prepare("
                SELECT a.*, p.nome as projeto_nome
                FROM atividades a
                LEFT JOIN projetos p ON a.projeto_id = p.id
                WHERE a.titulo LIKE ? OR a.descricao LIKE ? OR p.nome LIKE ?
                ORDER BY a.status, a.prioridade
                LIMIT 20
            ");
            
            $stmt->execute([$termoBusca, $termoBusca, $termoBusca]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao buscar atividades: ' . $e->getMessage());
            return [];
        }
    }
}