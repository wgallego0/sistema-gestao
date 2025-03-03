<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <div class="main-content">
        <!-- Sidebar com opções -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- Conteúdo principal -->
        <div class="content">
            <div class="card">
                <h2>Editar Atividade</h2>
                <p>Atualize os dados da atividade.</p>
                
                <form id="form-atividade" action="<?php echo BASE_URL; ?>/api/atividades.php?action=update" method="POST" data-ajax="true" data-reset="false" data-reload-page="true">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_PREFIX . 'csrf_token']; ?>">
                    <input type="hidden" name="id" value="<?php echo $atividade['id']; ?>">
                    
                    <div class="form-group">
                        <label for="titulo">Título</label>
                        <input type="text" id="titulo" name="titulo" class="form-control" required value="<?php echo htmlspecialchars($atividade['titulo']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <textarea id="descricao" name="descricao" class="form-control" rows="3"><?php echo htmlspecialchars($atividade['descricao'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="projeto_id">Projeto</label>
                            <select id="projeto_id" name="projeto_id" class="form-control">
                                <option value="">Sem projeto específico</option>
                                <?php foreach ($projetos as $projeto): ?>
                                    <option value="<?php echo $projeto['id']; ?>" <?php echo ($atividade['projeto_id'] == $projeto['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($projeto['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="prioridade">Prioridade</label>
                            <select id="prioridade" name="prioridade" class="form-control">
                                <option value="Alta" <?php echo $atividade['prioridade'] === 'Alta' ? 'selected' : ''; ?>>Alta</option>
                                <option value="Média" <?php echo $atividade['prioridade'] === 'Média' ? 'selected' : ''; ?>>Média</option>
                                <option value="Baixa" <?php echo $atividade['prioridade'] === 'Baixa' ? 'selected' : ''; ?>>Baixa</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="Não iniciada" <?php echo $atividade['status'] === 'Não iniciada' ? 'selected' : ''; ?>>Não iniciada</option>
                                <option value="Em andamento" <?php echo $atividade['status'] === 'Em andamento' ? 'selected' : ''; ?>>Em andamento</option>
                                <option value="Concluída" <?php echo $atividade['status'] === 'Concluída' ? 'selected' : ''; ?>>Concluída</option>
                                <option value="Bloqueada" <?php echo $atividade['status'] === 'Bloqueada' ? 'selected' : ''; ?>>Bloqueada</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="data_inicio">Data de Início</label>
                            <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="<?php echo $atividade['data_inicio'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="data_fim">Data de Fim</label>
                            <input type="date" id="data_fim" name="data_fim" class="form-control" value="<?php echo $atividade['data_fim'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="horas_estimadas">Horas Estimadas</label>
                            <input type="number" id="horas_estimadas" name="horas_estimadas" class="form-control" min="0" step="0.1" value="<?php echo $atividade['horas_estimadas']; ?>">
                        </div>
                    </div>
                    
                    <h3>Responsáveis</h3>
                    <div class="card">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Selecionar</th>
                                    <th>Nome</th>
                                    <th>Cargo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($liderados as $liderado): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="responsaveis[]" value="<?php echo $liderado['id']; ?>" id="liderado-<?php echo $liderado['id']; ?>"
                                                <?php echo in_array($liderado['id'], $idsResponsaveis) ? 'checked' : ''; ?>>
                                        </td>
                                        <td>
                                            <label for="liderado-<?php echo $liderado['id']; ?>"><?php echo htmlspecialchars($liderado['nome']); ?></label>
                                        </td>
                                        <td><?php echo htmlspecialchars($liderado['cargo']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">Atualizar Atividade</button>
                        <a href="<?php echo BASE_URL; ?>/atividades.php?action=view&id=<?php echo $atividade['id']; ?>" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Vinculação entre projeto e responsáveis
    const projetoSelect = document.getElementById('projeto_id');
    const checkboxes = document.querySelectorAll('input[name="responsaveis[]"]');
    
    projetoSelect.addEventListener('change', function() {
        const projetoId = this.value;
        if (!projetoId) return;
        
        // Buscar membros do projeto
        fetch(`${BASE_URL}/api/projetos.php?action=membros&id=${projetoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Destacar membros do projeto selecionado
                    const membroIds = data.data.map(membro => membro.liderado_id);
                    
                    checkboxes.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        if (membroIds.includes(parseInt(checkbox.value))) {
                            row.style.backgroundColor = '#f0f8ff'; // Highlight
                        } else {
                            row.style.backgroundColor = '';
                        }
                    });
                }
            })
            .catch(error => console.error('Erro:', error));
    });
    
    // Trigger change event on page load to highlight members of the currently selected project
    projetoSelect.dispatchEvent(new Event('change'));
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>