<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <div class="main-content">
        <!-- Sidebar com opções -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- Conteúdo principal -->
        <div class="content">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Equipe</h2>
                <div>
                    <a href="<?php echo BASE_URL; ?>/liderados.php?action=create" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Novo Liderado
                    </a>
                </div>
            </div>
            
            <!-- Listagem de liderados -->
            <div class="card">
                <?php if (empty($liderados)): ?>
                    <p class="empty-message">Nenhum liderado encontrado</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Cargo</th>
                                <th>Projetos</th>
                                <th>Cross-Funcional</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($liderados as $liderado): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($liderado['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($liderado['email']); ?></td>
                                    <td><?php echo htmlspecialchars($liderado['cargo']); ?></td>
                                    <td>
                                        <?php 
                                            // Obter projetos do liderado (simplificado para a tabela)
                                            $projetos = (new Liderado())->getProjetos($liderado['id']);
                                            $totalProjetos = count($projetos);
                                            
                                            if ($totalProjetos > 0) {
                                                echo $totalProjetos . ' projeto(s)';
                                            } else {
                                                echo 'Nenhum';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($liderado['cross_funcional']): ?>
                                            <span class="badge badge-success">Sim</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Não</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/liderados.php?action=view&id=<?php echo $liderado['id']; ?>" class="btn btn-sm" title="Visualizar">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/liderados.php?action=edit&id=<?php echo $liderado['id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <?php if (hasPermission('admin')): ?>
                                            <button class="btn btn-sm btn-danger" data-delete="liderados" data-id="<?php echo $liderado['id']; ?>" title="Excluir">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-success" data-modal="modal-associar" data-id="<?php echo $liderado['id']; ?>" title="Associar a Projeto">
                                            <i class="fa fa-link"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Associar Liderado a Projeto -->
<div class="modal" id="modal-associar">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Associar Liderado a Projeto</h3>
            <span class="modal-close">&times;</span>
        </div>
        <form id="form-associar" action="<?php echo BASE_URL; ?>/api/liderados.php?action=associar_projeto" method="POST" data-ajax="true" data-reload-page="true">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_PREFIX . 'csrf_token']; ?>">
            <input type="hidden" name="liderado_id" id="associar-liderado-id" value="">
            
            <div class="form-group">
                <label for="associar-projeto">Projeto</label>
                <select id="associar-projeto" name="projeto_id" class="form-control" required>
                    <option value="">Selecione um projeto</option>
                    <?php
                        $projetoModel = new Projeto();
                        $projetos = $projetoModel->getAll();
                        
                        foreach ($projetos as $projeto):
                    ?>
                        <option value="<?php echo $projeto['id']; ?>">
                            <?php echo htmlspecialchars($projeto['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="associar-percentual">Percentual de Dedicação</label>
                <input type="number" id="associar-percentual" name="percentual" class="form-control" required min="1" max="100" value="100">
                <small class="form-help">Percentual de tempo dedicado a este projeto</small>
            </div>
            
            <div class="form-group">
                <label for="associar-data">Data de Início</label>
                <input type="date" id="associar-data" name="data_inicio" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Associar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>