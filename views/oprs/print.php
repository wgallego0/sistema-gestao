<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impressão de OPR - <?php echo htmlspecialchars($relatorio['semana']); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/styles.css">
    <style>
        @media print {
            body {
                background-color: white;
                font-size: 12pt;
            }
            .no-print {
                display: none !important;
            }
            .opr-page {
                border: none;
                box-shadow: none;
                margin: 0;
                padding: 0;
            }
            @page {
                size: A4;
                margin: 1cm;
            }
        }
        
        .print-header {
            background-color: #2c3e50;
            color: white;
            padding: 10px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .print-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }
        
        .print-btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <!-- Cabeçalho apenas para visualização em tela -->
    <div class="print-header no-print">
        <h2>Impressão de OPR - <?php echo htmlspecialchars($relatorio['semana']); ?></h2>
        <div>
            <button onclick="window.print()" class="print-btn">
                <i class="fa fa-print"></i> Imprimir
            </button>
            <a href="<?php echo BASE_URL; ?>/oprs.php?action=view&id=<?php echo $relatorio['id']; ?>" class="print-btn">
                <i class="fa fa-arrow-left"></i> Voltar
            </a>
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
    
    <!-- Scripts para imprimir automaticamente em produção -->
    <script>
    // Em produção, você pode descomentar esta linha para iniciar a impressão automaticamente
    // window.onload = function() { window.print(); };
    </script>
</body>
</html>