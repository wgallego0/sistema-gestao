<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestão de Equipes</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
</head>
<body>
    <!-- Notificação -->
    <div class="notification" id="notification"></div>

    <!-- Cabeçalho com navegação -->
    <header>
        <h2>Sistema de Gestão de Equipes</h2>
        <div class="navbar">
            <a href="<?php echo BASE_URL; ?>/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/liderados.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'liderados.php' ? 'active' : ''; ?>">Equipe</a>
            <a href="<?php echo BASE_URL; ?>/projetos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'projetos.php' ? 'active' : ''; ?>">Projetos</a>
            <a href="<?php echo BASE_URL; ?>/atividades.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'atividades.php' ? 'active' : ''; ?>">Atividades</a>
            <a href="<?php echo BASE_URL; ?>/oprs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'oprs.php' ? 'active' : ''; ?>">OPRs</a>
            <a href="<?php echo BASE_URL; ?>/logout.php" title="Sair"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </header>