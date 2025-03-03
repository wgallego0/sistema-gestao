<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <div class="main-content">
        <!-- Sidebar com opções -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- Conteúdo principal -->
        <div class="content">
            <h2>Dashboard</h2>
            
            <?php if (!hasPermission('gestor') && !hasPermission('admin') && isset($_SESSION[SESSION_PREFIX . 'liderado_id'])): ?>
                <!-- Dashboard para liderados -->
                <div class="grid">
                    <div class="card">
                        <h3>Meus Projetos</h3>
                        <?php if (empty($dados['projetos'])): ?>
                            <p class="empty-message">Você não está associado a nenhum projeto</p>
                        <?php else: ?>
                            <div class="metrics">
                                <?php foreach ($dados['projetos'] as $index => $projeto): ?>
                                    <?php if ($index < 4): ?>
                                        <div class="metric-box">
                                            <div class="metric-value"><?php echo $projeto['percentual_dedicacao']; ?>%</div>
                                            <div class="metric-label"><?php echo htmlspecialchars($projeto['projeto_nome']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (count($dados['projetos']) > 4): ?>
                                <p><a href="<?php echo BASE_URL; ?>/projetos.php">Ver todos os projetos...</a></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card">
                        <h3>Minhas Atividades</h3>
                        <?php if (empty($dados['atividades_pendentes'])): ?>
                            <p class="empty-message">Você não tem atividades pendentes</p>
                        <?php else: ?>
                            <div class="metrics">
                                <div class="metric-box">
                                    <div class="metric-value"><?php echo count(array_filter($dados['atividades_pendentes'], function($a) { return $a['status'] === 'Em andamento'; })); ?></div>
                                    <div class="metric-label">Em andamento</div>
                                </div>
                                <div class="metric-box">
                                    <div class="metric-value"><?php echo count(array_filter($dados['atividades_pendentes'], function($a) { return $a['status'] === 'Não iniciada'; })); ?></div>
                                    <div class="metric-label">Não iniciadas</div>
                                </div>
                                <div class="metric-box">
                                    <div class="metric-value"><?php echo count(array_filter($dados['atividades_pendentes'], function($a) { return $a['status'] === 'Bloqueada'; })); ?></div>
                                    <div class="metric-label">Bloqueadas</div>
                                </div>
                            </div>
                            
                            <h4>Atividades Pendentes</h4>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Projeto</th>
                                        <th>Prioridade</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($dados['atividades_pendentes'], 0, 5) as $atividade): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($atividade['titulo']); ?></td>
                                            <td><?php echo htmlspecialchars($atividade['projeto_nome'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge <?php echo $atividade['prioridade'] === 'Alta' ? 'badge-danger' : ($atividade['prioridade'] === 'Média' ? 'badge-warning' : 'badge-info'); ?>">
                                                    <?php echo $atividade['prioridade']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $atividade['status']; ?></td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/atividades.php?action=view&id=<?php echo $atividade['id']; ?>" class="btn btn-sm">Detalhes</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <?php if (count($dados['atividades_pendentes']) > 5): ?>
                                <p><a href="<?php echo BASE_URL; ?>/atividades.php">Ver todas as atividades...</a></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="grid">
                    <div class="card">
                        <h3>Meus OPRs</h3>
                        <?php if (empty($dados['oprs'])): ?>
                            <p class="empty-message">Você ainda não criou nenhum OPR</p>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Semana</th>
                                        <th>Data</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($dados['oprs'], 0, 5) as $opr): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($opr['semana']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($opr['data_geracao'])); ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php echo $opr['status'] === 'Aprovado' ? 'badge-success' : 
                                                        ($opr['status'] === 'Enviado' ? 'badge-primary' : 
                                                            ($opr['status'] === 'Revisão' ? 'badge-warning' : 'badge-info')); ?>">
                                                    <?php echo $opr['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/oprs.php?action=view&id=<?php echo $opr['id']; ?>" class="btn btn-sm">Visualizar</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <?php if (count($dados['oprs']) > 5): ?>
                                <p><a href="<?php echo BASE_URL; ?>/oprs.php">Ver todos os OPRs...</a></p>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (isset($dados['tem_opr_semana_atual']) && !$dados['tem_opr_semana_atual']): ?>
                            <div class="alert alert-info">
                                <p>Você ainda não criou o OPR desta semana.</p>
                                <a href="<?php echo BASE_URL; ?>/oprs.php?action=create" class="btn btn-primary">Criar OPR da Semana</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card">
                        <h3>Horas por Projeto</h3>
                        <?php if (empty($dados['horas_por_projeto'])): ?>
                            <p class="empty-message">Nenhum apontamento de horas registrado</p>
                        <?php else: ?>
                            <div class="chart-container">
                                <canvas id="chart-horas-projeto"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Últimos Apontamentos</h3>
                    <?php if (empty($dados['apontamentos_recentes'])): ?>
                        <p class="empty-message">Nenhum apontamento de horas registrado</p>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Projeto</th>
                                    <th>Atividade</th>
                                    <th>Horas</th>
                                    <th>Descrição</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dados['apontamentos_recentes'] as $apontamento): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($apontamento['data'])); ?></td>
                                        <td><?php echo htmlspecialchars($apontamento['projeto_nome'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($apontamento['atividade_titulo'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format($apontamento['quantidade_horas'], 1); ?>h</td>
                                        <td><?php echo htmlspecialchars($apontamento['descricao'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                    
                    <p><a href="<?php echo BASE_URL; ?>/atividades.php?tab=apontamento-horas" class="btn btn-primary">Apontar Horas</a></p>
                </div>
                
            <?php else: ?>
                <!-- Dashboard para gestores e administradores -->
                <div class="grid">
                    <div class="card">
                        <h3>Visão Geral</h3>
                        <div class="metrics">
                            <div class="metric-box">
                                <div class="metric-value"><?php echo $dados['total_liderados']; ?></div>
                                <div class="metric-label">Liderados</div>
                            </div>
                            <div class="metric-box">
                                <div class="metric-value"><?php echo $dados['total_projetos']; ?></div>
                                <div class="metric-label">Projetos</div>
                            </div>
                            <div class="metric-box">
                                <div class="metric-value"><?php echo $dados['total_atividades']; ?></div>
                                <div class="metric-label">Atividades</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h3>Projetos por Status</h3>
                        <?php if (empty($dados['projetos_por_status'])): ?>
                            <p class="empty-message">Nenhum projeto registrado</p>
                        <?php else: ?>
                            <div class="chart-container">
                                <canvas id="chart-projetos-status"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="grid">
                    <div class="card">
                        <h3>Horas por Projeto</h3>
                        <?php if (empty($dados['horas_por_projeto'])): ?>
                            <p class="empty-message">Nenhum apontamento de horas registrado</p>
                        <?php else: ?>
                            <div class="chart-container">
                                <canvas id="chart-horas-projeto"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card">
                        <h3>Horas por Liderado</h3>
                        <?php if (empty($dados['horas_por_liderado'])): ?>
                            <p class="empty-message">Nenhum apontamento de horas registrado</p>
                        <?php else: ?>
                            <div class="chart-container">
                                <canvas id="chart-horas-liderado"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="grid">
                    <div class="card">
                        <h3>Atividades Recentes</h3>
                        <?php if (empty($dados['atividades_recentes'])): ?>
                            <p class="empty-message">Nenhuma atividade registrada</p>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Projeto</th>
                                        <th>Responsáveis</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dados['atividades_recentes'] as $atividade): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($atividade['titulo']); ?></td>
                                            <td><?php echo htmlspecialchars($atividade['projeto_nome'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($atividade['responsaveis'] ?? 'N/A'); ?></td>
                                            <td><?php echo $atividade['status']; ?></td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/atividades.php?action=view&id=<?php echo $atividade['id']; ?>" class="btn btn-sm">Detalhes</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card">
                        <h3>OPRs Pendentes</h3>
                        <?php if (empty($dados['oprs_pendentes'])): ?>
                            <p class="empty-message">Nenhum OPR pendente de aprovação</p>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Liderado</th>
                                        <th>Semana</th>
                                        <th>Data</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dados['oprs_pendentes'] as $opr): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($opr['liderado_nome']); ?></td>
                                            <td><?php echo htmlspecialchars($opr['semana']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($opr['data_geracao'])); ?></td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/oprs.php?action=view&id=<?php echo $opr['id']; ?>" class="btn btn-sm">Avaliar</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <p><a href="<?php echo BASE_URL; ?>/oprs.php?pendentes=1">Ver todos os OPRs pendentes...</a></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Apontamentos Recentes</h3>
                    <?php if (empty($dados['apontamentos_recentes'])): ?>
                        <p class="empty-message">Nenhum apontamento de horas registrado</p>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Liderado</th>
                                    <th>Data</th>
                                    <th>Projeto</th>
                                    <th>Atividade</th>
                                    <th>Horas</th>
                                    <th>Descrição</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dados['apontamentos_recentes'] as $apontamento): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($apontamento['liderado_nome']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($apontamento['data'])); ?></td>
                                        <td><?php echo htmlspecialchars($apontamento['projeto_nome'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($apontamento['atividade_titulo'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format($apontamento['quantidade_horas'], 1); ?>h</td>
                                        <td><?php echo htmlspecialchars($apontamento['descricao'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Scripts para gráficos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de horas por projeto
    <?php if (!empty($dados['horas_por_projeto'])): ?>
        new Chart(document.getElementById('chart-horas-projeto'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($dados['horas_por_projeto'], 'projeto')); ?>,
                datasets: [{
                    label: 'Horas',
                    data: <?php echo json_encode(array_column($dados['horas_por_projeto'], 'horas')); ?>,
                    backgroundColor: '#3498db',
                    borderColor: '#2980b9',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    <?php endif; ?>
    
    // Gráfico de horas por liderado (apenas para gestores e admin)
    <?php if (hasPermission('gestor') || hasPermission('admin')): ?>
        <?php if (!empty($dados['horas_por_liderado'])): ?>
            new Chart(document.getElementById('chart-horas-liderado'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($dados['horas_por_liderado'], 'liderado')); ?>,
                    datasets: [{
                        label: 'Horas',
                        data: <?php echo json_encode(array_column($dados['horas_por_liderado'], 'horas')); ?>,
                        backgroundColor: '#2ecc71',
                        borderColor: '#27ae60',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        <?php endif; ?>
        
        <?php if (!empty($dados['projetos_por_status'])): ?>
            new Chart(document.getElementById('chart-projetos-status'), {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_keys($dados['projetos_por_status'])); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($dados['projetos_por_status'])); ?>,
                        backgroundColor: [
                            '#3498db', '#2ecc71', '#f39c12', '#e74c3c', '#9b59b6'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        <?php endif; ?>
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>