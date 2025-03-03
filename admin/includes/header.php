<?php
// Verifica se o usuário está autenticado e tem permissão de administrador
if (!isLoggedIn() || !hasPermission('admin')) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header>
        <div class="logo-container">
            <h2>Painel Administrativo</h2>
        </div>
        <nav class="navbar">
            <a href="<?php echo BASE_URL; ?>/admin/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/admin/usuarios.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>">Usuários</a>
            <a href="<?php echo BASE_URL; ?>/admin/projetos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'projetos.php' ? 'active' : ''; ?>">Projetos</a>
            <a href="<?php echo BASE_URL; ?>/admin/atividades.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'atividades.php' ? 'active' : ''; ?>">Atividades</a>
            <a href="<?php echo BASE_URL; ?>/logout.php" title="Sair"><i class="fas fa-sign-out-alt"></i></a>
        </nav>
    </header>
