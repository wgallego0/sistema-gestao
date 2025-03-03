<!-- Modificar em views/includes/sidebar.php -->

<div class="sidebar">
    <h3>Menu Rápido</h3>
    <ul>
        <?php if (hasPermission('gestor') || hasPermission('admin')): ?>
            <li><a href="<?php echo BASE_URL; ?>/liderados.php?action=create" style="text-decoration: none; color: inherit;">Novo Liderado</a></li>
            <li><a href="<?php echo BASE_URL; ?>/projetos.php?action=create" style="text-decoration: none; color: inherit;">Novo Projeto</a></li>
        <?php endif; ?>
        <li><a href="<?php echo BASE_URL; ?>/atividades.php?action=create" style="text-decoration: none; color: inherit;">Nova Atividade</a></li>
        <li><a href="<?php echo BASE_URL; ?>/oprs.php?action=create" style="text-decoration: none; color: inherit;">Novo OPR</a></li>
    </ul>
    
    <h3 style="margin-top: 20px;">Liderados</h3>
    <ul id="lista-liderados">
        <?php
        // Listar liderados se for gestor/admin
        if (hasPermission('gestor') || hasPermission('admin')) {
            $lideradoModel = new Liderado();
            $liderados = $lideradoModel->getAll();
            
            foreach ($liderados as $liderado) {
                echo '<li><a href="' . BASE_URL . '/liderados.php?action=view&id=' . $liderado['id'] . '" style="text-decoration: none; color: inherit;">' . htmlspecialchars($liderado['nome']) . '</a></li>';
            }
        }
        // Ou mostrar apenas o próprio liderado
        else if (isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
            $lideradoModel = new Liderado();
            $liderado = $lideradoModel->getById($_SESSION[SESSION_PREFIX . 'liderado_id']);
            
            if ($liderado) {
                echo '<li><a href="' . BASE_URL . '/liderados.php?action=view&id=' . $liderado['id'] . '" style="text-decoration: none; color: inherit;">' . htmlspecialchars($liderado['nome']) . '</a></li>';
            }
        }
        ?>
    </ul>
    
    <h3 style="margin-top: 20px;">Projetos</h3>
    <ul id="lista-projetos">
        <?php
        // Listar projetos (filtrados por acesso)
        $projetoModel = new Projeto();
        
        // Se for liderado comum, mostrar apenas seus projetos
        if (!hasPermission('gestor') && !hasPermission('admin') && isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
            $lideradoModel = new Liderado();
            $liderado = $lideradoModel->getById($_SESSION[SESSION_PREFIX . 'liderado_id']);
            
            if ($liderado && isset($liderado['projetos'])) {
                foreach ($liderado['projetos'] as $projeto) {
                    echo '<li><a href="' . BASE_URL . '/projetos.php?action=view&id=' . $projeto['projeto_id'] . '" style="text-decoration: none; color: inherit;">' . htmlspecialchars($projeto['projeto_nome']) . '</a></li>';
                }
            }
        }
        // Se for gestor/admin, mostrar todos
        else {
            $projetos = $projetoModel->getAll();
            
            foreach ($projetos as $projeto) {
                echo '<li><a href="' . BASE_URL . '/projetos.php?action=view&id=' . $projeto['id'] . '" style="text-decoration: none; color: inherit;">' . htmlspecialchars($projeto['nome']) . '</a></li>';
            }
        }
        ?>
    </ul>
    
    <?php if (hasPermission('gestor') || hasPermission('admin')): ?>
    <h3 style="margin-top: 20px;">Relatórios</h3>
    <ul>
        <li><a href="<?php echo BASE_URL; ?>/relatorios.php?tipo=apontamentos" style="text-decoration: none; color: inherit;">Apontamentos</a></li>
        <li><a href="<?php echo BASE_URL; ?>/relatorios.php?tipo=oprs" style="text-decoration: none; color: inherit;">OPRs</a></li>
        <li><a href="<?php echo BASE_URL; ?>/relatorios.php?tipo=projetos" style="text-decoration: none; color: inherit;">Projetos</a></li>
    </ul>
    <?php endif; ?>

    <div style="margin-top: 30px; font-size: 12px; color: #666;">
        <p>Usuário: <?php echo htmlspecialchars($_SESSION[SESSION_PREFIX . 'user_name']); ?></p>
        <p>Perfil: <?php echo ucfirst(htmlspecialchars($_SESSION[SESSION_PREFIX . 'user_type'])); ?></p>
    </div>
</div>