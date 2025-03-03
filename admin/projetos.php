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
                <h2>Gerenciamento de Projetos</h2>
                <div>
                    <a href="<?php echo BASE_URL; ?>/admin/projetos.php?action=create" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Novo Projeto
                    </a>
                </div>
            </div>
            
            <!-- Listagem de projetos -->
            <div class="card">
                <?php $projetos = (new Projeto())->getAll(); ?>
                <?php if (empty($projetos)): ?>
                    <p class="empty-message">Nenhum projeto encontrado</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Data de Início</th>
                                <th>Data de Término</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projetos as $projeto): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($projeto['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($projeto['descricao']); ?></td>
                                    <td><?php echo htmlspecialchars($projeto['data_inicio']); ?></td>
                                    <td><?php echo htmlspecialchars($projeto['data_termino']); ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="<?php echo BASE_URL; ?>/admin/projetos.php?action=edit&id=<?php echo $projeto['id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <?php if (hasPermission('admin')): ?>
                                                <button class="btn btn-sm btn-danger" data-delete="projetos" data-id="<?php echo $projeto['id']; ?>" title="Excluir">
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