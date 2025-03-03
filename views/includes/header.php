<?php
// Get theme settings
$themeSettings = [];
if (file_exists(__DIR__ . '/../../theme_manager.php')) {
    require_once __DIR__ . '/../../theme_manager.php';
    $themeManager = new ThemeManager();
    $themeSettings = $themeManager->getThemeSettings();
}

// Default settings if theme manager is not available
$showLogo = $themeSettings['show_logo'] ?? true;
$logoUrl = $themeSettings['logo_url'] ?? '/images/default-logo.png';
$siteName = $themeSettings['site_name'] ?? 'Sistema de Gestão de Equipes';
$sidebarPosition = $themeSettings['sidebar_position'] ?? 'left';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($siteName); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
</head>
<body class="<?php echo $sidebarPosition === 'right' ? 'sidebar-right' : ''; ?>">
    <!-- Notificação -->
    <div class="notification" id="notification"></div>

    <!-- Cabeçalho com navegação -->
    <header>
        <div class="logo-container">
            <?php if ($showLogo && !empty($logoUrl)): ?>
                <img src="<?php echo BASE_URL . $logoUrl; ?>" alt="Logo" class="site-logo">
            <?php endif; ?>
            <h2><?php echo htmlspecialchars($siteName); ?></h2>
        </div>
        <div class="navbar">
            <a href="<?php echo BASE_URL; ?>/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/liderados.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'liderados.php' ? 'active' : ''; ?>">Equipe</a>
            <a href="<?php echo BASE_URL; ?>/projetos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'projetos.php' ? 'active' : ''; ?>">Projetos</a>
            <a href="<?php echo BASE_URL; ?>/atividades.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'atividades.php' ? 'active' : ''; ?>">Atividades</a>
            <a href="<?php echo BASE_URL; ?>/oprs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'oprs.php' ? 'active' : ''; ?>">OPRs</a>
            <?php if (isLoggedIn() && hasPermission('admin')): ?>
                <a href="<?php echo BASE_URL; ?>/tema.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'tema.php' ? 'active' : ''; ?>">Tema</a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>/logout.php" title="Sair"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </header>