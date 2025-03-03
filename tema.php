<?php
// tema.php - Theme configuration page

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/theme_manager.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Check if user has admin permission
if (!hasPermission('admin')) {
    $_SESSION[SESSION_PREFIX . 'error'] = 'Você não tem permissão para gerenciar o tema do sistema';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Create theme manager instance
$themeManager = new ThemeManager();

// Get current theme settings
$themeSettings = $themeManager->getThemeSettings();

// Include header
require_once __DIR__ . '/views/includes/header.php';
?>

<div class="container">
    <div class="main-content">
        <!-- Sidebar with options -->
        <?php require_once __DIR__ . '/views/includes/sidebar.php'; ?>
        
        <!-- Main content -->
        <div class="content">
            <div class="card">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Configurações de Tema</h2>
                    <div>
                        <button id="btn-preview" class="btn btn-primary">
                            <i class="fa fa-eye"></i> Pré-visualizar
                        </button>
                        <button id="btn-reset" class="btn btn-warning">
                            <i class="fa fa-undo"></i> Restaurar Padrão
                        </button>
                    </div>
                </div>
                
                <p>Personalize a aparência do sistema ajustando as configurações abaixo.</p>
                
                <div id="theme-preview" style="display: none; margin-bottom: 20px;">
                    <h3>Pré-visualização</h3>
                    <div class="preview-container" style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
                        <div id="preview-header" style="padding: 10px 20px; display: flex; justify-content: space-between; align-items: center;">
                            <div class="preview-logo-container" style="display: flex; align-items: center;">
                                <img id="preview-logo" src="<?php echo BASE_URL . $themeSettings['logo_url']; ?>" alt="Logo" style="height: 40px; margin-right: 10px;">
                                <h3 id="preview-title" style="margin: 0;"><?php echo htmlspecialchars($themeSettings['site_name']); ?></h3>
                            </div>
                            <div class="preview-nav" style="display: flex; gap: 10px;">
                                <a class="preview-nav-item active" style="padding: 5px 10px; border-radius: 4px; text-decoration: none;">Dashboard</a>
                                <a class="preview-nav-item" style="padding: 5px 10px; border-radius: 4px; text-decoration: none;">Equipe</a>
                                <a class="preview-nav-item" style="padding: 5px 10px; border-radius: 4px; text-decoration: none;">Projetos</a>
                            </div>
                        </div>
                        <div style="display: flex; padding: 20px;">
                            <div id="preview-sidebar" style="width: 200px; padding: 15px; border-radius: 8px; margin-right: 20px;">
                                <h4>Menu Rápido</h4>
                                <ul style="list-style: none; padding-left: 10px;">
                                    <li style="margin-bottom: 8px;">Novo Liderado</li>
                                    <li style="margin-bottom: 8px;">Novo Projeto</li>
                                    <li style="margin-bottom: 8px;">Nova Atividade</li>
                                </ul>
                            </div>
                            <div style="flex: 1;">
                                <div id="preview-card" style="padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                                    <h3>Visão Geral</h3>
                                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                                        <div class="preview-metric" style="flex: 1; text-align: center; padding: 15px; border-radius: 8px;">
                                            <div style="font-size: 24px; font-weight: bold;">5</div>
                                            <div style="font-size: 12px;">Liderados</div>
                                        </div>
                                        <div class="preview-metric" style="flex: 1; text-align: center; padding: 15px; border-radius: 8px;">
                                            <div style="font-size: 24px; font-weight: bold;">3</div>
                                            <div style="font-size: 12px;">Projetos</div>
                                        </div>
                                        <div class="preview-metric" style="flex: 1; text-align: center; padding: 15px; border-radius: 8px;">
                                            <div style="font-size: 24px; font-weight: bold;">12</div>
                                            <div style="font-size: 12px;">Atividades</div>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <button id="preview-button-primary" class="preview-button" style="padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">Botão Primário</button>
                                    <button id="preview-button-success" class="preview-button" style="padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">Sucesso</button>
                                    <button id="preview-button-danger" class="preview-button" style="padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">Perigo</button>
                                    <button id="preview-button-warning" class="preview-button" style="padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">Alerta</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form id="theme-form" action="<?php echo BASE_URL; ?>/api/tema.php?action=update_settings" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_PREFIX . 'csrf_token']; ?>">
                    
                    <div class="tab-container">
                        <div class="tab-buttons">
                            <button type="button" class="tab-btn active" data-tab="tab-geral">Geral</button>
                            <button type="button" class="tab-btn" data-tab="tab-cores">Cores</button>
                            <button type="button" class="tab-btn" data-tab="tab-botoes">Botões</button>
                            <button type="button" class="tab-btn" data-tab="tab-avancado">Avançado</button>
                        </div>
                        
                        <!-- Guia Geral -->
                        <div id="tab-geral" class="tab-content active">
                            <h3>Configurações Gerais</h3>
                            
                            <div class="form-group">
                                <label for="site_name">Nome do Sistema</label>
                                <input type="text" id="site_name" name="site_name" class="form-control" value="<?php echo htmlspecialchars($themeSettings['site_name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="logo">Logo</label>
                                <div>
                                    <img id="current-logo" src="<?php echo BASE_URL . $themeSettings['logo_url']; ?>" alt="Logo atual" style="max-height: 100px; margin-bottom: 10px; background-color: #f0f0f0; padding: 5px; border-radius: 4px;">
                                </div>
                                <input type="file" id="logo" name="logo" class="form-control" accept="image/*">
                                <small class="form-help">Recomendado: 200x50 pixels, formatos PNG ou SVG com fundo transparente.</small>
                                <div class="form-check" style="margin-top: 10px;">
                                    <input type="checkbox" id="show_logo" name="show_logo" class="form-check-input" <?php echo $themeSettings['show_logo'] ? 'checked' : ''; ?>>
                                    <label for="show_logo" class="form-check-label">Exibir logo no cabeçalho</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="sidebar_position">Posição da Barra Lateral</label>
                                <select id="sidebar_position" name="sidebar_position" class="form-control">
                                    <option value="left" <?php echo $themeSettings['sidebar_position'] === 'left' ? 'selected' : ''; ?>>Esquerda</option>
                                    <option value="right" <?php echo $themeSettings['sidebar_position'] === 'right' ? 'selected' : ''; ?>>Direita</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Guia Cores -->
                        <div id="tab-cores" class="tab-content">
                            <h3>Esquema de Cores</h3>
                            
                            <div class="grid-3">
                                <div class="form-group">
                                    <label for="primary_color">Cor Primária</label>
                                    <input type="color" id="primary_color" name="primary_color" class="form-control" value="<?php echo $themeSettings['primary_color']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="secondary_color">Cor Secundária</label>
                                    <input type="color" id="secondary_color" name="secondary_color" class="form-control" value="<?php echo $themeSettings['secondary_color']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="accent_color">Cor de Destaque</label>
                                    <input type="color" id="accent_color" name="accent_color" class="form-control" value="<?php echo $themeSettings['accent_color']; ?>">
                                </div>
                            </div>
                            
                            <h4>Cores de Interface</h4>
                            
                            <div class="grid-3">
                                <div class="form-group">
                                    <label for="background_color">Cor de Fundo</label>
                                    <input type="color" id="background_color" name="background_color" class="form-control" value="<?php echo $themeSettings['background_color']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="text_color">Cor do Texto</label>
                                    <input type="color" id="text_color" name="text_color" class="form-control" value="<?php echo $themeSettings['text_color']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="header_color">Cor do Cabeçalho</label>
                                    <input type="color" id="header_color" name="header_color" class="form-control" value="<?php echo $themeSettings['header_color']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="sidebar_color">Cor da Barra Lateral</label>
                                    <input type="color" id="sidebar_color" name="sidebar_color" class="form-control" value="<?php echo $themeSettings['sidebar_color']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="card_color">Cor dos Cards</label>
                                    <input type="color" id="card_color" name="card_color" class="form-control" value="<?php echo $themeSettings['card_color']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Guia Botões -->
                        <div id="tab-botoes" class="tab-content">
                            <h3>Cores dos Botões</h3>
                            
                            <div class="grid-2">
                                <div class="form-group">
                                    <label for="button_primary_color">Botão Primário</label>
                                    <input type="color" id="button_primary_color" name="button_primary_color" class="form-control" value="<?php echo $themeSettings['button_primary_color']; ?>">
                                    <div class="preview-item" style="margin-top: 10px; padding: 10px; border-radius: 4px; text-align: center;">
                                        <button type="button" style="background-color: <?php echo $themeSettings['button_primary_color']; ?>; color: white; border: none; padding: 8px 15px; border-radius: 4px;">Botão Primário</button>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="button_success_color">Botão de Sucesso</label>
                                    <input type="color" id="button_success_color" name="button_success_color" class="form-control" value="<?php echo $themeSettings['button_success_color']; ?>">
                                    <div class="preview-item" style="margin-top: 10px; padding: 10px; border-radius: 4px; text-align: center;">
                                        <button type="button" style="background-color: <?php echo $themeSettings['button_success_color']; ?>; color: white; border: none; padding: 8px 15px; border-radius: 4px;">Botão de Sucesso</button>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="button_danger_color">Botão de Perigo</label>
                                    <input type="color" id="button_danger_color" name="button_danger_color" class="form-control" value="<?php echo $themeSettings['button_danger_color']; ?>">
                                    <div class="preview-item" style="margin-top: 10px; padding: 10px; border-radius: 4px; text-align: center;">
                                        <button type="button" style="background-color: <?php echo $themeSettings['button_danger_color']; ?>; color: white; border: none; padding: 8px 15px; border-radius: 4px;">Botão de Perigo</button>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="button_warning_color">Botão de Alerta</label>
                                    <input type="color" id="button_warning_color" name="button_warning_color" class="form-control" value="<?php echo $themeSettings['button_warning_color']; ?>">
                                    <div class="preview-item" style="margin-top: 10px; padding: 10px; border-radius: 4px; text-align: center;">
                                        <button type="button" style="background-color: <?php echo $themeSettings['button_warning_color']; ?>; color: white; border: none; padding: 8px 15px; border-radius: 4px;">Botão de Alerta</button>
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