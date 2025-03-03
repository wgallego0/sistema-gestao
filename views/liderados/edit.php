<?php require_once __DIR__ . '/../includes/header.php'; ?>
<div class="container">
<div class="main-content">
<!-- Sidebar com opções -->
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<!-- Conteúdo principal -->
<div class="content">
        <div class="card">
            <h2>Editar Liderado</h2>
            <p>Edite os dados do liderado.</p>
            
            <form id="form-liderado" action="<?php echo BASE_URL; ?>/api/liderados.php?action=update" method="POST" data-ajax="true" data-reset="false">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_PREFIX . 'csrf_token']; ?>">
                <input type="hidden" name="id" value="<?php echo $liderado['id']; ?>">
                
                <div class="form-group">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" class="form-control" required value="<?php echo htmlspecialchars($liderado['nome']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($liderado['email']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="cargo">Cargo</label>
                    <input type="text" id="cargo" name="cargo" class="form-control" required value="<?php echo htmlspecialchars($liderado['cargo']); ?>">
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" id="cross_funcional" name="cross_funcional" class="form-check-input" <?php echo $liderado['cross_funcional'] ? 'checked' : ''; ?>>
                        <label for="cross_funcional" class="form-check-label">Cross-Funcional</label>
                    </div>
                    <small class="form-help">Marque esta opção se o liderado trabalha em múltiplos projetos ao mesmo tempo.</small>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Atualizar</button>
                    <a href="<?php echo BASE_URL; ?>/liderados.php?action=view&id=<?php echo $liderado['id']; ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>