<?php
// api/tema.php - API endpoint for theme management

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../theme_manager.php';

// Verify that the user is logged in
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Você precisa estar logado para acessar esta API'], 401);
}

// Check if user has admin permission
if (!hasPermission('admin')) {
    jsonResponse(['error' => 'Você não tem permissão para gerenciar o tema do sistema'], 403);
}

// Create theme manager instance
$themeManager = new ThemeManager();

// Determine action based on HTTP method and parameters
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : null;
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get current theme settings
        if ($action === 'get_settings') {
            $settings = $themeManager->getThemeSettings();
            jsonResponse(['success' => true, 'data' => $settings]);
        }
        // Generate preview CSS
        else if ($action === 'preview_css') {
            $css = $themeManager->generateThemeCSS();
            header('Content-Type: text/css');
            echo $css;
            exit;
        }
        else {
            jsonResponse(['error' => 'Ação não especificada'], 400);
        }
        break;
    
    case 'POST':
        // Verify CSRF token
        verifyCsrfToken();
        
        if ($action === 'update_settings') {
            // Update theme settings
            $settings = [];
            
            // Process color settings
            $colorFields = [
                'primary_color', 'secondary_color', 'accent_color', 
                'background_color', 'text_color', 'header_color', 
                'sidebar_color', 'card_color', 'button_primary_color',
                'button_success_color', 'button_danger_color', 'button_warning_color'
            ];
            
            foreach ($colorFields as $field) {
                if (isset($_POST[$field])) {
                    $settings[$field] = sanitizeInput($_POST[$field]);
                }
            }
            
            // Process boolean settings
            $boolFields = ['show_logo'];
            foreach ($boolFields as $field) {
                if (isset($_POST[$field])) {
                    $settings[$field] = (bool) $_POST[$field];
                }
            }
            
            // Process text settings
            $textFields = ['custom_css', 'site_name', 'sidebar_position'];
            foreach ($textFields as $field) {
                if (isset($_POST[$field])) {
                    $settings[$field] = sanitizeInput($_POST[$field]);
                }
            }
            
            // Update settings
            $result = $themeManager->updateThemeSettings($settings);
            
            if ($result) {
                // Save CSS to file
                $themeManager->saveThemeCSS();
                jsonResponse(['success' => true, 'message' => 'Configurações de tema atualizadas com sucesso']);
            } else {
                jsonResponse(['error' => 'Erro ao atualizar configurações de tema'], 500);
            }
        }
        else if ($action === 'upload_logo') {
            // Upload logo
            if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                jsonResponse(['error' => 'Nenhum arquivo enviado ou erro no upload'], 400);
            }
            
            $result = $themeManager->uploadLogo($_FILES['logo']);
            
            if ($result['success']) {
                // Save CSS to file
                $themeManager->saveThemeCSS();
                jsonResponse(['success' => true, 'message' => $result['message'], 'logo_url' => $result['logo_url']]);
            } else {
                jsonResponse(['error' => $result['message']], 500);
            }
        }
        else if ($action === 'reset') {
            // Reset theme to defaults
            $result = $themeManager->resetTheme();
            
            if ($result) {
                // Save CSS to file
                $themeManager->saveThemeCSS();
                jsonResponse(['success' => true, 'message' => 'Tema redefinido para os valores padrão']);
            } else {
                jsonResponse(['error' => 'Erro ao redefinir tema'], 500);
            }
        }
        else {
            jsonResponse(['error' => 'Ação não especificada ou parâmetros inválidos'], 400);
        }
        break;
    
    default:
        jsonResponse(['error' => 'Método não suportado'], 405);
}