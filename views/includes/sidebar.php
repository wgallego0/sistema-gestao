<div class="sidebar">
    <h3>Menu Rápido</h3>
    <ul>
        <?php if (hasPermission('gestor') || hasPermission('admin')): ?>
            <li data-action="novo_liderado">Novo Liderado</li>
            <li data-action="novo_projeto">Novo Projeto</li>
        <?php endif; ?>
        <li data-action="nova_atividade">Nova Atividade</li>
        <li data-action="novo_opr">Novo OPR</li>
    </ul>
    
    <h3 style="margin-top: 20px;">Liderados</h3>
    <ul id="lista-liderados">
        <?php
        // Listar liderados se for gestor/admin
        if (hasPermission('gestor') || hasPermission('admin')) {
            $lideradoModel = new Liderado();
            $liderados = $lideradoModel->getAll();
            
            foreach ($liderados as $liderado) {
                echo '<li data-id="' . $liderado['id'] . '">' . htmlspecialchars($liderado['nome']) . '</li>';
            }
        }
        // Ou mostrar apenas o próprio liderado
        else if (isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
            $lideradoModel = new Liderado();
            $liderado = $lideradoModel->getById($_SESSION[SESSION_PREFIX . 'liderado_id']);
            
            if ($liderado) {
                echo '<li data-id="' . $liderado['id'] . '">' . htmlspecialchars($liderado['nome']) . '</li>';
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
                    echo '<li data-id="' . $projeto['projeto_id'] . '">' . htmlspecialchars($projeto['projeto_nome']) . '</li>';
                }
            }
        }
        // Se for gestor/admin, mostrar todos
        else {
            $projetos = $projetoModel->getAll();
            
            foreach ($projetos as $projeto) {
                echo '<li data-id="' . $projeto['id'] . '">' . htmlspecialchars($projeto['nome']) . '</li>';
            }
        }
        ?>
    </ul>
    
    <?php if (hasPermission('gestor') || hasPermission('admin')): ?>
    <h3 style="margin-top: 20px;">Relatórios</h3>
    <ul>
        <li data-action="relatorio_horas">Apontamentos</li>
        <li data-action="relatorio_oprs">OPRs</li>
        <li data-action="relatorio_projetos">Projetos</li>
    </ul>
    <?php endif; ?>

    <div style="margin-top: 30px; font-size: 12px; color: #666;">
        <p>Usuário: <?php echo htmlspecialchars($_SESSION[SESSION_PREFIX . 'user_name']); ?></p>
        <p>Perfil: <?php echo ucfirst(htmlspecialchars($_SESSION[SESSION_PREFIX . 'user_type'])); ?></p>
    </div>
</div>