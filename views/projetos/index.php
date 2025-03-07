<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <div class="main-content">
        <!-- Sidebar com opções -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- Conteúdo principal -->
        <div class="content">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Projetos</h2>
                <div>
                    <a href="<?php echo BASE_URL; ?>/projetos.php?action=create" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Novo Projeto
                    </a>
                </div>
            </div>
            
            <!-- Listagem de projetos -->
            <div class="card">
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
                                    <td><?php echo isset($projeto['nome']) ? htmlspecialchars($projeto['nome']) : 'N/A'; ?></td>
                                    <td><?php echo isset($projeto['descricao']) ? htmlspecialchars($projeto['descricao']) : 'N/A'; ?></td>
                                    <td><?php echo isset($projeto['data_inicio']) ? htmlspecialchars($projeto['data_inicio']) : 'N/A'; ?></td>
                                    <td><?php echo isset($projeto['data_termino']) ? htmlspecialchars($projeto['data_termino']) : 'N/A'; ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="<?php echo BASE_URL; ?>/projetos.php?action=view&id=<?php echo $projeto['id']; ?>" class="btn btn-sm btn-light" title="Visualizar">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>/projetos.php?action=edit&id=<?php echo $projeto['id']; ?>" class="btn btn-sm btn-primary" title="Editar">
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
