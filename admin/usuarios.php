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
                <h2>Gerenciamento de Usuários</h2>
                <div>
                    <a href="<?php echo BASE_URL; ?>/admin/usuarios.php?action=create" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Novo Usuário
                    </a>
                </div>
            </div>
            
            <!-- Listagem de usuários -->
            <div class="card">
                <?php $usuarios = (new Usuario())->getAll(); ?>
                <?php if (empty($usuarios)): ?>
                    <p class="empty-message">Nenhum usuário encontrado</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Permissão</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['permissao']); ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="<?php echo BASE_URL; ?>/admin/usuarios.php?action=edit&id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <?php if (hasPermission('admin')): ?>
                                                <button class="btn btn-sm btn-danger" data-delete="usuarios" data-id="<?php echo $usuario['id']; ?>" title="Excluir">
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