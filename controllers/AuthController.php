<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController {
    private $model;
    
    public function __construct() {
        $this->model = new Usuario();
    }
    
    /**
     * Exibir formulário de login
     */
    public function loginForm() {
        // Se já estiver logado, redirecionar para dashboard
        if (isLoggedIn()) {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
        
        include_once __DIR__ . '/../views/auth/login.php';
    }
    
    /**
     * Processar login
     */
    public function login() {
        // Se já estiver logado, redirecionar para dashboard
        if (isLoggedIn()) {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
        
        // Verificar token CSRF
        verifyCsrfToken();
        
        // Validar e sanitizar dados
        $email = sanitizeInput($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        
        if (empty($email) || empty($senha)) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Email e senha são obrigatórios';
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
        
        // Tentar fazer login
        $usuario = $this->model->login($email, $senha);
        
        if ($usuario) {
            // Registrar sessão
            $_SESSION[SESSION_PREFIX . 'user_id'] = $usuario['id'];
            $_SESSION[SESSION_PREFIX . 'user_name'] = $usuario['nome'];
            $_SESSION[SESSION_PREFIX . 'user_email'] = $usuario['email'];
            $_SESSION[SESSION_PREFIX . 'user_type'] = $usuario['tipo'];
            
            // Se for liderado, guardar o ID do liderado
            if ($usuario['liderado_id']) {
                $_SESSION[SESSION_PREFIX . 'liderado_id'] = $usuario['liderado_id'];
            }
            
            // Registrar último acesso
            $this->model->registrarAcesso($usuario['id']);
            
            // Redirecionar para dashboard
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        } else {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Email ou senha inválidos';
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
    }
    
    /**
     * Fazer logout
     */
    public function logout() {
        // Destruir sessão
        session_unset();
        session_destroy();
        
        // Redirecionar para página de login
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    
    /**
     * Exibir formulário para alterar senha
     */
    public function alterarSenhaForm() {
        // Verificar se está logado
        if (!isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
        
        include_once __DIR__ . '/../views/auth/alterar_senha.php';
    }
    
    /**
     * Processar alteração de senha
     */
    public function alterarSenha() {
        // Verificar se está logado
        if (!isLoggedIn()) {
            jsonResponse(['error' => 'Você precisa estar logado para realizar esta ação'], 401);
        }
        
        // Verificar token CSRF
        verifyCsrfToken();
        
        // Validar e sanitizar dados
        $senhaAtual = $_POST['senha_atual'] ?? '';
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';
        
        if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
            jsonResponse(['error' => 'Todos os campos são obrigatórios'], 400);
        }
        
        if ($novaSenha !== $confirmarSenha) {
            jsonResponse(['error' => 'As senhas não conferem'], 400);
        }
        
        if (strlen($novaSenha) < 6) {
            jsonResponse(['error' => 'A nova senha deve ter pelo menos 6 caracteres'], 400);
        }
        
        // Tentar alterar senha
        $userId = $_SESSION[SESSION_PREFIX . 'user_id'];
        $result = $this->model->alterarSenha($userId, $senhaAtual, $novaSenha);
        
        if ($result) {
            jsonResponse(['success' => true, 'message' => 'Senha alterada com sucesso']);
        } else {
            jsonResponse(['error' => 'Senha atual incorreta ou erro ao alterar senha'], 400);
        }
    }
    
    /**
     * Exibir formulário para recuperar senha
     */
    public function recuperarSenhaForm() {
        // Se já estiver logado, redirecionar para dashboard
        if (isLoggedIn()) {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
        
        include_once __DIR__ . '/../views/auth/recuperar_senha.php';
    }
    
    /**
     * Processar solicitação de recuperação de senha
     */
    public function recuperarSenha() {
        // Se já estiver logado, redirecionar para dashboard
        if (isLoggedIn()) {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
        
        // Verificar token CSRF
        verifyCsrfToken();
        
        // Validar e sanitizar dados
        $email = sanitizeInput($_POST['email'] ?? '');
        
        if (empty($email)) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Email é obrigatório';
            header('Location: ' . BASE_URL . '/recuperar_senha.php');
            exit;
        }
        
        // Verificar se email existe
        $usuario = $this->model->getByEmail($email);
        
        if (!$usuario) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Email não encontrado';
            header('Location: ' . BASE_URL . '/recuperar_senha.php');
            exit;
        }
        
        // Gerar token de recuperação
        $token = $this->model->gerarTokenRecuperacao($usuario['id']);
        
        if ($token) {
            // Enviar email com link de recuperação
            $resultado = $this->enviarEmailRecuperacao($email, $token);
            
            if ($resultado) {
                $_SESSION[SESSION_PREFIX . 'success'] = 'Um email com instruções para recuperar sua senha foi enviado';
            } else {
                $_SESSION[SESSION_PREFIX . 'error'] = 'Erro ao enviar email de recuperação';
            }
        } else {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Erro ao gerar token de recuperação';
        }
        
        header('Location: ' . BASE_URL . '/recuperar_senha.php');
        exit;
    }
    
    /**
     * Exibir formulário para redefinir senha
     */
    public function redefinirSenhaForm() {
        // Se já estiver logado, redirecionar para dashboard
        if (isLoggedIn()) {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
        
        // Validar token
        $token = sanitizeInput($_GET['token'] ?? '');
        
        if (empty($token)) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Token inválido';
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
        
        // Verificar se token é válido
        $usuario = $this->model->verificarTokenRecuperacao($token);
        
        if (!$usuario) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Token inválido ou expirado';
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
        
        include_once __DIR__ . '/../views/auth/redefinir_senha.php';
    }
    
    /**
     * Processar redefinição de senha
     */
    public function redefinirSenha() {
        // Se já estiver logado, redirecionar para dashboard
        if (isLoggedIn()) {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
        
        // Verificar token CSRF
        verifyCsrfToken();
        
        // Validar e sanitizar dados
        $token = sanitizeInput($_POST['token'] ?? '');
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';
        
        if (empty($token) || empty($novaSenha) || empty($confirmarSenha)) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Todos os campos são obrigatórios';
            header('Location: ' . BASE_URL . '/redefinir_senha.php?token=' . urlencode($token));
            exit;
        }
        
        if ($novaSenha !== $confirmarSenha) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'As senhas não conferem';
            header('Location: ' . BASE_URL . '/redefinir_senha.php?token=' . urlencode($token));
            exit;
        }
        
        if (strlen($novaSenha) < 6) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'A nova senha deve ter pelo menos 6 caracteres';
            header('Location: ' . BASE_URL . '/redefinir_senha.php?token=' . urlencode($token));
            exit;
        }
        
        // Verificar se token é válido
        $usuario = $this->model->verificarTokenRecuperacao($token);
        
        if (!$usuario) {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Token inválido ou expirado';
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
        
        // Redefinir senha
        $result = $this->model->redefinirSenha($usuario['id'], $novaSenha);
        
        if ($result) {
            $_SESSION[SESSION_PREFIX . 'success'] = 'Senha redefinida com sucesso. Faça login com sua nova senha.';
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        } else {
            $_SESSION[SESSION_PREFIX . 'error'] = 'Erro ao redefinir senha';
            header('Location: ' . BASE_URL . '/redefinir_senha.php?token=' . urlencode($token));
            exit;
        }
    }
    
    /**
     * Método auxiliar para enviar email de recuperação
     */
    private function enviarEmailRecuperacao($email, $token) {
        $destinatario = $email;
        $assunto = 'Recuperação de Senha - Sistema de Gestão de Equipes';
        
        $link = BASE_URL . '/redefinir_senha.php?token=' . urlencode($token);
        
        $mensagem = "
        <html>
        <head>
            <title>Recuperação de Senha</title>
        </head>
        <body>
            <h2>Recuperação de Senha</h2>
            <p>Você solicitou a recuperação de senha no Sistema de Gestão de Equipes.</p>
            <p>Clique no link abaixo para redefinir sua senha:</p>
            <p><a href=\"{$link}\">{$link}</a></p>
            <p>Este link é válido por 24 horas.</p>
            <p>Se você não solicitou a recuperação de senha, ignore este email.</p>
        </body>
        </html>
        ";
        
        // Cabeçalhos
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Sistema de Gestão de Equipes <no-reply@empresa.com>\r\n";
        
        // Enviar email
        return mail($destinatario, $assunto, $mensagem, $headers);
    }
}