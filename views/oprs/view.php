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
                        OnePageReport - <?php echo htmlspecialchars($relatorio['semana']); ?>
                        <span class="badge <?php echo $relatorio['status_classe']; ?>"><?php echo htmlspecialchars($relatorio['status']); ?></span>
                    </h2>
                    <div>
                        <?php if ($relatorio['status'] === 'Rascunho' && ($_SESSION[SESSION_PREFIX . 'liderado_id'] == $relatorio['liderado_id'] || hasPermission('admin'))): ?>
                            <a href="<?php echo BASE_URL; ?>/oprs.php?action=edit&id=<?php echo $relatorio['id']; ?>" class="btn btn-primary">
                                <i class="fa fa-edit"></i> Editar
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo BASE_URL; ?>/oprs.php?action=print&id=<?php echo $relatorio['id']; ?>" class="btn btn-success" target="_blank">
                            <i class="fa fa-print"></i> Imprimir
                        </a>
                        
                        <?php if (hasPermission('gestor') || hasPermission('admin')): ?>
                            <?php if ($relatorio['status'] === 'Enviado'): ?>
                                <button class="btn btn-success update-status" data-id="<?php echo $relatorio['id']; ?>" data-status="Aprovado">
                                    <i class="fa fa-check"></i> Aprovar
                                </button>
                                <button class="btn btn-warning update-status" data-id="<?php echo $relatorio['id']; ?>" data-status="Revisão">
                                    <i class="fa fa-undo"></i> Solicitar Revisão
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($relatorio['status'] === 'Rascunho' && $_SESSION[SESSION_PREFIX . 'liderado_id'] == $relatorio['liderado_id']): ?>
                            <button class="btn btn-primary update-status" data-id="<?php echo $relatorio['id']; ?>" data-status="Enviado">
                                <i class="fa fa-paper-plane"></i> Enviar
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- OPR em uma única página -->
            <div class="opr-page">
                <!-- Cabeçalho do OPR -->
                <div class="opr-header">
                    <div>
                        <h2>OnePageReport (OPR)</h2>
                        <p>Liderado: <strong><?php echo htmlspecialchars($relatorio['liderado_nome']); ?></strong></p>
                        <p>Cargo: <strong><?php echo htmlspecialchars($relatorio['liderado_cargo']); ?></strong></p>
                    </div>
                    <div style="text-align: right;">
                        <p>Semana: <strong><?php echo htmlspecialchars($relatorio['semana']); ?></strong></p>
                        <p>Status: <span class="badge <?php echo $relatorio['status_classe']; ?>"><?php echo htmlspecialchars($relatorio['status']); ?></span></p>
                        <p>Gerado em: <strong><?php echo htmlspecialchars($relatorio['data_geracao_formatada']); ?></strong></p>
                    </div>
                </div>
                
                <!-- Métricas principais -->
                <div class="metrics">
                    <div class="metric-box">
                        <div class="metric-value"><?php echo number_format($relatorio['total_horas_semana'], 1); ?>h</div>
                        <div class="metric-label">Horas Totais</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-value"><?php echo count($relatorio['clientes']); ?></div>
                        <div class="metric-label">Clientes</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-value"><?php echo count($relatorio['atividades_realizadas']); ?></div>
                        <div class="metric-label">Atividades</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-value"><?php echo count($relatorio['proximas_atividades']); ?></div>
                        <div class="metric-label">Próximas</div>
                    </div>
                </div>
                
                <?php if (!empty($relatorio['graficos_formatados']['projetos']['labels'])): ?>
                <div class="two-columns">
                    <!-- Gráfico de horas por projeto -->
                    <div class="chart-container">
                        <h3>Horas por Projeto</h3>
                        <canvas id="chart-projetos"></canvas>
                    </div>
                    
                    <!-- Gráfico de horas por dia -->
                    <div class="chart-container">
                        <h3>Horas por Dia</h3>
                        <canvas id="chart-dias"></canvas>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="two-columns">
                    <!-- Clientes Atendidos -->
                    <div class="opr-section">
                        <h3>Clientes Atendidos</h3>
                        <?php if (empty($relatorio['clientes'])): ?>
                            <p class="empty-message">Nenhum cliente registrado</p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($relatorio['clientes'] as $cliente): ?>
                                    <li>
                                        <strong><?php echo htmlspecialchars($cliente['cliente']); ?></strong>
                                        <?php if (!empty($cliente['descricao'])): ?>
                                            <p><?php echo htmlspecialchars($cliente['descricao']); ?></p>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Menções de Projetos -->
                    <div class="opr-section">
                        <h3>Menções de Projetos</h3>
                        <?php if (empty($relatorio['mencoes_projetos'])): ?>
                            <p class="empty-message">Nenhuma menção registrada</p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($relatorio['mencoes_projetos'] as $mencao): ?>
                                    <li>
                                        <?php if (!empty($mencao['projeto_nome'])): ?>
                                            <strong><?php echo htmlspecialchars($mencao['projeto_nome']); ?></strong>
                                            <?php if ($mencao['destaque']): ?>
                                                <span class="badge badge-primary">Destaque</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <p><?php echo htmlspecialchars($mencao['descricao']); ?></p>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Atividades Realizadas -->
                <div class="opr-section">
                    <h3>Atividades Realizadas</h3>
                    <?php if (empty($relatorio['atividades_realizadas'])): ?>
                        <p class="empty-message">Nenhuma atividade registrada</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($relatorio['atividades_realizadas'] as $atividade): ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($atividade['descricao']); ?></strong>
                                    <?php if (!empty($atividade['atividade_titulo'])): ?>
                                        <span class="badge badge-info"><?php echo htmlspecialchars($atividade['atividade_titulo']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($atividade['resultado'])): ?>
                                        <p>Resultado: <?php echo htmlspecialchars($atividade['resultado']); ?></p>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <div class="two-columns">
                    <!-- Próximas Atividades -->
                    <div class="opr-section">
                        <h3>Próximas Atividades</h3>
                        <?php if (empty($relatorio['proximas_atividades'])): ?>
                            <p class="empty-message">Nenhuma próxima atividade registrada</p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($relatorio['proximas_atividades'] as $proxima): ?>
                                    <li>
                                        <strong><?php echo htmlspecialchars($proxima['descricao']); ?></strong>
                                        <div>
                                            <span class="badge 
                                                <?php echo $proxima['prioridade'] === 'Alta' ? 'badge-danger' : 
                                                    ($proxima['prioridade'] === 'Média' ? 'badge-warning' : 'badge-info'); ?>">
                                                <?php echo htmlspecialchars($proxima['prioridade']); ?>
                                            </span>
                                            
                                            <?php if (!empty($proxima['data_limite'])): ?>
                                                <span>Data Limite: <?php echo formatDate($proxima['data_limite']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Riscos Identificados -->
                    <div class="opr-section">
                        <h3>Riscos Identificados</h3>
                        <?php if (empty($relatorio['riscos'])): ?>
                            <p class="empty-message">Nenhum risco registrado</p>
                        <?php else: ?>
                            <ul class="risco-list">
                                <?php foreach ($relatorio['riscos'] as $risco): ?>
                                    <?php
                                        $riskClass = $risco['impacto'] === 'Alto' ? 'risk-high' : 
                                                    ($risco['impacto'] === 'Médio' ? 'risk-medium' : 'risk-low');
                                    ?>
                                    <li class="risk-item <?php echo $riskClass; ?>">
                                        <div>
                                            <strong><?php echo htmlspecialchars($risco['descricao']); ?></strong>
                                            <div>
                                                <span>Impacto: <?php echo htmlspecialchars($risco['impacto']); ?></span> | 
                                                <span>Probabilidade: <?php echo htmlspecialchars($risco['probabilidade']); ?></span>
                                            </div>
                                            <?php if (!empty($risco['mitigacao'])): ?>
                                                <p>Mitigação: <?php echo htmlspecialchars($risco['mitigacao']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Apontamentos da Semana -->
                <div class="opr-section">
                    <h3>Apontamentos da Semana</h3>
                    <?php if (empty($relatorio['apontamentos'])): ?>
                        <p class="empty-message">Nenhum apontamento registrado</p>
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
                                <?php foreach ($relatorio['apontamentos'] as $apontamento): ?>
                                    <tr>
                                        <td><?php echo formatDate($apontamento['data']); ?></td>
                                        <td><?php echo htmlspecialchars($apontamento['projeto_nome'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($apontamento['atividade_titulo'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format($apontamento['quantidade_horas'], 1); ?>h</td>
                                        <td><?php echo htmlspecialchars($apontamento['descricao'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3">Total:</th>
                                    <th><?php echo number_format($relatorio['total_horas_semana'], 1); ?>h</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para gráficos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($relatorio['graficos_formatados']['projetos']['labels'])): ?>
        // Gráfico de horas por projeto
        new Chart(document.getElementById('chart-projetos'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($relatorio['graficos_formatados']['projetos']['labels']); ?>,
                datasets: [{
                    label: 'Horas',
                    data: <?php echo json_encode($relatorio['graficos_formatados']['projetos']['data']); ?>,
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
        
        // Gráfico de horas por dia
        new Chart(document.getElementById('chart-dias'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($relatorio['graficos_formatados']['dias']['labels']); ?>,
                datasets: [{
                    label: 'Horas',
                    data: <?php echo json_encode($relatorio['graficos_formatados']['dias']['data']); ?>,
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
    
    // Atualizar status do OPR
    const statusButtons = document.querySelectorAll('.update-status');
    statusButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            
            if (confirm(`Tem certeza que deseja alterar o status para "${status}"?`)) {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('status', status);
                formData.append('csrf_token', CSRF_TOKEN);
                
                fetch('<?php echo BASE_URL; ?>/api/oprs.php?action=update_status', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message || 'Status atualizado com sucesso', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotification(data.error || 'Erro ao atualizar status', 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showNotification('Erro de comunicação com o servidor', 'error');
                });
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>