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
                                            
                                            echo $totalProjetos > 0 ? "$totalProjetos projeto(s)" : "Nenhum";
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $liderado['cross_funcional'] ? 'badge-success' : 'badge-secondary'; ?>">
                                            <?php echo $liderado['cross_funcional'] ? 'Sim' : 'Não'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2"> <!-- Alinha os botões lado a lado -->
                                            <a href="<?php echo BASE_URL; ?>/liderados.php?action=view&id=<?php echo $liderado['id']; ?>" class="btn btn-sm btn-light" title="Visualizar">
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
                                        </div>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
