<?php
require_once __DIR__ . '/../config.php';

class Usuario {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Autenticar usuário
     * 
     * @param string $email Email do usuário
     * @param string $senha Senha do usuário
     * @return array|false Dados do usuário ou false se falhar
     */
    public function login($email, $senha) {
        try {
            $stmt = $this->conn->prepare("
                SELECT u.*, l.id as liderado_id
                FROM usuarios u
                LEFT JOIN liderados l ON u.liderado_id = l.id
                WHERE u.email = ? AND u.ativo = 1
            ");
            
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();
            
            if (!$usuario) {
                return false;
            }
            
            // Verificar senha
            if (!password_verify($senha, $usuario['senha'])) {
                return false;
            }
            
            return $usuario;
        } catch (PDOException $e) {
            logError('Erro ao autenticar usuário: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter usuário por ID
     * 
     * @param int $id ID do usuário
     * @return array|false Dados do usuário ou false se não encontrado
     */
    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT u.*, l.id as liderado_id
                FROM usuarios u
                LEFT JOIN liderados l ON u.liderado_id = l.id
                WHERE u.id = ?
            ");
            
            $stmt->execute([$id]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            logError('Erro ao obter usuário por ID: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter usuário por email
     * 
     * @param string $email Email do usuário
     * @return array|false Dados do usuário ou false se não encontrado
     */
    public function getByEmail($email) {
        try {
            $stmt = $this->conn->prepare("
                SELECT u.*, l.id as liderado_id
                FROM usuarios u
                LEFT JOIN liderados l ON u.liderado_id = l.id
                WHERE u.email = ?
            ");
            
            $stmt->execute([$email]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            logError('Erro ao obter usuário por email: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar acesso do usuário
     * 
     * @param int $id ID do usuário
     * @return bool Sucesso ou falha na operação
     */
    public function registrarAcesso($id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE usuarios
                SET ultimo_acesso = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            logError('Erro ao registrar acesso: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Adicionar novo usuário
     * 
     * @param array $data Dados do usuário
     * @return int|false ID do usuário inserido ou false em caso de erro
     */
    public function add($data) {
        try {
            $this->conn->beginTransaction();
            
            // Hash da senha
            $senhaHash = password_hash($data['senha'], PASSWORD_DEFAULT);
            
            $stmt = $this->conn->prepare("
                INSERT INTO usuarios (nome, email, senha, tipo, liderado_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['nome'],
                $data['email'],
                $senhaHash,
                $data['tipo'],
                $data['liderado_id'] ?? null
            ]);
            
            $id = $this->conn->lastInsertId();
            
            // Registrar no log
            logActivity('usuarios', $id, 'INSERT', null, [
                'nome' => $data['nome'],
                'email' => $data['email'],
                'tipo' => $data['tipo'],
                'liderado_id' => $data['liderado_id'] ?? null
            ]);
            
            $this->conn->commit();
            
            return $id;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao adicionar usuário: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualizar usuário existente
     * 
     * @param int $id ID do usuário
     * @param array $data Novos dados do usuário
     * @return bool Sucesso ou falha na operação
     */
    public function update($id, $data) {
        try {
            $this->conn->beginTransaction();
            
            // Obter dados antigos para o log
            $oldData = $this->getById($id);
            
            if (!$oldData) {
                throw new Exception("Usuário não encontrado");
            }
            
            // Preparar query base
            $sql = "UPDATE usuarios SET nome = ?, email = ?, tipo = ?, liderado_id = ?";
            $params = [
                $data['nome'],
                $data['email'],
                $data['tipo'],
                $data['liderado_id'] ?? null
            ];
            
            // Adicionar senha à query se fornecida
            if (!empty($data['senha'])) {
                $sql .= ", senha = ?";
                $params[] = password_hash($data['senha'], PASSWORD_DEFAULT);
            }
            
            // Adicionar ID ao final dos parâmetros
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute($params);
            
            // Registrar no log
            $logData = [
                'nome' => $data['nome'],
                'email' => $data['email'],
                'tipo' => $data['tipo'],
                'liderado_id' => $data['liderado_id'] ?? null
            ];
            
            logActivity('usuarios', $id, 'UPDATE', $oldData, $logData);
            
            $this->conn->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao atualizar usuário: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Desativar um usuário
     * 
     * @param int $id ID do usuário
     * @return bool Sucesso ou falha na operação
     */
    public function delete($id) {
        try {
            $this->conn->beginTransaction();
            
            // Obter dados antigos para o log
            $oldData = $this->getById($id);
            
            if (!$oldData) {
                throw new Exception("Usuário não encontrado");
            }
            
            // Apenas desativar, não excluir definitivamente
            $stmt = $this->conn->prepare("UPDATE usuarios SET ativo = 0 WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            // Registrar no log
            logActivity('usuarios', $id, 'DELETE', $oldData, null);
            
            $this->conn->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao desativar usuário: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Alterar senha do usuário
     * 
     * @param int $id ID do usuário
     * @param string $senhaAtual Senha atual
     * @param string $novaSenha Nova senha
     * @return bool Sucesso ou falha na operação
     */
    public function alterarSenha($id, $senhaAtual, $novaSenha) {
        try {
            // Obter usuário e verificar senha atual
            $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $usuario = $stmt->fetch();
            
            if (!$usuario || !password_verify($senhaAtual, $usuario['senha'])) {
                return false;
            }
            
            // Atualizar senha
            $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            
            $stmt = $this->conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $result = $stmt->execute([$novaSenhaHash, $id]);
            
            // Registrar no log
            logActivity('usuarios', $id, 'UPDATE', ['action' => 'senha_alterada'], null);
            
            return $result;
        } catch (PDOException $e) {
            logError('Erro ao alterar senha: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gerar token de recuperação de senha
     * 
     * @param int $id ID do usuário
     * @return string|false Token gerado ou false em caso de erro
     */
    public function gerarTokenRecuperacao($id) {
        try {
            // Gerar token aleatório
            $token = bin2hex(random_bytes(32));
            
            // Definir expiração (24 horas)
            $expiracao = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Salvar token no banco de dados (supondo uma tabela de tokens)
            $stmt = $this->conn->prepare("
                INSERT INTO tokens_recuperacao (usuario_id, token, expiracao)
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([$id, $token, $expiracao]);
            
            return $token;
        } catch (PDOException $e) {
            logError('Erro ao gerar token de recuperação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar token de recuperação
     * 
     * @param string $token Token de recuperação
     * @return array|false Dados do usuário ou false se token inválido
     */
    public function verificarTokenRecuperacao($token) {
        try {
            $stmt = $this->conn->prepare("
                SELECT u.*
                FROM tokens_recuperacao t
                JOIN usuarios u ON t.usuario_id = u.id
                WHERE t.token = ? AND t.expiracao > CURRENT_TIMESTAMP AND t.utilizado = 0
            ");
            
            $stmt->execute([$token]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            logError('Erro ao verificar token de recuperação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Redefinir senha do usuário
     * 
     * @param int $id ID do usuário
     * @param string $novaSenha Nova senha
     * @return bool Sucesso ou falha na operação
     */
    public function redefinirSenha($id, $novaSenha) {
        try {
            $this->conn->beginTransaction();
            
            // Atualizar senha
            $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            
            $stmt = $this->conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $result = $stmt->execute([$novaSenhaHash, $id]);
            
            // Marcar todos os tokens deste usuário como utilizados
            $stmt = $this->conn->prepare("
                UPDATE tokens_recuperacao
                SET utilizado = 1
                WHERE usuario_id = ?
            ");
            
            $stmt->execute([$id]);
            
            // Registrar no log
            logActivity('usuarios', $id, 'UPDATE', ['action' => 'senha_redefinida'], null);
            
            $this->conn->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            logError('Erro ao redefinir senha: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter todos os usuários
     * 
     * @param bool $apenasAtivos Retornar apenas usuários ativos
     * @return array Lista de usuários
     */
    public function getAll($apenasAtivos = true) {
        try {
            $sql = "
                SELECT u.*, l.nome as liderado_nome
                FROM usuarios u
                LEFT JOIN liderados l ON u.liderado_id = l.id
            ";
            
            if ($apenasAtivos) {
                $sql .= " WHERE u.ativo = 1";
            }
            
            $sql .= " ORDER BY u.nome";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError('Erro ao obter usuários: ' . $e->getMessage());
            return [];
        }
    }
}