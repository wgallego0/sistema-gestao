<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <div class="main-content">
        <!-- Sidebar com opções -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- Conteúdo principal -->
        <div class="content">
            <div class="card">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><?php echo htmlspecialchars($liderado['nome']); ?></h2>
                    <div>
                        <?php if (hasPermission('gestor') || hasPermission('admin')): ?>
                            <a href="<?php echo BASE_URL; ?>/liderados.php?action=edit&id=<?php echo $liderado['id']; ?>" class="btn btn-primary">
                                <i class="fa fa-edit"></i> Editar
                            </a>
                            <?php if (hasPermission('admin')): ?>
                                <button class="btn btn-danger" data-delete="liderados" data-id="<?php echo $liderado['id']; ?>" title="Excluir">
                                    <i class="fa fa-trash"></i> Excluir
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-success" data-modal="modal-associar" data-id="<?php echo $liderado['id']; ?>" title="Associar a Projeto">
                                <i class="fa fa-link"></i> Associar a Projeto
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Detalhes do Liderado -->
                <div class="grid-2">
                    <div>
                        <h3>Informações Pessoais</h3>
                        <p><strong>Nome:</strong> <?php echo htmlspecialchars($liderado['nome']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($liderado['email']); ?></p>
                        <p><strong>Cargo:</strong> <?php echo htmlspecialchars($liderado['cargo']); ?></p>
                        <p>
                            <strong>Cross-Funcional:</strong> 
                            <?php if ($liderado['cross_funcional']): ?>
                                <span class="badge badge-success">Sim</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Não</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div>
                        <h3>Estatísticas</h3>
                        <div class="metrics">
                            <div class="metric-box">
                                <div class="metric-value"><?php echo count($liderado['projetos']); ?></div>
                                <div class="metric-label">Projetos</div>
                            </div>
                            <div class="metric-box">
                                <div class="metric-value"><?php echo $estatisticas['total_atividades']; ?></div>
                                <div class="metric-label">Atividades</div>
                            </div>
                            <div class="metric-box">
                                <div class="metric-value"><?php echo number_format($estatisticas['horas_ultimo_mes'], 1); ?>h</div>
                                <div class="metric-label">Horas (30 dias)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Projetos do Liderado -->
            <div class="card">
                <h3>Projetos</h3>
                <?php if (empty($liderado['projetos'])): ?>
                    <p class="empty-message">Sem projetos associados</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Projeto</th>
                                <th>Status</th>
                                <th>Data Início</th>
                                <th>Dedicação</th>
                                <?php if (hasPermission('gestor') || hasPermission('admin')): ?>
                                    <th>Ações</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($liderado['projetos'] as $projeto): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/projetos.php?action=view&id=<?php echo $projeto['projeto_id']; ?>">
                                            <?php echo htmlspecialchars($projeto['projeto_nome']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($projeto['projeto_status']); ?></td>
                                    <td><?php echo formatDate($projeto['data_inicio']); ?></td>
                                    <td><?php echo $projeto['percentual_dedicacao']; ?>%</td>
                                    <?php if (hasPermission('gestor') || hasPermission('admin')): ?>
                                        <td>
                                            <button class="btn btn-sm btn-danger" data-modal="modal-remover-projeto" 
                                                    data-liderado-id="<?php echo $liderado['id']; ?>" 
                                                    data-projeto-id="<?php echo $projeto['projeto_id']; ?>"
                                                    data-projeto-nome="<?php echo htmlspecialchars($projeto['projeto_nome']); ?>">
                                                <i class="fa fa-unlink"></i> Remover
                                            </button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
                <?php if (hasPermission('gestor') || hasPermission('admin')): ?>
                    <button class="btn btn-primary" data-modal="modal-associar" data-id="<?php echo $liderado['id']; ?>">
                        <i class="fa fa-plus"></i> Associar a Projeto
                    </button>
                <?php endif; ?>
            </div>
            
            <!-- Gráfico de horas por projeto -->
            <div class="card">
                <h3>Horas por Projeto (Últimos 30 dias)</h3>
                <?php if (empty($estatisticas['horas_por_projeto'])): ?>
                    <p class="empty-message">Sem dados de horas para exibir</p>
                <?php else: ?>
                    <div class="chart-container">
                        <canvas id="chart-horas-projeto"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Associar Liderado a Projeto -->
<div class="modal" id="modal-associar">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Associar Liderado a Projeto</h3>
            <span class="modal-close">&times;</span>
        </div>
        <form id="form-associar" action="<?php echo BASE_URL; ?>/api/liderados.php?action=associar_projeto" method="POST" data-ajax="true" data-reload-page="true">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_PREFIX . 'csrf_token']; ?>">
            <input type="hidden" name="liderado_id" id="associar-liderado-id" value="<?php echo $liderado['id']; ?>">
            
            <div class="form-group">
                <label for="associar-projeto">Projeto</label>
                <select id="associar-projeto" name="projeto_id" class="form-control" required>
                    <option value="">Selecione um projeto</option>
                    <?php
                        $projetoModel = new Projeto();
                        $projetos = $projetoModel->getAll();
                        
                        foreach ($projetos as $proj):
                    ?>
                        <option value="<?php echo $proj['id']; ?>">
                            <?php echo htmlspecialchars($proj['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="associar-percentual">Percentual de Dedicação</label>
                <input type="number" id="associar-percentual" name="percentual" class="form-control" required min="1" max="100" value="100">
                <small class="form-help">Percentual de tempo dedicado a este projeto</small>
            </div>
            
            <div class="form-group">
                <label for="associar-data">Data de Início</label>
                <input type="date" id="associar-data" name="data_inicio" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Associar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para Remover Liderado de Projeto -->
<div class="modal" id="modal-remover-projeto">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Remover do Projeto</h3>
            <span class="modal-close">&times;</span>
        </div>
        <form id="form-remover-projeto" action="<?php echo BASE_URL; ?>/api/liderados.php?action=remover_projeto" method="POST" data-ajax="true" data-reload-page="true">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_PREFIX . 'csrf_token']; ?>">
            <input type="hidden" name="liderado_id" id="remover-liderado-id" value="">
            <input type="hidden" name="projeto_id" id="remover-projeto-id" value="">
            
            <div class="form-group">
                <p>Tem certeza que deseja remover o liderado <strong><?php echo htmlspecialchars($liderado['nome']); ?></strong> do projeto <strong id="remover-projeto-nome"></strong>?</p>
            </div>
            
            <div class="form-group">
                <label for="remover-data">Data de Saída</label>
                <input type="date" id="remover-data" name="data_fim" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-danger">Remover</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar gráfico de horas por projeto
    <?php if (!empty($estatisticas['horas_por_projeto'])): ?>
        const horasProjetoData = {
            labels: <?php echo json_encode(array_column($estatisticas['horas_por_projeto'], 'projeto')); ?>,
            datasets: [{
                label: 'Horas',
                data: <?php echo json_encode(array_column($estatisticas['horas_por_projeto'], 'horas')); ?>,
                backgroundColor: '#3498db',
                borderColor: '#2980b9',
                borderWidth: 1
            }]
        };

        new Chart(document.getElementById('chart-horas-projeto'), {
            type: 'bar',
            data: horasProjetoData,
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
    
    // Inicializar modal de remoção de projeto
    const removerButtons = document.querySelectorAll('[data-modal="modal-remover-projeto"]');
    removerButtons.forEach(button => {
        button.addEventListener('click', function() {
            const lideradoId = this.getAttribute('data-liderado-id');
            const projetoId = this.getAttribute('data-projeto-id');
            const projetoNome = this.getAttribute('data-projeto-nome');
            
            document.getElementById('remover-liderado-id').value = lideradoId;
            document.getElementById('remover-projeto-id').value = projetoId;
            document.getElementById('remover-projeto-nome').textContent = projetoNome;
            
            const modal = document.getElementById('modal-remover-projeto');
            if (modal) {
                modal.style.display = 'flex';
            }
        });
    });
    
    // Inicializar modal de associação (já tratado pelo initModals no main.js)
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>