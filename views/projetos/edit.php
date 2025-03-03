<?php require_once __DIR__ . '/../includes/header.php'; ?>
<div class="container">
    <div class="main-content">
        <!-- Sidebar com opções -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
Copy    <!-- Conteúdo principal -->
    <div class="content">
        <div class="card">
            <h2>Editar Projeto</h2>
            <p>Preencha os dados para atualizar o projeto.</p>
            
            <form id="form-projeto" action="<?php echo BASE_URL; ?>/api/projetos.php?action=update" method="POST" data-ajax="true" data-reset="false" data-reload-page="true">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_PREFIX . 'csrf_token']; ?>">
                <input type="hidden" name="id" value="<?php echo $projeto['id']; ?>">
                
                <div class="form-group">
                    <label for="nome">Nome do Projeto</label>
                    <input type="text" id="nome" name="nome" class="form-control" required value="<?php echo htmlspecialchars($projeto['nome']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" class="form-control" rows="3"><?php echo htmlspecialchars($projeto['descricao']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="data_inicio">Data de Início</label>
                        <input type="date" id="data_inicio" name="data_inicio" class="form-control" required value="<?php echo $projeto['data_inicio']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="data_fim">Data de Fim (opcional)</label>
                        <input type="date" id="data_fim" name="data_fim" class="form-control" value="<?php echo $projeto['data_fim'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="Não iniciado" <?php echo $projeto['status'] === 'Não iniciado' ? 'selected' : ''; ?>>Não iniciado</option>
                        <option value="Em andamento" <?php echo $projeto['status'] === 'Em andamento' ? 'selected' : ''; ?>>Em andamento</option>
                        <option value="Concluído" <?php echo $projeto['status'] === 'Concluído' ? 'selected' : ''; ?>>Concluído</option>
                        <option value="Pausado" <?php echo $projeto['status'] === 'Pausado' ? 'selected' : ''; ?>>Pausado</option>
                        <option value="Cancelado" <?php echo $projeto['status'] === 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Atualizar Projeto</button>
                    <a href="<?php echo BASE_URL; ?>/projetos.php?action=view&id=<?php echo $projeto['id']; ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>