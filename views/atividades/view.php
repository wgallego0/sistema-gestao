<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <div class="main-content">
        <!-- Sidebar com opções -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- Conteúdo principal -->
        <div class="content">
            <div class="card">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><?php echo htmlspecialchars($atividade['titulo']); ?></h2>
                    <div>
                        <a href="<?php echo BASE_URL; ?>/atividades.php?action=edit&id=<?php echo $atividade['id']; ?>" class="btn btn-primary">
                            <i class="fa fa-edit"></i> Editar
                        </a>
                        <?php if (hasPermission('gestor') || hasPermission('admin')): ?>
                            <button class="btn btn-danger" data-delete="atividades" data-id="<?php echo $atividade['id']; ?>">
                                <i class="fa fa-trash"></i> Excluir
                            </button>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>/atividades.php" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Voltar
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Detalhes da Atividade -->
            <div class="card">
                <h3>Detalhes da Atividade</h3>
                
                <div class="grid-2">
                    <div>
                        <p><strong>Título:</strong> <?php echo htmlspecialchars($atividade['titulo']); ?></p>
                        <p><strong>Projeto:</strong> <?php echo htmlspecialchars($projeto['nome'] ?? 'Sem projeto'); ?></p>
                        <p><strong>Status:</strong> <?php echo $atividade['status']; ?></p>
                        <p><strong>Prioridade:</strong> <?php echo $atividade['prioridade']; ?></p>
                    </div>
                    <div>
                        <p><strong>Data de Início:</strong> <?php echo $atividade['data_inicio'] ? date('d/m/Y', strtotime($atividade['data_inicio'])) : 'Não definida'; ?></p>
                        <p><strong>Data de Fim:</strong> <?php echo $atividade['data_fim'] ? date('d/m/Y', strtotime($atividade['data_fim'])) : 'Não definida'; ?></p>
                        <p><strong>Horas Estimadas:</strong> <?php echo number_format($atividade['horas_estimadas'], 1); ?> horas</p>
                        <p><strong>Horas Realizadas:</strong> <?php echo number_format($atividade['horas_realizadas'], 1); ?> horas</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Descrição:</label>
                    <div class="card" style="padding: 10px;">
                        <?php echo nl2br(htmlspecialchars($atividade['descricao'] ?? 'Sem descrição')); ?>
                    </div>
                </div>
                
                <h4>Responsáveis</h4>
                <?php if (empty($responsaveis)): ?>
                    <p class="empty-message">Nenhum responsável atribuído</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($responsaveis as $responsavel): ?>
                            <li><?php echo htmlspecialchars($responsavel['nome']); ?> (<?php echo htmlspecialchars($responsavel['cargo']); ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <!-- Atualizar Status -->
                <h4>Atualizar Status</h4>
                <form id="form-status" action="<?php echo BASE_URL; ?>/api/atividades.php?action=update_status" method="POST" data-ajax="true" data-reload-page="true">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_PREFIX . 'csrf_token']; ?>">
                    <input type="hidden" name="id" value="<?php echo $atividade['id']; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="status">Novo Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="Não iniciada" <?php echo $atividade['status'] === 'Não iniciada' ? 'selected' : ''; ?>>Não iniciada</option>
                                <option value="Em andamento" <?php echo $atividade['status'] === 'Em andamento' ? 'selected' : ''; ?>>Em andamento</option>
                                <option value="Concluída" <?php echo $atividade['status'] === 'Concluída' ? 'selected' : ''; ?>>Concluída</option>
                                <option value="Bloqueada" <?php echo $atividade['status'] === 'Bloqueada' ? 'selected' : ''; ?>>Bloqueada</option>
                            </select>
                        </div>
                        <div class="form-group" style="align-self: flex-end;">
                            <button type="submit" class="btn btn-primary">Atualizar Status</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Apontamentos de Horas -->
            <div class="card">
                <h3>Apontamentos de Horas</h3>
                
                <div class="tab-container">
                    <div class="tab-buttons">
                        <button class="tab-btn active" data-tab="tab-apontamentos-lista">Lista de Apontamentos</button>
                        <button class="tab-btn" data-tab="tab-apontamentos-novo">Novo Apontamento</button>
                    </div>
                    
                    <div id="tab-apontamentos-lista" class="tab-content active">
                        <?php if (empty($apontamentos)): ?>
                            <p class="empty-message">Nenhum apontamento registrado para esta atividade</p>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Liderado</th>
                                        <th>Horas</th>
                                        <th>Descrição</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($apontamentos as $apontamento): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($apontamento['data'])); ?></td>
                                            <td><?php echo htmlspecialchars($apontamento['liderado_nome']); ?></td>
                                            <td><?php echo number_format($apontamento['quantidade_horas'], 1); ?>h</td>
                                            <td><?php echo htmlspecialchars($apontamento['descricao'] ?? ''); ?></td>
                                            <td>
                                                <?php if (hasPermission('admin') || $_SESSION[SESSION_PREFIX . 'liderado_id'] == $apontamento['liderado_id']): ?>
                                                    <button class="btn btn-sm btn-danger" data-delete="apontamentos" data-id="<?php echo $apontamento['id']; ?>" title="Excluir">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2">Total:</th>
                                        <th>
                                            <?php 
                                                $totalHoras = array_reduce($apontamentos, function($total, $apontamento) {
                                                    return $total + $apontamento['quantidade_horas'];
                                                }, 0);
                                                echo number_format($totalHoras, 1); 
                                            ?>h
                                        </th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        <?php endif; ?>
                    </div>
                    
                    <div id="tab-apontamentos-novo" class="tab-content">
                        <form id="form-apontamento" action="<?php echo BASE_URL; ?>/api/apontamentos.php?action=store" method="POST" data-ajax="true" data-reload-page="true">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_PREFIX . 'csrf_token']; ?>">
                            <input type="hidden" name="atividade_id" value="<?php echo $atividade['id']; ?>">
                            <?php if ($projeto): ?>
                                <input type="hidden" name="projeto_id" value="<?php echo $projeto['id']; ?>">
                            <?php endif; ?>
                            
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Verificar horas disponíveis para o dia selecionado
document.addEventListener('DOMContentLoaded', function() {
    const dataInput = document.getElementById('data');
    const horasInput = document.getElementById('quantidade_horas');
    
    dataInput.addEventListener('change', function() {
        verificarHorasDisponiveis();
    });
    
    function verificarHorasDisponiveis() {
        const data = dataInput.value;
        if (!data) return;
        
        fetch(`${BASE_URL}/api/apontamentos.php?action=horas_disponiveis&data=${data}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const horasDisponiveis = data.data.horas_disponiveis;
                    horasInput.max = horasDisponiveis;
                    
                    if (horasDisponiveis < 0.1) {
                        horasInput.disabled = true;
                        horasInput.value = '';
                        alert('Você já atingiu o limite de horas para este dia.');
                    } else {
                        horasInput.disabled = false;
                        horasInput.value = Math.min(horasInput.value || 1, horasDisponiveis);
                    }
                }
            })
            .catch(error => console.error('Erro:', error));
    }
    
    // Verificar inicialmente
    verificarHorasDisponiveis();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>