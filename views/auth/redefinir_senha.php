<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Sistema de Gestão de Equipes</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="login-bg">
    <div class="auth-container">
        <div class="auth-logo">
            <h1>Sistema de Gestão de Equipes</h1>
        </div>
        
        <h2 class="auth-title">Redefinir Senha</h2>
        
        <?php if (isset($_SESSION[SESSION_PREFIX . 'error'])): ?>
            <div class="error-message">
                <?php 
                    echo $_SESSION[SESSION_PREFIX . 'error']; 
                    unset($_SESSION[SESSION_PREFIX . 'error']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION[SESSION_PREFIX . 'success'])): ?>
            <div class="success-message">
                <?php 
                    echo $_SESSION[SESSION_PREFIX . 'success']; 
                    unset($_SESSION[SESSION_PREFIX . 'success']);
                ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo BASE_URL; ?>/redefinir_senha.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_PREFIX . 'csrf_token']; ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
            
            <div class="form-group">
                <label for="nova_senha">Nova Senha</label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" id="nova_senha" name="nova_senha" class="form-control" required>
                </div>
                <small class="form-help">A senha deve ter pelo menos 6 caracteres.</small>
            </div>
            
            <div class="form-group">
                <label for="confirmar_senha">Confirmar Senha</label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Redefinir Senha</button>
            </div>
        </form>
        
        <div class="auth-links">
            <a href="<?php echo BASE_URL; ?>/login.php">Voltar para Login</a>
        </div>
    </div>
    
    <script>
        // Constantes para JS
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const CSRF_TOKEN = '<?php echo $_SESSION[SESSION_PREFIX . 'csrf_token']; ?>';
        const SESSION_ERROR = '<?php echo isset($_SESSION[SESSION_PREFIX . 'error']) ? $_SESSION[SESSION_PREFIX . 'error'] : ''; ?>';
        const SESSION_SUCCESS = '<?php echo isset($_SESSION[SESSION_PREFIX . 'success']) ? $_SESSION[SESSION_PREFIX . 'success'] : ''; ?>';
    </script>
    <script src="<?php echo BASE_URL; ?>/js/main.js"></script>
</body>
</html>