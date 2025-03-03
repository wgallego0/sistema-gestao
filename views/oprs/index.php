<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <div class="main-content">
        <!-- Sidebar com opções -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- Conteúdo principal -->
        <div class="content">
            <div class="d-flex justify-content-between align-items-center">
                <h2>OnePageReports (OPRs)</h2>
                <div>
                    <a href="<?php echo BASE_URL; ?>/oprs.php?action=create" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Novo OPR
                    </a>
                    <?php if (hasPermission('gestor') || hasPermission('admin')): ?>
                        <a href="<?php echo BASE_URL; ?>/oprs.php?pendentes=1" class="btn btn-warning">
                            <i class="fa fa-clock"></i> Pendentes
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Filtros -->
            <?php if (hasPermission('gestor') || hasPermission('admin')): ?>
                <div class="card">
                    <form method="GET" action="<?php echo BASE_URL; ?>/oprs.php">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="liderado_id">Liderado</label>
                                <select id="liderado_id" name="liderado_id" class="form-control">
                                    <option value="">Todos os liderados</option>
                                    <?php foreach ($liderados as $liderado): ?>
                                        <option value="<?php echo $liderado['id']; ?>" <?php echo (isset($_GET['liderado_id']) && $_GET['liderado_id'] == $liderado['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($liderado['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="align-self: flex-end;">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                                <a href="<?php echo BASE_URL; ?>/oprs.php" class="btn btn-secondary">Limpar</a>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
            
            <!-- Listagem de OPRs -->
            <div class="card">
                <?php if (empty($oprs)): ?>
                    <p class="empty-message">Nenhum OPR encontrado</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Semana</th>
                                <?php if (hasPermission('gestor') || hasPermission('admin')): ?>
                                    <th>Liderado</th>
                                <?php endif; ?>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Horas</th>
                                <th>Clientes</th>
                                <th>Atividades</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($oprs as $opr): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($opr['semana']); ?></td>
                                    <?php if (hasPermission('gestor') || hasPermission('admin')): ?>
                                        <td><?php echo htmlspecialchars($opr['liderado_nome']); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo date('d/m/Y', strtotime($opr['data_geracao'])); ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php echo $opr['status'] === 'Aprovado' ? 'badge-success' : 
                                                ($opr['status'] === 'Enviado' ? 'badge-primary' : 
                                                    ($opr['status'] === 'Revisão' ? 'badge-warning' : 'badge-info')); ?>">
                                            <?php echo $opr['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($opr['total_horas_semana'], 1); ?>h</td>
                                    <td><?php echo $opr['total_clientes']; ?></td>
                                    <td><?php echo $opr['total_atividades']; ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/oprs.php?action=view&id=<?php echo $opr['id']; ?>" class="btn btn-sm" title="Visualizar">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <?php if ($opr['status'] === 'Rascunho' && 
                                                ($_SESSION[SESSION_PREFIX . 'liderado_id'] == $opr['liderado_id'] || hasPermission('admin'))): ?>
                                            <a href="<?php echo BASE_URL; ?>/oprs.php?action=edit&id=<?php echo $opr['id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <?php if (hasPermission('admin') || $_SESSION[SESSION_PREFIX . 'liderado_id'] == $opr['liderado_id']): ?>
                                                <button class="btn btn-sm btn-danger" data-delete="oprs" data-id="<?php echo $opr['id']; ?>" title="Excluir">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <a href="<?php echo BASE_URL; ?>/oprs.php?action=print&id=<?php echo $opr['id']; ?>" class="btn btn-sm btn-success" target="_blank" title="Imprimir">
                                            <i class="fa fa-print"></i>
                                        </a>
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