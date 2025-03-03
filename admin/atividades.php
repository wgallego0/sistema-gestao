<?php require_once __DIR__ . '/../config.php'; ?>
<?php require_once __DIR__ . '/../includes/auth.php'; ?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <div class="main-content">
        <!-- Sidebar com opções -->
        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        
        <!-- Conteúdo principal -->
        <div class="content">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Gerenciamento de Atividades</h2>
                <div>
                    <a href="<?php echo BASE_URL; ?>/admin/atividades.php?action=create" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Nova Atividade
                    </a>
                </div>
            </div>
            
            <!-- Listagem de atividades -->
            <div class="card">
                <?php $atividades = (new Atividade())->getAll(); ?>
                <?php if (empty($atividades)): ?>
                    <p class="empty-message">Nenhuma atividade encontrada</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Projeto</th>
                                <th>Responsável</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($atividades as $atividade): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($atividade['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($atividade['projeto_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($atividade['responsavel']); ?></td>
                                    <td>
                                        <span class="badge <?php echo ($atividade['status'] == 'Concluída') ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo htmlspecialchars($atividade['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="<?php echo BASE_URL; ?>/admin/atividades.php?action=edit&id=<?php echo $atividade['id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <?php if (hasPermission('admin')): ?>
                                                <button class="btn btn-sm btn-danger" data-delete="atividades" data-id="<?php echo $atividade['id']; ?>" title="Excluir">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>