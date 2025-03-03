: 8px 15px; border-radius: 4px;">Botão de Alerta</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Guia Avançado -->
                        <div id="tab-avancado" class="tab-content">
                            <h3>Configurações Avançadas</h3>
                            
                            <div class="form-group">
                                <label for="custom_css">CSS Personalizado</label>
                                <textarea id="custom_css" name="custom_css" class="form-control" rows="10"><?php echo htmlspecialchars($themeSettings['custom_css']); ?></textarea>
                                <small class="form-help">Adicione regras CSS personalizadas para ajustar o tema de acordo com suas necessidades.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                        <button type="button" id="btn-upload-logo" class="btn btn-success">Enviar Logo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para upload de logo -->
<div class="modal" id="modal-upload-logo">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Upload de Logo</h3>
            <span class="modal-close">&times;</span>
        </div>
        <form id="form-logo" action="<?php echo BASE_URL; ?>/api/tema.php?action=upload_logo" method="POST" enctype="multipart/form-data" data-ajax="true">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_PREFIX . 'csrf_token']; ?>">
            
            <div class="form-group">
                <label for="logo-file">Selecione a imagem</label>
                <input type="file" id="logo-file" name="logo" class="form-control" accept="image/*" required>
                <small class="form-help">Formatos aceitos: JPEG, PNG, GIF, SVG. Tamanho recomendado: 200x50 pixels.</small>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Enviar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Visualização em tempo real das alterações de cores
    const colorInputs = document.querySelectorAll('input[type="color"]');
    colorInputs.forEach(input => {
        input.addEventListener('input', updatePreview);
    });
    
    // Visualização em tempo real das alterações de texto
    document.getElementById('site_name').addEventListener('input', function() {
        document.getElementById('preview-title').textContent = this.value;
    });
    
    // Atualizar visualização
    function updatePreview() {
        const primaryColor = document.getElementById('primary_color').value;
        const secondaryColor = document.getElementById('secondary_color').value;
        const accentColor = document.getElementById('accent_color').value;
        const backgroundColor = document.getElementById('background_color').value;
        const textColor = document.getElementById('text_color').value;
        const headerColor = document.getElementById('header_color').value;
        const sidebarColor = document.getElementById('sidebar_color').value;
        const cardColor = document.getElementById('card_color').value;
        const buttonPrimaryColor = document.getElementById('button_primary_color').value;
        const buttonSuccessColor = document.getElementById('button_success_color').value;
        const buttonDangerColor = document.getElementById('button_danger_color').value;
        const buttonWarningColor = document.getElementById('button_warning_color').value;
        
        // Atualizar preview
        document.querySelector('.preview-container').style.backgroundColor = backgroundColor;
        document.querySelector('.preview-container').style.color = textColor;
        document.getElementById('preview-header').style.backgroundColor = headerColor;
        document.getElementById('preview-header').style.color = 'white';
        document.getElementById('preview-sidebar').style.backgroundColor = sidebarColor;
        document.getElementById('preview-card').style.backgroundColor = cardColor;
        
        // Atualizar botões
        document.getElementById('preview-button-primary').style.backgroundColor = buttonPrimaryColor;
        document.getElementById('preview-button-primary').style.color = 'white';
        document.getElementById('preview-button-success').style.backgroundColor = buttonSuccessColor;
        document.getElementById('preview-button-success').style.color = 'white';
        document.getElementById('preview-button-danger').style.backgroundColor = buttonDangerColor;
        document.getElementById('preview-button-danger').style.color = 'white';
        document.getElementById('preview-button-warning').style.backgroundColor = buttonWarningColor;
        document.getElementById('preview-button-warning').style.color = 'white';
        
        // Atualizar métricas
        const metricBoxes = document.querySelectorAll('.preview-metric');
        metricBoxes.forEach(box => {
            box.style.backgroundColor = cardColor;
            box.style.borderColor = accentColor;
        });
        
        // Atualizar item de navegação ativo
        const activeNavItem = document.querySelector('.preview-nav-item.active');
        activeNavItem.style.backgroundColor = secondaryColor;
    }
    
    // Botão de pré-visualização
    document.getElementById('btn-preview').addEventListener('click', function() {
        const previewSection = document.getElementById('theme-preview');
        if (previewSection.style.display === 'none') {
            previewSection.style.display = 'block';
            this.innerHTML = '<i class="fa fa-eye-slash"></i> Ocultar Pré-visualização';
            updatePreview();
        } else {
            previewSection.style.display = 'none';
            this.innerHTML = '<i class="fa fa-eye"></i> Pré-visualizar';
        }
    });
    
    // Botão de upload de logo
    document.getElementById('btn-upload-logo').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('modal-upload-logo').style.display = 'flex';
    });
    
    // Formulário de upload de logo
    document.getElementById('form-logo').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch(this.getAttribute('action'), {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                document.getElementById('modal-upload-logo').style.display = 'none';
                
                // Atualizar imagem da logo
                const logoUrl = BASE_URL + data.logo_url;
                document.getElementById('current-logo').src = logoUrl;
                document.getElementById('preview-logo').src = logoUrl;
                
                // Resetar formulário
                document.getElementById('form-logo').reset();
            } else {
                showNotification(data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotification('Erro ao enviar logo', 'error');
        });
    });
    
    // Formulário principal
    document.getElementById('theme-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch(this.getAttribute('action'), {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                
                // Recarregar a página após 1,5 segundos
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification(data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotification('Erro ao salvar configurações', 'error');
        });
    });
    
    // Botão de reset
    document.getElementById('btn-reset').addEventListener('click', function() {
        if (confirm('Tem certeza que deseja restaurar as configurações padrão do tema? Esta ação não pode ser desfeita.')) {
            // Enviar requisição para reset
            const formData = new FormData();
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            
            fetch(BASE_URL + '/api/tema.php?action=reset', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    
                    // Recarregar a página após 1,5 segundos
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification(data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showNotification('Erro ao restaurar configurações padrão', 'error');
            });
        }
    });
});
</script>

<?php require_once __DIR__ . '/views/includes/footer.php'; ?>