<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <div class="main-content">
        <!-- Sidebar com opções -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- Conteúdo principal -->
        <div class="content">
            <div class="card">
                <h2>Novo Projeto</h2>
                <p>Preencha os dados para criar um novo projeto.</p>
                
                <form id="form-projeto" action="<?php echo BASE_URL; ?>/api/projetos.php?action=store" method="POST" data-ajax="true" data-reset="false" data-reload-page="true">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_PREFIX . 'csrf_token']; ?>">
                    
                    <div class="form-group">
                        <label for="nome">Nome do Projeto</label>
                        <input type="text" id="nome" name="nome" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <textarea id="descricao" name="descricao" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="data_inicio">Data de Início</label>
                            <input type="date" id="data_inicio" name="data_inicio" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="data_fim">Data de Fim (opcional)</label>
                            <input type="date" id="data_fim" name="data_fim" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="Não iniciado">Não iniciado</option>
                            <option value="Em andamento">Em andamento</option>
                            <option value="Concluído">Concluído</option>
                            <option value="Pausado">Pausado</option>
                            <option value="Cancelado">Cancelado</option>
                        </select>
                    </div>
                    
                    <h3>Membros do Projeto</h3>
                    <div class="card">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Selecionar</th>
                                    <th>Nome</th>
                                    <th>Cargo</th>
                                    <th>Percentual de Dedicação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($liderados as $liderado): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="liderados[]" value="<?php echo $liderado['id']; ?>" id="liderado-<?php echo $liderado['id']; ?>">
                                        </td>
                                        <td>
                                            <label for="liderado-<?php echo $liderado['id']; ?>"><?php echo htmlspecialchars($liderado['nome']); ?></label>
                                        </td>
                                        <td><?php echo htmlspecialchars($liderado['cargo']); ?></td>
                                        <td>
                                            <input type="number" name="percentual[<?php echo $liderado['id']; ?>]" class="form-control" min="1" max="100" value="100">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">Salvar Projeto</button>
                        <a href="<?php echo BASE_URL; ?>/projetos.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>