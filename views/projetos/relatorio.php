<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório do Projeto - <?php echo htmlspecialchars($projeto['nome']); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/styles.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <style>
        @media print {
            body {
                background-color: white;
            }
            .no-print {
                display: none !important;
            }
            .page {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fa fa-print"></i> Imprimir Relatório
            </button>
            <a href="<?php echo BASE_URL; ?>/projetos.php?action=view&id=<?php echo $projeto['id']; ?>" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Voltar
            </a>
        </div>
Copy    <div class="page">
        <div class="card">
            <h1>Relatório do Projeto: <?php echo htmlspecialchars($projeto['nome']); ?></h1>
            
            <div class="two-columns">
                <div>
                    <h3>Informações Básicas</h3>
                    <p><strong>Nome:</strong> <?php echo htmlspecialchars($projeto['nome']); ?></p>
                    <p><strong>Descrição:</strong> <?php echo htmlspecialchars($projeto['descricao'] ?? 'Sem descrição'); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($projeto['status']); ?></p>
                    <p><strong>Data de Início:</strong> <?php echo formatDate($projeto['data_inicio']); ?></p>
                    <p><strong>Data de Fim:</strong> <?php echo $projeto['data_fim'] ? formatDate($projeto['data_fim']) : 'Não definida'; ?></p>
                </div>
                
                <div>
                    <h3>Métricas Gerais</h3>
                    <div class="metrics">
                        <div class="metric-box">
                            <div class="metric-value"><?php echo $estatisticas['total_membros']; ?></div>
                            <div class="metric-label">Membros</div>
                        </div>
                        <div class="metric-box">
                            <div class="metric-value"><?php echo $estatisticas['total_atividades']; ?></div>
                            <div class="metric-label">Atividades</div>
                        </div>
                        <div class="metric-box">
                            <div class="metric-value"><?php echo number_format($estatisticas['total_horas'], 1); ?>h</div>
                            <div class="metric-label">Horas Totais</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page">
            <div class="card">
                <h3>Progresso do Projeto</h3>
                <div class="chart-container">
                    <canvas id="chart-progresso"></canvas>
                </div>
                
                <div class="two-columns">
                    <div>
                        <h4>Atividades por Status</h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Quantidade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Concluídas</td>
                                    <td><?php echo $estatisticas['progresso']['atividades_concluidas']; ?></td>
                                </tr>
                                <tr>
                                    <td>Em Andamento</td>
                                    <td><?php echo $estatisticas['progresso']['atividades_andamento']; ?></td>
                                </tr>
                                <tr>
                                    <td>Não Iniciadas</td>
                                    <td><?php echo $estatisticas['progresso']['atividades_nao_iniciadas']; ?></td>
                                </tr>
                                <tr>
                                    <td>Bloqueadas</td>
                                    <td><?php echo $estatisticas['progresso']['atividades_bloqueadas']; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div>
                        <h4>Informações Adicionais</h4>
                        <p><strong>Total de Atividades:</strong> <?php echo $estatisticas['total_atividades']; ?></p>
                        <p><strong>Percentual Concluído:</strong> <?php echo number_format($estatisticas['progresso']['percentual_concluido'], 2); ?>%</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="page">
            <div class="card">
                <h3>Horas por Liderado</h3>
                <div class="chart-container">
                    <canvas id="chart-horas-liderado"></canvas>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Liderado</th>
                            <th>Horas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estatisticas['horas_por_liderado'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['liderado']); ?></td>
                                <td><?php echo number_format($item['horas'], 1); ?>h</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="page">
            <div class="card">
                <h3>Horas por Atividade</h3>
                <div class="chart-container">
                    <canvas id="chart-horas-atividade"></canvas>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Atividade</th>
                            <th>Horas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estatisticas['horas_por_atividade'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['atividade']); ?></td>
                                <td><?php echo number_format($item['horas'], 1); ?>h</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                <?php echo $estatisticas['progresso']['atividades_concluidas'] ?? 0; ?>,
                <?php echo $estatisticas['progresso']['atividades_andamento'] ?? 0; ?>,
                <?php echo $estatisticas['progresso']['atividades_nao_iniciadas'] ?? 0; ?>,
                <?php echo $estatisticas['progresso']['atividades_bloqueadas'] ?? 0; ?>
            ],
            backgroundColor: [
                '#2ecc71',  // Concluídas (verde)
                '#3498db',  // Em Andamento (azul)
                '#f39c12',  // Não Iniciadas (laranja)
                '#e74c3c'   // Bloqueadas (vermelho)
            ]
        }]
    };

    new Chart(document.getElementById('chart-progresso'), {
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

    // Gráfico de Horas por Liderado
    const horasLideradoData = {
        labels: <?php echo json_encode(array_column($estatisticas['horas_por_liderado'], 'liderado')); ?>,
        datasets: [{
            label: 'Horas',
            data: <?php echo json_encode(array_column($estatisticas['horas_por_liderado'], 'horas')); ?>,
            backgroundColor: '#3498db',
            borderColor: '#2980b9',
            borderWidth: 1
        }]
    };

    new Chart(document.getElementById('chart-horas-liderado'), {
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
                    display: true                    ,
                    text: 'Horas Trabalhadas por Liderado'
                }
            }
        }
    });

    // Gráfico de Horas por Atividade
    const horasAtividadeData = {
        labels: <?php echo json_encode(array_column($estatisticas['horas_por_atividade'], 'atividade')); ?>,
        datasets: [{
            label: 'Horas',
            data: <?php echo json_encode(array_column($estatisticas['horas_por_atividade'], 'horas')); ?>,
            backgroundColor: '#2ecc71',
            borderColor: '#27ae60',
            borderWidth: 1
        }]
    };

    new Chart(document.getElementById('chart-horas-atividade'), {
        type: 'bar',
        data: horasAtividadeData,
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
                    text: 'Horas Trabalhadas por Atividade'
                }
            }
        }
    });

    // Iniciar impressão automática em produção
    // window.onload = function() { window.print(); };
});
</script>
</body>
</html>