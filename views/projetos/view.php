<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <div class="main-content">
        <!-- Sidebar com opções -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- Conteúdo principal -->
        <div class="content">
            <div class="card">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>
                        <?php echo htmlspecialchars($projeto['nome']); ?>
                        <span class="badge 
                            <?php echo $projeto['status'] === 'Concluído' ? 'badge-success' : 
                                ($projeto['status'] === 'Em andamento' ? 'badge-primary' : 
                                    ($projeto['status'] === 'Pausado' ? 'badge-warning' : 'badge-secondary')); ?>">
                            <?php echo htmlspecialchars($projeto['status']); ?>
                        </span>
                    </h2>
                    <div>
                        <?php if (hasPermission('gestor') || hasPermission('admin')): ?>
                            <a href="<?php echo BASE_URL; ?>/projetos.php?action=edit&id=<?php echo $projeto['id']; ?>" class="btn btn-primary">
                                <i class="fa fa-edit"></i> Editar
                            </a>
                            <?php if (hasPermission('admin')): ?>
                                <button class="btn btn-danger" data-delete="projetos" data-id="<?php echo $projeto['id']; ?>">
                                    <i class="fa fa-trash"></i> Excluir
                                </button>
                            <?php endif; ?>
                            <a href="<?php echo BASE_URL; ?>/projetos.php?action=relatorio&id=<?php echo $projeto['id']; ?>" class="btn btn-success">
                                <i class="fa fa-file"></i> Relatório
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="two-columns">
                    <div>
                        <h3>Detalhes do Projeto</h3>
                        <p><strong>Descrição:</strong> <?php echo htmlspecialchars($projeto['descricao'] ?? 'Sem descrição'); ?></p>
                        <p><strong>Data de Início:</strong> <?php echo formatDate($projeto['data_inicio']); ?></p>
                        <p><strong>Data de Fim:</strong> <?php echo $projeto['data_fim'] ? formatDate($projeto['data_fim']) : 'Não definida'; ?></p>
                    </div>
                    
                    <div>
                        <h3>Métricas</h3>
                        <div class="metrics">
                            <div class="metric-box">
                                <div class="metric-value"><?php echo is_array($projeto['membros']) ? count($projeto['membros']) : 0; ?></div>
                                <div class="metric-label">Membros</div>
                            </div>
                            <div class="metric-box">
                                <div class="metric-value"><?php echo is_array($projeto['atividades']) ? count($projeto['atividades']) : 0; ?></div>
                                <div class="metric-label">Atividades</div>
                            </div>
                            <div class="metric-box">
                                <div class="metric-value"><?php echo isset($projeto['total_horas']) ? number_format($projeto['total_horas'], 1) : '0'; ?>h</div>
                                <div class="metric-label">Total de Horas</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="grid-2">
                <!-- Membros do Projeto -->
                <div class="card">
                    <h3>Membros</h3>
                    <?php if (empty($projeto['membros'])): ?>
                        <p class="empty-message">Nenhum membro associado</p>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Cargo</th>
                                    <th>Dedicação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projeto['membros'] as $membro): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/liderados.php?action=view&id=<?php echo $membro['liderado_id']; ?>">
                                                <?php echo htmlspecialchars($membro['liderado_nome']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($membro['liderado_cargo']); ?></td>
                                        <td><?php echo $membro['percentual_dedicacao']; ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <!-- Atividades do Projeto -->
                <div class="card">
                    <h3>Atividades</h3>
                    <?php if (empty($projeto['atividades'])): ?>
                        <p class="empty-message">Nenhuma atividade registrada</p>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Status</th>
                                    <th>Prioridade</th>
                                    <th>Horas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projeto['atividades'] as $atividade): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/atividades.php?action=view&id=<?php echo $atividade['id']; ?>">
                                                <?php echo htmlspecialchars($atividade['titulo']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo $atividade['status']; ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                echo $atividade['prioridade'] === 'Alta' ? 'badge-danger' : 
                                                    ($atividade['prioridade'] === 'Média' ? 'badge-warning' : 'badge-info'); 
                                            ?>">
                                                <?php echo $atividade['prioridade']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo number_format($atividade['horas_realizadas'], 1); ?> / 
                                            <?php echo number_format($atividade['horas_estimadas'], 1); ?>h
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Gráficos de Progresso -->
            <div class="grid-2">
                <div class="card">
                    <h3>Progresso do Projeto</h3>
                    <div class="chart-container">
                        <canvas id="chart-progresso"></canvas>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Horas por Liderado</h3>
                    <div class="chart-container">
                        <canvas id="chart-horas-liderado"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Apontamentos Recentes -->
            <div class="card">
                <h3>Apontamentos Recentes</h3>
                <?php if (empty($apontamentosRecentes)): ?>
                    <p class="empty-message">Nenhum apontamento registrado</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Liderado</th>
                                <th>Atividade</th>
                                <th>Horas</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apontamentosRecentes as $apontamento): ?>
                                <tr>
                                    <td><?php echo formatDate($apontamento['data']); ?></td>
                                    <td><?php echo htmlspecialchars($apontamento['liderado_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($apontamento['atividade_titulo'] ?? 'N/A'); ?></td>
                                    <td><?php echo number_format($apontamento['quantidade_horas'], 1); ?>h</td>
                                    <td><?php echo htmlspecialchars($apontamento['descricao'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de Progresso
    const progressoData = {
        labels: ['Concluídas', 'Em Andamento', 'Não Iniciadas', 'Bloqueadas'],
        datasets: [{
            data: [
                <?php 
                    echo isset($estatisticas['progresso']['atividades_concluidas']) 
                        ? $estatisticas['progresso']['atividades_concluidas'] 
                        : 0; 
                ?>,
                <?php 
                    echo isset($estatisticas['progresso']['atividades_andamento']) 
                        ? $estatisticas['progresso']['atividades_andamento'] 
                        : 0; 
                ?>,
                <?php 
                    echo isset($estatisticas['progresso']['atividades_nao_iniciadas']) 
                        ? $estatisticas['progresso']['atividades_nao_iniciadas'] 
                        : 0; 
                ?>,
                <?php 
                    echo isset($estatisticas['progresso']['atividades_bloqueadas']) 
                        ? $estatisticas['progresso']['atividades_bloqueadas'] 
                        : 0; 
                ?>
            ],
            backgroundColor: [
                '#2ecc71',  // Concluídas (verde)
                '#3498db',  // Em Andamento (azul)
                '#f39c12',  // Não Iniciadas (laranja)
                '#e74c3c'   // Bloqueadas (vermelho)
            ]
        }]
    };

    const progressoChart = document.getElementById('chart-progresso');
    if (progressoChart) {
        new Chart(progressoChart, {
            type: 'pie',
            data: progressoData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    title: {
                        display: true,
                        text: 'Progresso das Atividades'
                    }
                }
            }
        });
    }

    // Gráfico de Horas por Liderado
    const horasLideradoChart = document.getElementById('chart-horas-liderado');
    
    <?php
    $lideradoLabels = isset($estatisticas['horas_por_liderado']) 
        ? array_column($estatisticas['horas_por_liderado'], 'liderado') 
        : [];
    
    $lideradoHoras = isset($estatisticas['horas_por_liderado']) 
        ? array_column($estatisticas['horas_por_liderado'], 'horas') 
        : [];
    ?>

    if (horasLideradoChart) {
        const horasLideradoData = {
            labels: <?php echo json_encode($lideradoLabels); ?>,
            datasets: [{
                label: 'Horas',
                data: <?php echo json_encode($lideradoHoras); ?>,
                backgroundColor: '#3498db',
                borderColor: '#2980b9',
                borderWidth: 1
            }]
        };

        new Chart(horasLideradoChart, {
            type: 'bar',
            data: horasLideradoData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Horas'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Horas Trabalhadas por Liderado'
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>