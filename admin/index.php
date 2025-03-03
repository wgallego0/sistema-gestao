<?php require_once __DIR__ . '/../config.php'; ?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <div class="main-content">
        <!-- Sidebar com opções -->
        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        
        <!-- Conteúdo principal -->
        <div class="content">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Painel Administrativo</h2>
            </div>
            
            <!-- Estatísticas do sistema -->
            <div class="card">
                <div class="dashboard d-flex gap-3">
                    <div class="stat-box card p-3">
                        <h3>Usuários</h3>
                        <p><?php echo (new Usuario())->getTotal(); ?> cadastrados</p>
                    </div>
                    <div class="stat-box card p-3">
                        <h3>Projetos</h3>
                        <p><?php echo (new Projeto())->getTotal(); ?> ativos</p>
                    </div>
                    <div class="stat-box card p-3">
                        <h3>Atividades</h3>
                        <p><?php echo (new Atividade())->getTotal(); ?> em andamento</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
