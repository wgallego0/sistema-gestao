<?php
// Include the necessary files directly 
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../models/Projeto.php';
require_once __DIR__ . '/../../models/Liderado.php';
require_once __DIR__ . '/../../models/Atividade.php';

// Verify if the user is logged in
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Get the necessary data for the form
$projetoModel = new Projeto();
$lideradoModel = new Liderado();

// Preselected project, if provided in the URL
$projetoId = isset($_GET['projeto_id']) ? (int) $_GET['projeto_id'] : null;
$projeto = null;

if ($projetoId) {
    $projeto = $projetoModel->getById($projetoId);
}

// Load projects for the view
$projetos = [];

if (hasPermission('gestor') || hasPermission('admin')) {
    $projetos = $projetoModel->getAll();
} else if (isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
    $liderado = $lideradoModel->getById($_SESSION[SESSION_PREFIX . 'liderado_id']);
    
    if ($liderado && isset($liderado['projetos'])) {
        foreach ($liderado['projetos'] as $projetoBasico) {
            $projeto = $projetoModel->getById($projetoBasico['projeto_id']);
            if ($projeto) {
                $projetos[] = $projeto;
            }
        }
    }
}

// Load liderados for the view (to select responsibles)
$liderados = [];

if (hasPermission('gestor') || hasPermission('admin')) {
    $liderados = $lideradoModel->getAll();
} else {
    // For common liderado, include only himself as responsible
    if (isset($_SESSION[SESSION_PREFIX . 'liderado_id'])) {
        $liderado = $lideradoModel->getById($_SESSION[SESSION_PREFIX . 'liderado_id']);
        if ($liderado) {
            $liderados[] = $liderado;
        }
    }
}

// Include header
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="main-content">
        <!-- Sidebar with options -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- Main content -->
        <div class="content">
            <div class="card">
                <h2>Nova Atividade</h2>
                <p>Preencha os dados para adicionar uma nova atividade.</p>
                
                <form id="form-atividade" action="<?php echo BASE_URL; ?>/api/atividades.php?action=store" method="POST" data-ajax="true" data-reset="true" data-reload-page="true">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_PREFIX . 'csrf_token']; ?>">
                    
                    <div class="form-group">
                        <label for="titulo">Título</label>
                        <input type="text" id="titulo" name="titulo" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <textarea id="descricao" name="descricao" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="projeto_id">Projeto</label>
                            <select id="projeto_id" name="projeto_id" class="form-control">
                                <option value="">Sem projeto específico</option>
                                <?php foreach ($projetos as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo ($projetoId && $p['id'] == $projetoId) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="prioridade">Prioridade</label>
                            <select id="prioridade" name="prioridade" class="form-control">
                                <option value="Alta">Alta</option>
                                <option value="Média" selected>Média</option>
                                <option value="Baixa">Baixa</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="Não iniciada" selected>Não iniciada</option>
                                <option value="Em andamento">Em andamento</option>
                                <option value="Concluída">Concluída</option>
                                <option value="Bloqueada">Bloqueada</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="data_inicio">Data de Início</label>
                            <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="data_fim">Data de Fim</label>
                            <input type="date" id="data_fim" name="data_fim" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="horas_estimadas">Horas Estimadas</label>
                            <input type="number" id="horas_estimadas" name="horas_estimadas" class="form-control" min="0" step="0.1" value="0">
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
                                            <input type="checkbox" name="responsaveis[]" value="<?php echo $liderado['id']; ?>" id="liderado-<?php echo $liderado['id']; ?>" <?php echo (!hasPermission('gestor') && !hasPermission('admin')) ? 'checked' : ''; ?>>
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
                        <button type="submit" class="btn btn-primary">Salvar Atividade</button>
                        <a href="<?php echo BASE_URL; ?>/atividades.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Link between project and responsible people
    const projetoSelect = document.getElementById('projeto_id');
    const checkboxes = document.querySelectorAll('input[name="responsaveis[]"]');
    
    projetoSelect.addEventListener('change', function() {
        const projetoId = this.value;
        if (!projetoId) return;
        
        // Get project members
        fetch(`${BASE_URL}/api/projetos.php?action=membros&id=${projetoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Highlight members of the selected project
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
    
    // Trigger initial load
    if (projetoSelect.value) {
        projetoSelect.dispatchEvent(new Event('change'));
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>