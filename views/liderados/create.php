<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <div class="main-content">
        <!-- Sidebar com opções -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- Conteúdo principal -->
        <div class="content">
            <div class="card">
                <h2>Novo Liderado</h2>
                <p>Preencha os dados para adicionar um novo liderado.</p>
                
                <form id="form-liderado" action="<?php echo BASE_URL; ?>/api/liderados.php?action=store" method="POST" data-ajax="true" data-reset="true" data-reload-page="false">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_PREFIX . 'csrf_token']; ?>">
                    
                    <div class="form-group">
                        <label for="nome">Nome</label>
                        <input type="text" id="nome" name="nome" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cargo">Cargo</label>
                        <input type="text" id="cargo" name="cargo" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="cross_funcional" name="cross_funcional" class="form-check-input">
                            <label for="cross_funcional" class="form-check-label">Cross-Funcional</label>
                        </div>
                        <small class="form-help">Marque esta opção se o liderado trabalha em múltiplos projetos ao mesmo tempo.</small>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Salvar</button>
                        <a href="<?php echo BASE_URL; ?>/liderados.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-liderado');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validação básica
        const nome = document.getElementById('nome').value.trim();
        const email = document.getElementById('email').value.trim();
        const cargo = document.getElementById('cargo').value.trim();
        
        if (!nome || !email || !cargo) {
            showNotification('Preencha todos os campos obrigatórios', 'error');
            return;
        }
        
        // Validar email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showNotification('Email inválido', 'error');
            return;
        }
        
        // Submeter formulário via AJAX
        const formData = new FormData(form);
        
        fetch(form.getAttribute('action'), {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Liderado adicionado com sucesso', 'success');
                
                // Resetar formulário se especificado
                if (form.getAttribute('data-reset') !== 'false') {
                    form.reset();
                }
                
                // Redirecionar após um breve delay
                setTimeout(() => {
                    window.location.href = `${BASE_URL}/liderados.php?action=view&id=${data.id}`;
                }, 1500);
            } else {
                showNotification(data.error || 'Erro ao adicionar liderado', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotification('Erro de comunicação com o servidor', 'error');
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>