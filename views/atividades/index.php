<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <div class="main-content">
        <!-- Sidebar com opções -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- Conteúdo principal -->
        <div class="content">
            <!-- Título e botões de ação -->
            <div class="d-flex justify-content-between align-items-center">
                <h2>Atividades</h2>
                <div>
                    <a href="<?php echo BASE_URL; ?>/atividades.php?action=create" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Nova Atividade
                    </a>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card">
                <form method="GET" action="<?php echo BASE_URL; ?>/atividades.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="projeto_id">Projeto</label>
                            <select id="projeto_id" name="projeto_id" class="form-control">
                                <option value="">Todos os projetos</option>
                                <?php foreach ($projetos as $projeto): ?>
                                    <option value="<?php echo $projeto['id']; ?>" <?php echo (isset($_GET['projeto_id']) && $_GET['projeto_id'] == $projeto['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($projeto['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="">Todos os status</option>
                                <option value="Não iniciada" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Não iniciada') ? 'selected' : ''; ?>>Não iniciada</option>
                                <option value="Em andamento" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Em andamento') ? 'selected' : ''; ?>>Em andamento</option>
                                <option value="Concluída" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Concluída') ? 'selected' : ''; ?>>Concluída</option>
                                <option value="Bloqueada" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Bloqueada') ? 'selected' : ''; ?>>Bloqueada</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ativas">Mostrar</label>
                            <select id="ativas" name="ativas" class="form-control">
                                <option value="1" <?php echo (!isset($_GET['ativas']) || $_GET['ativas'] == '1') ? 'selected' : ''; ?>>Apenas ativas</option>
                                <option value="0" <?php echo (isset($_GET['ativas']) && $_GET['ativas'] == '0') ? 'selected' : ''; ?>>Todas</option>
                            </select>
                        </div>
                        <div class="form-group" style="align-self: flex-end;">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <a href="<?php echo BASE_URL; ?>/atividades.php" class="btn btn-secondary">Limpar</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Tabela de atividades -->
            <div class="card">
                <div class="tab-container">
                    <div class="tab-buttons">
                        <button class="tab-btn active" data-tab="tab-lista">Lista de Atividades</button>
                        <button class="tab-btn" data-tab="tab-apontamento">Apontar Horas</button>
                    </div>
                    
                    <!-- Tab de lista de atividades -->
                    <div id="tab-lista" class="tab-content active">
                        <?php if (empty($atividades)): ?>
                            <p class="empty-message">Nenhuma atividade encontrada com os filtros selecionados.</p>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Projeto</th>
                                        <th>Responsáveis</th>
                                        <th>Prioridade</th>
                                        <th>Status</th>
                                        <th>Período</th>
                                        <th>Horas</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($atividades as $atividade): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($atividade['titulo']); ?></td>
                                            <td><?php echo htmlspecialchars($atividade['projeto_nome'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($atividade['responsaveis'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge <?php echo $atividade['prioridade'] === 'Alta' ? 'badge-danger' : ($atividade['prioridade'] === 'Média' ? 'badge-warning' : 'badge-info'); ?>">
                                                    <?php echo $atividade['prioridade']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $atividade['status']; ?></td>
                                            <td>
                                                <?php if ($atividade['data_inicio']): ?>
                                                    <?php echo date('d/m/Y', strtotime($atividade['data_inicio'])); ?>
                                                    <?php if ($atividade['data_fim']): ?>
                                                        - <?php echo date('d/m/Y', strtotime($atividade['data_fim'])); ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    echo $atividade['horas_realizadas'] > 0 ? number_format($atividade['horas_realizadas'], 1) : '0'; 
                                                ?> / 
                                                <?php 
                                                    echo $atividade['horas_estimadas'] > 0 ? number_format($atividade['horas_estimadas'], 1) : '0'; 
                                                ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/atividades.php?action=view&id=<?php echo $atividade['id']; ?>" class="btn btn-sm" title="Visualizar">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>/atividades.php?action=edit&id=<?php echo $atividade['id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <?php if (hasPermission('gestor') || hasPermission('admin')): ?>
                                                    <button class="btn btn-sm btn-danger" data-delete="atividades" data-id="<?php echo $atividade['id']; ?>" title="Excluir">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Tab de apontamento de horas -->
                    <div id="tab-apontamento" class="tab-content">
                        <h3>Apontar Horas de Atividade</h3>
                        <form id="form-apontamento" action="<?php echo BASE_URL; ?>/api/apontamentos.php?action=store" method="POST" data-ajax="true" data-reload-table="tabela-apontamentos">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_PREFIX . 'csrf_token']; ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="atividade_id">Atividade</label>
                                    <select id="atividade_id" name="atividade_id" class="form-control">
                                        <option value="">Selecione uma atividade (opcional)</option>
                                        <?php foreach ($atividades as $atividade): ?>
                                            <option value="<?php echo $atividade['id']; ?>">
                                                <?php echo htmlspecialchars($atividade['titulo']); ?> 
                                                (<?php echo htmlspecialchars($atividade['projeto_nome'] ?? 'Sem projeto'); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="projeto_id_apontamento">Projeto</label>
                                    <select id="projeto_id_apontamento" name="projeto_id" class="form-control">
                                        <option value="">Selecione um projeto (opcional)</option>
                                        <?php foreach ($projetos as $projeto): ?>
                                            <option value="<?php echo $projeto['id']; ?>">
                                                <?php echo htmlspecialchars($projeto['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="data">Data</label>
                                    <input type="date" id="data" name="data" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="quantidade_horas">Quantidade de Horas</label>
                                    <input type="number" id="quantidade_horas" name="quantidade_horas" class="form-control" required min="0.1" max="24" step="0.1">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="descricao">Descrição</label>
                                <textarea id="descricao" name="descricao" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Registrar Horas</button>
                            </div>
                        </form>
                        
                        <h3>Apontamentos Recentes</h3>
                        <table class="table" id="tabela-apontamentos">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Projeto</th>
                                    <th>Atividade</th>
                                    <th>Horas</th>
                                    <th>Descrição</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $apontamentoModel = new Apontamento();
                                    $lideradoId = isset($_SESSION[SESSION_PREFIX . 'liderado_id']) ? $_SESSION[SESSION_PREFIX . 'liderado_id'] : null;
                                    $apontamentosRecentes = $apontamentoModel->getAll($lideradoId, null, date('Y-m-d', strtotime('-30 days')));
                                    
                                    if (empty($apontamentosRecentes)):
                                ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Nenhum apontamento encontrado nos últimos 30 dias.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($apontamentosRecentes, 0, 10) as $apontamento): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($apontamento['data'])); ?></td>
                                            <td><?php echo htmlspecialchars($apontamento['projeto_nome'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($apontamento['atividade_titulo'] ?? 'N/A'); ?></td>
                                            <td><?php echo number_format($apontamento['quantidade_horas'], 1); ?>h</td>
                                            <td><?php echo htmlspecialchars($apontamento['descricao'] ?? ''); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-danger" data-delete="apontamentos" data-id="<?php echo $apontamento['id']; ?>" title="Excluir">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se há tab na URL e ativar
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    
    if (tab === 'apontamento-horas') {
        document.querySelector('.tab-btn[data-tab="tab-apontamento"]').click();
    }
    
    // Vincular atividade e projeto
    const atividadeSelect = document.getElementById('atividade_id');
    const projetoSelect = document.getElementById('projeto_id_apontamento');
    
    atividadeSelect.addEventListener('change', function() {
        const atividadeId = this.value;
        if (!atividadeId) return;
        
        // Buscar dados da atividade
        fetch(`${BASE_URL}/api/atividades.php?id=${atividadeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.projeto_id) {
                    projetoSelect.value = data.data.projeto_id;
                }
            })
            .catch(error => console.error('Erro:', error));
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>