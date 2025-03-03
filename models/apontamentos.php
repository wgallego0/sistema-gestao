<?php
require_once __DIR__ . '/../config.php';

class Apontamento {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Obter todos os apontamentos
     * 
     * @param int $lideradoId ID do liderado para filtro (opcional)
     * @param int $projetoId ID do projeto para filtro (opcional)
     * @param string $dataInicio Data inicial para filtro (opcional)
     * @param string $dataFim Data final para filtro (opcional)
     * @return array Lista de apontamentos
     */
    public function getAll($lideradoId = null, $projetoId = null, $dataInicio = null, $dataFim = null) {
        try {
            $sql = "
                SELECT a.*, 
                       l.nome as liderado_nome,
                       p.nome as projeto_nome,
                       at.titulo as atividade_titulo
                FROM apontamentos a
                JOIN liderados l ON a.liderado_id = l.id
                LEFT JOIN projetos p ON a.projeto_id = p.id
                LEFT JOIN atividades at ON a.atividade_id = at.id
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($lideradoId) {
                $sql .= " AND a.liderado_id = ?";
                $params[] = $lideradoId;
            }
            
            if ($projetoId) {
                $sql .= " AND a.projeto_id = ?";
                $params[] = $projetoId;
            }
            
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
     * Obter apontamento pelo ID
     * 
     * @param int $id ID do apontamento
     * @return array|false Dados do apontamento ou false se não encontrado
     */
    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT a.*, 
                       l.nome as liderado_nome,
                       p.nome as projeto_nome,
                       at.titulo as atividade_titulo
                FROM apontamentos a
                JOIN liderados l ON a.liderado_id = l.id
                LEFT JOIN projetos p ON a.projeto_id = p.id
                LEFT JOIN atividades at ON a.atividade_id = at.id
                WHERE a.id = ?
            ");
            
            $stmt->execute([$id]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            logError('Erro ao obter apontamento por ID: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Adicionar novo apontamento
     * 
     * @param array $data Dados do apontamento
     * @return int|false ID do apontamento inserido ou false em caso de erro
     */
    public function add($data) {
        try {
            $this->conn->beginTransaction();
            
            $stmt = $this->conn->prepare("
                INSERT INTO apontamentos (liderado_id, projeto_id, atividade_id, data, quantidade_horas, descricao, opr_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['liderado_id'],
                $data['projeto_id'] ?? null,
                $data['atividade_id'] ?? null,
                $data['data'],
                $data['quantidade_horas'],
                $data['descricao'] ?? null,
                $data['opr_id'] ?? null
            ]);
            
            $id = $this->conn->lastInsertId();
            
            // Registrar no log
            logActivity('apontamentos', $id, 'INSERT', null, $data);
            
            $this->conn->commit();
            
            return $id;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao adicionar apontamento: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualizar apontamento existente
     * 
     * @param int $id ID do apontamento
     * @param array $data Novos dados do apontamento
     * @return bool Sucesso ou falha na operação
     */
    public function update($id, $data) {
        try {
            $this->conn->beginTransaction();
            
            // Obter dados antigos para o log
            $oldData = $this->getById($id);
            
            if (!$oldData) {
                throw new Exception("Apontamento não encontrado");
            }
            
            $stmt = $this->conn->prepare("
                UPDATE apontamentos 
                SET projeto_id = ?, atividade_id = ?, data = ?, quantidade_horas = ?, descricao = ?, opr_id = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $data['projeto_id'] ?? null,
                $data['atividade_id'] ?? null,
                $data['data'],
                $data['quantidade_horas'],
                $data['descricao'] ?? null,
                $data['opr_id'] ?? null,
                $id
            ]);
            
            // Registrar no log
            logActivity('apontamentos', $id, 'UPDATE', $oldData, $data);
            
            $this->conn->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao atualizar apontamento: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Excluir um apontamento
     * 
     * @param int $id ID do apontamento
     * @return bool Sucesso ou falha na operação
     */
    public function delete($id) {
        try {
            $this->conn->beginTransaction();
            
            // Obter dados antigos para o log
            $oldData = $this->getById($id);
            
            if (!$oldData) {
                throw new Exception("Apontamento não encontrado");
            }
            
            $stmt = $this->conn->prepare("DELETE FROM apontamentos WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            // Registrar no log
            logActivity('apontamentos', $id, 'DELETE', $oldData, null);
            
            $this->conn->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao excluir apontamento: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter apontamentos recentes (para dashboard)
     * 
     * @param int $limite Número máximo de registros
     * @return array Lista de apontamentos recentes
     */
    public function getRecentes($limite = 10) {
        try {
            $stmt = $this->conn->prepare("
                SELECT a.*, 
                       l.nome as liderado_nome,
                       p.nome as projeto_nome,
                       at.titulo as atividade_titulo
                FROM apontamentos a
                JOIN liderados l ON a.liderado_id = l.id
                LEFT JOIN projetos p ON a.projeto_id = p.id
                LEFT JOIN atividades at ON a.atividade_id = at.id
                ORDER BY a.data_cadastro DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limite]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter apontamentos recentes: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter horas totais por projeto
     * 
     * @param string $dataInicio Data inicial para filtro (opcional)
     * @param string $dataFim Data final para filtro (opcional)
     * @return array Horas por projeto
     */
    public function getHorasPorProjeto($dataInicio = null, $dataFim = null) {
        try {
            $sql = "
                SELECT p.nome as projeto, SUM(a.quantidade_horas) as horas
                FROM apontamentos a
                JOIN projetos p ON a.projeto_id = p.id
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($dataInicio) {
                $sql .= " AND a.data >= ?";
                $params[] = $dataInicio;
            }
            
            if ($dataFim) {
                $sql .= " AND a.data <= ?";
                $params[] = $dataFim;
            }
            
            $sql .= " GROUP BY a.projeto_id ORDER BY horas DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter horas por projeto: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter horas totais por liderado
     * 
     * @param string $dataInicio Data inicial para filtro (opcional)
     * @param string $dataFim Data final para filtro (opcional)
     * @return array Horas por liderado
     */
    public function getHorasPorLiderado($dataInicio = null, $dataFim = null) {
        try {
            $sql = "
                SELECT l.nome as liderado, SUM(a.quantidade_horas) as horas
                FROM apontamentos a
                JOIN liderados l ON a.liderado_id = l.id
                WHERE 1=1
            ";
            
            $params = [];
            
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
     * Obter horas totais por dia
     * 
     * @param string $dataInicio Data inicial para filtro
     * @param string $dataFim Data final para filtro
     * @return array Horas por dia
     */
    public function getHorasPorDia($dataInicio, $dataFim) {
        try {
            $stmt = $this->conn->prepare("
                SELECT a.data, SUM(a.quantidade_horas) as horas
                FROM apontamentos a
                WHERE a.data BETWEEN ? AND ?
                GROUP BY a.data
                ORDER BY a.data
            ");
            
            $stmt->execute([$dataInicio, $dataFim]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter horas por dia: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter apontamentos da semana atual para um liderado
     * 
     * @param int $lideradoId ID do liderado
     * @return array Apontamentos da semana
     */
    public function getApontamentosSemana($lideradoId) {
        try {
            // Calcular início e fim da semana atual
            $hoje = new DateTime();
            $inicioSemana = clone $hoje;
            $inicioSemana->modify('this week monday');
            
            $fimSemana = clone $inicioSemana;
            $fimSemana->modify('+6 days');
            
            return $this->getAll(
                $lideradoId, 
                null, 
                $inicioSemana->format('Y-m-d'), 
                $fimSemana->format('Y-m-d')
            );
        } catch (PDOException $e) {
            logError('Erro ao obter apontamentos da semana: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calcular estatísticas de apontamentos
     * 
     * @param string $dataInicio Data inicial para filtro (opcional)
     * @param string $dataFim Data final para filtro (opcional)
     * @return array Estatísticas
     */
    public function getEstatisticas($dataInicio = null, $dataFim = null) {
        try {
            // Total de horas
            $sql = "SELECT SUM(quantidade_horas) as total FROM apontamentos";
            $params = [];
            
            if ($dataInicio || $dataFim) {
                $sql .= " WHERE 1=1";
                
                if ($dataInicio) {
                    $sql .= " AND data >= ?";
                    $params[] = $dataInicio;
                }
                
                if ($dataFim) {
                    $sql .= " AND data <= ?";
                    $params[] = $dataFim;
                }
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $totalHoras = $stmt->fetch()['total'] ?? 0;
            
            // Horas por projeto
            $horasPorProjeto = $this->getHorasPorProjeto($dataInicio, $dataFim);
            
            // Horas por liderado
            $horasPorLiderado = $this->getHorasPorLiderado($dataInicio, $dataFim);
            
            // Horas por dia (se houver datas de filtro)
            $horasPorDia = [];
            if ($dataInicio && $dataFim) {
                $horasPorDia = $this->getHorasPorDia($dataInicio, $dataFim);
            }
            
            return [
                'total_horas' => $totalHoras,
                'horas_por_projeto' => $horasPorProjeto,
                'horas_por_liderado' => $horasPorLiderado,
                'horas_por_dia' => $horasPorDia
            ];
        } catch (PDOException $e) {
            logError('Erro ao obter estatísticas de apontamentos: ' . $e->getMessage());
            return [
                'total_horas' => 0,
                'horas_por_projeto' => [],
                'horas_por_liderado' => [],
                'horas_por_dia' => []
            ];
        }
    }
    
    /**
     * Verificar se um liderado tem horas disponíveis para apontar em uma data
     * 
     * @param int $lideradoId ID do liderado
     * @param string $data Data para verificação
     * @param float $horasAtual Horas atuais a considerar (para edição)
     * @return float Horas disponíveis
     */
    public function getHorasDisponiveis($lideradoId, $data, $horasAtual = 0) {
        try {
            $stmt = $this->conn->prepare("
                SELECT SUM(quantidade_horas) as total
                FROM apontamentos
                WHERE liderado_id = ? AND data = ?
            ");
            
            $stmt->execute([$lideradoId, $data]);
            $resultado = $stmt->fetch();
            
            $horasApontadas = $resultado ? (float) $resultado['total'] : 0;
            $horasApontadas -= $horasAtual;
            
            $horasDiaPadrao = (float) getConfig('horas_dia_padrao', 8);
            
            return max(0, $horasDiaPadrao - $horasApontadas);
        } catch (PDOException $e) {
            logError('Erro ao calcular horas disponíveis: ' . $e->getMessage());
            return 0;
        }
    }
}