<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <div class="main-content">
        <!-- Sidebar com opções -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- Conteúdo principal -->
        <div class="content">
            <div class="d-flex justify-content-between align-items-center">
                <h2>OPRs Pendentes de Aprovação</h2>
                <div>
                    <a href="<?php echo BASE_URL; ?>/oprs.php" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Voltar para OPRs
                    </a>
                </div>
            </div>
            
            <!-- Listagem de OPRs pendentes -->
            <div class="card">
                <?php if (empty($pendentes)): ?>
                    <p class="empty-message">Não há OPRs pendentes de aprovação</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Semana</th>
                                <th>Liderado</th>
                                <th>Cargo</th>
                                <th>Data</th>
                                <th>Horas</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendentes as $opr): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($opr['semana']); ?></td>
                                    <td><?php echo htmlspecialchars($opr['liderado_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($opr['liderado_cargo']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($opr['data_geracao'])); ?></td>
                                    <td><?php echo number_format($opr['total_horas_semana'], 1); ?>h</td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/oprs.php?action=view&id=<?php echo $opr['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fa fa-check"></i> Avaliar
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/oprs.php?action=print&id=<?php echo $opr['id']; ?>" class="btn btn-sm btn-success" target="_blank">
                                            <i class="fa fa-print"></i> Imprimir
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