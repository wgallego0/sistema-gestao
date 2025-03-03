<?php
require_once __DIR__ . '/../config.php';

class OPR {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Obter todos os OPRs
     * 
     * @param int $lideradoId ID do liderado para filtro (opcional)
     * @return array Lista de OPRs
     */
    public function getAll($lideradoId = null) {
        try {
            $sql = "
                SELECT o.*, l.nome as liderado_nome, l.cargo as liderado_cargo,
                       (SELECT COUNT(*) FROM opr_clientes WHERE opr_id = o.id) as total_clientes,
                       (SELECT COUNT(*) FROM opr_atividades_realizadas WHERE opr_id = o.id) as total_atividades,
                       (SELECT COUNT(*) FROM opr_proximas_atividades WHERE opr_id = o.id) as total_proximas,
                       (SELECT COUNT(*) FROM opr_riscos WHERE opr_id = o.id) as total_riscos
                FROM oprs o
                JOIN liderados l ON o.liderado_id = l.id
            ";
            
            $params = [];
            
            if ($lideradoId) {
                $sql .= " WHERE o.liderado_id = ?";
                $params[] = $lideradoId;
            }
            
            $sql .= " ORDER BY o.data_geracao DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter OPRs: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter OPR pelo ID
     * 
     * @param int $id ID do OPR
     * @return array|false Dados do OPR ou false se não encontrado
     */
    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT o.*, l.nome as liderado_nome, l.cargo as liderado_cargo
                FROM oprs o
                JOIN liderados l ON o.liderado_id = l.id
                WHERE o.id = ?
            ");
            
            $stmt->execute([$id]);
            
            $opr = $stmt->fetch();
            
            if ($opr) {
                // Obter detalhes adicionais do OPR
                $opr['clientes'] = $this->getClientes($id);
                $opr['atividades_realizadas'] = $this->getAtividadesRealizadas($id);
                $opr['proximas_atividades'] = $this->getProximasAtividades($id);
                $opr['riscos'] = $this->getRiscos($id);
                $opr['mencoes_projetos'] = $this->getMencoesProjetos($id);
                $opr['apontamentos'] = $this->getApontamentos($id);
                $opr['graficos'] = $this->gerarDadosGraficos($id);
            }
            
            return $opr;
        } catch (PDOException $e) {
            logError('Erro ao obter OPR por ID: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Adicionar novo OPR
     * 
     * @param array $data Dados do OPR
     * @return int|false ID do OPR inserido ou false em caso de erro
     */
    public function add($data) {
        try {
            $this->conn->beginTransaction();
            
            $stmt = $this->conn->prepare("
                INSERT INTO oprs (liderado_id, semana, status)
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([
                $data['liderado_id'],
                $data['semana'],
                $data['status'] ?? 'Rascunho'
            ]);
            
            $id = $this->conn->lastInsertId();
            
            // Adicionar clientes atendidos
            if (isset($data['clientes']) && is_array($data['clientes'])) {
                foreach ($data['clientes'] as $cliente) {
                    $this->adicionarCliente($id, $cliente);
                }
            }
            
            // Adicionar atividades realizadas
            if (isset($data['atividades_realizadas']) && is_array($data['atividades_realizadas'])) {
                foreach ($data['atividades_realizadas'] as $atividade) {
                    $this->adicionarAtividadeRealizada($id, $atividade);
                }
            }
            
            // Adicionar próximas atividades
            if (isset($data['proximas_atividades']) && is_array($data['proximas_atividades'])) {
                foreach ($data['proximas_atividades'] as $proxima) {
                    $this->adicionarProximaAtividade($id, $proxima);
                }
            }
            
            // Adicionar riscos
            if (isset($data['riscos']) && is_array($data['riscos'])) {
                foreach ($data['riscos'] as $risco) {
                    $this->adicionarRisco($id, $risco);
                }
            }
            
            // Adicionar menções de projetos
            if (isset($data['mencoes_projetos']) && is_array($data['mencoes_projetos'])) {
                foreach ($data['mencoes_projetos'] as $mencao) {
                    $this->adicionarMencaoProjeto($id, $mencao);
                }
            }
            
            // Adicionar apontamentos
            if (isset($data['apontamentos']) && is_array($data['apontamentos'])) {
                foreach ($data['apontamentos'] as $apontamento) {
                    $this->adicionarApontamento($id, $apontamento);
                }
            }
            
            // Registrar no log
            logActivity('oprs', $id, 'INSERT', null, $data);
            
            $this->conn->commit();
            
            return $id;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao adicionar OPR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualizar OPR existente
     * 
     * @param int $id ID do OPR
     * @param array $data Novos dados do OPR
     * @return bool Sucesso ou falha na operação
     */
    public function update($id, $data) {
        try {
            $this->conn->beginTransaction();
            
            // Obter dados antigos para o log
            $oldData = $this->getById($id);
            
            if (!$oldData) {
                throw new Exception("OPR não encontrado");
            }
            
            $stmt = $this->conn->prepare("
                UPDATE oprs 
                SET semana = ?, status = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['semana'],
                $data['status'],
                $id
            ]);
            
            // Atualizar clientes atendidos
            if (isset($data['clientes'])) {
                // Remover todos os clientes existentes
                $this->removerClientes($id);
                
                // Adicionar novos clientes
                foreach ($data['clientes'] as $cliente) {
                    $this->adicionarCliente($id, $cliente);
                }
            }
            
            // Atualizar atividades realizadas
            if (isset($data['atividades_realizadas'])) {
                // Remover todas as atividades existentes
                $this->removerAtividadesRealizadas($id);
                
                // Adicionar novas atividades
                foreach ($data['atividades_realizadas'] as $atividade) {
                    $this->adicionarAtividadeRealizada($id, $atividade);
                }
            }
            
            // Atualizar próximas atividades
            if (isset($data['proximas_atividades'])) {
                // Remover todas as próximas atividades existentes
                $this->removerProximasAtividades($id);
                
                // Adicionar novas próximas atividades
                foreach ($data['proximas_atividades'] as $proxima) {
                    $this->adicionarProximaAtividade($id, $proxima);
                }
            }
            
            // Atualizar riscos
            if (isset($data['riscos'])) {
                // Remover todos os riscos existentes
                $this->removerRiscos($id);
                
                // Adicionar novos riscos
                foreach ($data['riscos'] as $risco) {
                    $this->adicionarRisco($id, $risco);
                }
            }
            
            // Atualizar menções de projetos
            if (isset($data['mencoes_projetos'])) {
                // Remover todas as menções existentes
                $this->removerMencoesProjetos($id);
                
                // Adicionar novas menções
                foreach ($data['mencoes_projetos'] as $mencao) {
                    $this->adicionarMencaoProjeto($id, $mencao);
                }
            }
            
            // Atualizar apontamentos
            if (isset($data['apontamentos'])) {
                // Remover ou desassociar apontamentos existentes
                $this->removerApontamentos($id);
                
                // Adicionar novos apontamentos
                foreach ($data['apontamentos'] as $apontamento) {
                    $this->adicionarApontamento($id, $apontamento);
                }
            }
            
            // Registrar no log
            logActivity('oprs', $id, 'UPDATE', $oldData, $data);
            
            $this->conn->commit();
            
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao atualizar OPR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Excluir um OPR
     * 
     * @param int $id ID do OPR
     * @return bool Sucesso ou falha na operação
     */
    public function delete($id) {
        try {
            $this->conn->beginTransaction();
            
            // Obter dados antigos para o log
            $oldData = $this->getById($id);
            
            if (!$oldData) {
                throw new Exception("OPR não encontrado");
            }
            
            // Remover registros relacionados
            $this->removerClientes($id);
            $this->removerAtividadesRealizadas($id);
            $this->removerProximasAtividades($id);
            $this->removerRiscos($id);
            $this->removerMencoesProjetos($id);
            $this->removerApontamentos($id, true); // Apenas desassociar, não excluir
            
            // Excluir o OPR
            $stmt = $this->conn->prepare("DELETE FROM oprs WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            // Registrar no log
            logActivity('oprs', $id, 'DELETE', $oldData, null);
            
            $this->conn->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao excluir OPR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter clientes atendidos de um OPR
     * 
     * @param int $oprId ID do OPR
     * @return array Lista de clientes
     */
    public function getClientes($oprId) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM opr_clientes WHERE opr_id = ?");
            $stmt->execute([$oprId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter clientes do OPR: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Adicionar cliente atendido ao OPR
     * 
     * @param int $oprId ID do OPR
     * @param array $data Dados do cliente
     * @return int|false ID do registro inserido ou false em caso de erro
     */
    public function adicionarCliente($oprId, $data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO opr_clientes (opr_id, cliente, descricao)
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([
                $oprId,
                $data['cliente'],
                $data['descricao'] ?? null
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            logError('Erro ao adicionar cliente ao OPR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remover todos os clientes de um OPR
     * 
     * @param int $oprId ID do OPR
     * @return bool Sucesso ou falha na operação
     */
    public function removerClientes($oprId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM opr_clientes WHERE opr_id = ?");
            return $stmt->execute([$oprId]);
        } catch (PDOException $e) {
            logError('Erro ao remover clientes do OPR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter atividades realizadas de um OPR
     * 
     * @param int $oprId ID do OPR
     * @return array Lista de atividades realizadas
     */
    public function getAtividadesRealizadas($oprId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT ar.*, a.titulo as atividade_titulo
                FROM opr_atividades_realizadas ar
                LEFT JOIN atividades a ON ar.atividade_id = a.id
                WHERE ar.opr_id = ?
            ");
            
            $stmt->execute([$oprId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter atividades realizadas do OPR: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Adicionar atividade realizada ao OPR
     * 
     * @param int $oprId ID do OPR
     * @param array $data Dados da atividade
     * @return int|false ID do registro inserido ou false em caso de erro
     */
    public function adicionarAtividadeRealizada($oprId, $data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO opr_atividades_realizadas (opr_id, atividade_id, descricao, resultado)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $oprId,
                $data['atividade_id'] ?? null,
                $data['descricao'],
                $data['resultado'] ?? null
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            logError('Erro ao adicionar atividade realizada ao OPR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remover todas as atividades realizadas de um OPR
     * 
     * @param int $oprId ID do OPR
     * @return bool Sucesso ou falha na operação
     */
    public function removerAtividadesRealizadas($oprId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM opr_atividades_realizadas WHERE opr_id = ?");
            return $stmt->execute([$oprId]);
        } catch (PDOException $e) {
            logError('Erro ao remover atividades realizadas do OPR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter próximas atividades de um OPR
     * 
     * @param int $oprId ID do OPR
     * @return array Lista de próximas atividades
     */
    public function getProximasAtividades($oprId) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM opr_proximas_atividades WHERE opr_id = ?");
            $stmt->execute([$oprId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter próximas atividades do OPR: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Adicionar próxima atividade ao OPR
     * 
     * @param int $oprId ID do OPR
     * @param array $data Dados da próxima atividade
     * @return int|false ID do registro inserido ou false em caso de erro
     */
    public function adicionarProximaAtividade($oprId, $data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO opr_proximas_atividades (opr_id, descricao, data_limite, prioridade)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $oprId,
                $data['descricao'],
                $data['data_limite'] ?? null,
                $data['prioridade'] ?? 'Média'
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            logError('Erro ao adicionar próxima atividade ao OPR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remover todas as próximas atividades de um OPR
     * 
     * @param int $oprId ID do OPR
     * @return bool Sucesso ou falha na operação
     */
    public function removerProximasAtividades($oprId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM opr_proximas_atividades WHERE opr_id = ?");
            return $stmt->execute([$oprId]);
        } catch (PDOException $e) {
            logError('Erro ao remover próximas atividades do OPR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter riscos de um OPR
     * 
     * @param int $oprId ID do OPR
     * @return array Lista de riscos
     */
    public function getRiscos($oprId) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM opr_riscos WHERE opr_id = ?");
            $stmt->execute([$oprId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter riscos do OPR: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Adicionar risco ao OPR
     * 
     * @param int $oprId ID do OPR
     * @param array $data Dados do risco
     * @return int|false ID do registro inserido ou false em caso de erro
     */
    public function adicionarRisco($oprId, $data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO opr_riscos (opr_id, descricao, impacto, probabilidade, mitigacao)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $oprId,
                $data['descricao'],
                $data['impacto'] ?? 'Médio',
                $data['probabilidade'] ?? 'Média',
                $data['mitigacao'] ?? null
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            logError('Erro ao adicionar risco ao OPR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remover todos os riscos de um OPR
     * 
     * @param int $oprId ID do OPR
     * @return bool Sucesso ou falha na operação
     */
    public function removerRiscos($oprId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM opr_riscos WHERE opr_id = ?");
            return $stmt->execute([$oprId]);
        } catch (PDOException $e) {
            logError('Erro ao remover riscos do OPR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter menções de projetos de um OPR
     * 
     * @param int $oprId ID do OPR
     * @return array Lista de menções de projetos
     */
    public function getMencoesProjetos($oprId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT mp.*, p.nome as projeto_nome
                FROM opr_mencoes_projetos mp
                LEFT JOIN projetos p ON mp.projeto_id = p.id
                WHERE mp.opr_id = ?
            ");
            
            $stmt->execute([$oprId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter menções de projetos do OPR: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Adicionar menção de projeto ao OPR
     * 
     * @param int $oprId ID do OPR
     * @param array $data Dados da menção
     * @return int|false ID do registro inserido ou false em caso de erro
     */
    public function adicionarMencaoProjeto($oprId, $data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO opr_mencoes_projetos (opr_id, projeto_id, descricao, destaque)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $oprId,
                $data['projeto_id'] ?? null,
                $data['descricao'],
                isset($data['destaque']) ? $data['destaque'] : 0
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            logError('Erro ao adicionar menção de projeto ao OPR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remover todas as menções de projetos de um OPR
     * 
     * @param int $oprId ID do OPR
     * @return bool Sucesso ou falha na operação
     */
    public function removerMencoesProjetos($oprId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM opr_mencoes_projetos WHERE opr_id = ?");
            return $stmt->execute([$oprId]);
        } catch (PDOException $e) {
            logError('Erro ao remover menções de projetos do OPR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter apontamentos de um OPR
     * 
     * @param int $oprId ID do OPR
     * @return array Lista de apontamentos
     */
    public function getApontamentos($oprId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT a.*, p.nome as projeto_nome, at.titulo as atividade_titulo
                FROM apontamentos a
                LEFT JOIN projetos p ON a.projeto_id = p.id
                LEFT JOIN atividades at ON a.atividade_id = at.id
                WHERE a.opr_id = ?
                ORDER BY a.data
            ");
            
            $stmt->execute([$oprId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter apontamentos do OPR: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Adicionar apontamento ao OPR
     * 
     * @param int $oprId ID do OPR
     * @param array $data Dados do apontamento
     * @return int|false ID do registro inserido ou false em caso de erro
     */
    public function adicionarApontamento($oprId, $data) {
        try {
            // Verificar se já existe um apontamento
            if (isset($data['id']) && $data['id']) {
                // Atualizar apontamento existente
                $stmt = $this->conn->prepare("
                    UPDATE apontamentos 
                    SET opr_id = ?
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([$oprId, $data['id']]);
                return $result ? $data['id'] : false;
            } else {
                // Obter liderado_id do OPR
                $stmt = $this->conn->prepare("SELECT liderado_id FROM oprs WHERE id = ?");
                $stmt->execute([$oprId]);
                $opr = $stmt->fetch();
                
                if (!$opr) {
                    throw new Exception("OPR não encontrado");
                }
                
                // Criar novo apontamento
                $stmt = $this->conn->prepare("
                    INSERT INTO apontamentos (liderado_id, projeto_id, atividade_id, data, quantidade_horas, descricao, opr_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $opr['liderado_id'],
                    $data['projeto_id'] ?? null,
                    $data['atividade_id'] ?? null,
                    $data['data'],
                    $data['quantidade_horas'],
                    $data['descricao'] ?? null,
                    $oprId
                ]);
                
                return $this->conn->lastInsertId();
            }
        } catch (PDOException $e) {
            logError('Erro ao adicionar apontamento ao OPR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remover todos os apontamentos de um OPR
     * 
     * @param int $oprId ID do OPR
     * @param bool $apenasDesassociar Apenas desassociar ou excluir completamente
     * @return bool Sucesso ou falha na operação
     */
    public function removerApontamentos($oprId, $apenasDesassociar = false) {
        try {
            if ($apenasDesassociar) {
                // Apenas desassociar os apontamentos (definir opr_id como NULL)
                $stmt = $this->conn->prepare("UPDATE apontamentos SET opr_id = NULL WHERE opr_id = ?");
            } else {
                // Excluir completamente os apontamentos
                $stmt = $this->conn->prepare("DELETE FROM apontamentos WHERE opr_id = ?");
            }
            
            return $stmt->execute([$oprId]);
        } catch (PDOException $e) {
            logError('Erro ao remover apontamentos do OPR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualizar status do OPR
     * 
     * @param int $id ID do OPR
     * @param string $status Novo status
     * @return bool Sucesso ou falha na operação
     */
    public function atualizarStatus($id, $status) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE oprs 
                SET status = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([$status, $id]);
        } catch (PDOException $e) {
            logError('Erro ao atualizar status do OPR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gerar dados para gráficos do OPR
     * 
     * @param int $oprId ID do OPR
     * @return array Dados para os gráficos
     */
    public function gerarDadosGraficos($oprId) {
        try {
            $apontamentos = $this->getApontamentos($oprId);
            
            // Estruturas para armazenar dados dos gráficos
            $horasPorProjeto = [];
            $horasPorDia = [
                'Segunda' => 0,
                'Terça' => 0,
                'Quarta' => 0,
                'Quinta' => 0,
                'Sexta' => 0,
                'Sábado' => 0,
                'Domingo' => 0
            ];
            
            // Preencher com os dados
            foreach ($apontamentos as $apontamento) {
                // Horas por projeto
                $nomeProjeto = $apontamento['projeto_nome'] ?? 'Sem projeto';
                
                if (!isset($horasPorProjeto[$nomeProjeto])) {
                    $horasPorProjeto[$nomeProjeto] = 0;
                }
                
                $horasPorProjeto[$nomeProjeto] += $apontamento['quantidade_horas'];
                
                // Horas por dia da semana
                $diaSemana = $this->obterDiaSemana($apontamento['data']);
                $horasPorDia[$diaSemana] += $apontamento['quantidade_horas'];
            }
            
            return [
                'horas_por_projeto' => $horasPorProjeto,
                'horas_por_dia' => $horasPorDia
            ];
        } catch (PDOException $e) {
            logError('Erro ao gerar dados para gráficos do OPR: ' . $e->getMessage());
            return [
                'horas_por_projeto' => [],
                'horas_por_dia' => []
            ];
        }
    }
    
/**
     * Gerar relatório completo do OPR em formato pronto para uso
     * 
     * @param int $oprId ID do OPR
     * @return array|false Dados formatados do OPR ou false em caso de erro
     */
    public function gerarRelatorio($oprId) {
        try {
            $opr = $this->getById($oprId);
            
            if (!$opr) {
                return false;
            }
            
            // Formatar datas
            $opr['data_geracao_formatada'] = formatDate($opr['data_geracao']);
            
            // Formatar status
            $statusClasses = [
                'Rascunho' => 'badge-warning',
                'Enviado' => 'badge-primary',
                'Aprovado' => 'badge-success',
                'Revisão' => 'badge-danger'
            ];
            
            $opr['status_classe'] = $statusClasses[$opr['status']] ?? '';
            
            // Calcular total geral
            $totalGeral = [
                'clientes' => count($opr['clientes']),
                'atividades' => count($opr['atividades_realizadas']),
                'proximas' => count($opr['proximas_atividades']),
                'riscos' => count($opr['riscos']),
                'mencoes' => count($opr['mencoes_projetos']),
                'horas' => $opr['total_horas_semana']
            ];
            
            $opr['total_geral'] = $totalGeral;
            
            // Formatar dados para gráficos
            $graficos = [
                'projetos' => [
                    'labels' => array_keys($opr['graficos']['horas_por_projeto']),
                    'data' => array_values($opr['graficos']['horas_por_projeto'])
                ],
                'dias' => [
                    'labels' => array_keys($opr['graficos']['horas_por_dia']),
                    'data' => array_values($opr['graficos']['horas_por_dia'])
                ]
            ];
            
            $opr['graficos_formatados'] = $graficos;
            
            return $opr;
        } catch (PDOException $e) {
            logError('Erro ao gerar relatório do OPR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar se já existe um OPR para o liderado na semana
     * 
     * @param int $lideradoId ID do liderado
     * @param string $semana Semana de referência
     * @param int $excluirId ID a excluir da verificação (para edição)
     * @return bool True se já existir
     */
    public function existeNaSemana($lideradoId, $semana, $excluirId = null) {
        try {
            $sql = "SELECT id FROM oprs WHERE liderado_id = ? AND semana = ?";
            $params = [$lideradoId, $semana];
            
            if ($excluirId) {
                $sql .= " AND id != ?";
                $params[] = $excluirId;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            logError('Erro ao verificar OPR na semana: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter último OPR do liderado
     * 
     * @param int $lideradoId ID do liderado
     * @return array|false Último OPR ou false se não encontrado
     */
    public function getUltimoDoLiderado($lideradoId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM oprs
                WHERE liderado_id = ?
                ORDER BY data_geracao DESC
                LIMIT 1
            ");
            
            $stmt->execute([$lideradoId]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            logError('Erro ao obter último OPR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter sugestão de semana para novo OPR
     * 
     * @return string Semana sugerida no formato configurado
     */
    public function getSugestaoSemana() {
        $formato = getConfig('formato_semana_opr', 'SS-AAAA');
        $hoje = new DateTime();
        
        // Obter número da semana
        $semana = $hoje->format('W');
        $ano = $hoje->format('Y');
        
        // Substituir no formato configurado
        $resultado = str_replace('SS', $semana, $formato);
        $resultado = str_replace('AAAA', $ano, $resultado);
        $resultado = str_replace('AA', substr($ano, 2), $resultado);
        
        return $resultado;
    }
    
    /**
     * Método auxiliar para obter o dia da semana
     * 
     * @param string $data Data no formato Y-m-d
     * @return string Nome do dia da semana
     */
    private function obterDiaSemana($data) {
        $diasSemana = [
            0 => 'Domingo',
            1 => 'Segunda',
            2 => 'Terça', 
            3 => 'Quarta',
            4 => 'Quinta',
            5 => 'Sexta',
            6 => 'Sábado'
        ];
        
        $timestamp = strtotime($data);
        $diaSemanaNumero = date('w', $timestamp);
        
        return $diasSemana[$diaSemanaNumero];
    }
    
    /**
     * Obter OPRs pendentes de aprovação
     * 
     * @return array Lista de OPRs pendentes
     */
    public function getPendentesAprovacao() {
        try {
            $stmt = $this->conn->prepare("
                SELECT o.*, l.nome as liderado_nome, l.cargo as liderado_cargo
                FROM oprs o
                JOIN liderados l ON o.liderado_id = l.id
                WHERE o.status = 'Enviado'
                ORDER BY o.data_geracao ASC
            ");
            
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter OPRs pendentes: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter estatísticas gerais de OPRs
     * 
     * @param string $dataInicio Data inicial para filtro (opcional)
     * @param string $dataFim Data final para filtro (opcional)
     * @return array Estatísticas
     */
    public function getEstatisticas($dataInicio = null, $dataFim = null) {
        try {
            $params = [];
            $whereClause = "";
            
            if ($dataInicio) {
                $whereClause .= " WHERE o.data_geracao >= ?";
                $params[] = $dataInicio;
                
                if ($dataFim) {
                    $whereClause .= " AND o.data_geracao <= ?";
                    $params[] = $dataFim;
                }
            } else if ($dataFim) {
                $whereClause .= " WHERE o.data_geracao <= ?";
                $params[] = $dataFim;
            }
            
            // Total de OPRs
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total,
                       SUM(CASE WHEN status = 'Rascunho' THEN 1 ELSE 0 END) as rascunho,
                       SUM(CASE WHEN status = 'Enviado' THEN 1 ELSE 0 END) as enviado,
                       SUM(CASE WHEN status = 'Aprovado' THEN 1 ELSE 0 END) as aprovado,
                       SUM(CASE WHEN status = 'Revisão' THEN 1 ELSE 0 END) as revisao
                FROM oprs o
                $whereClause
            ");
            
            $stmt->execute($params);
            
            $totalOPRs = $stmt->fetch();
            
            // Distribuição por liderado
            $stmt = $this->conn->prepare("
                SELECT l.nome as liderado, COUNT(*) as total
                FROM oprs o
                JOIN liderados l ON o.liderado_id = l.id
                $whereClause
                GROUP BY o.liderado_id
                ORDER BY total DESC
                LIMIT 10
            ");
            
            $stmt->execute($params);
            
            $porLiderado = $stmt->fetchAll();
            
            // Total de horas apontadas em OPRs
            $stmt = $this->conn->prepare("
                SELECT SUM(total_horas_semana) as total
                FROM oprs o
                $whereClause
            ");
            
            $stmt->execute($params);
            
            $totalHoras = $stmt->fetch()['total'] ?? 0;
            
            return [
                'total_oprs' => $totalOPRs,
                'por_liderado' => $porLiderado,
                'total_horas' => $totalHoras
            ];
        } catch (PDOException $e) {
            logError('Erro ao obter estatísticas de OPRs: ' . $e->getMessage());
            return [];
        }
    }
}