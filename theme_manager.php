<?php
// theme_manager.php - Controller for theme management

require_once __DIR__ . '/config.php';

class ThemeManager {
    private $conn;
    
    // Default theme settings
    private $defaultSettings = [
        'logo_url' => '/images/default-logo.png',
        'primary_color' => '#2c3e50',
        'secondary_color' => '#3498db',
        'accent_color' => '#2ecc71',
        'background_color' => '#f5f5f5',
        'text_color' => '#333333',
        'header_color' => '#2c3e50',
        'sidebar_color' => '#ffffff',
        'card_color' => '#ffffff',
        'button_primary_color' => '#3498db',
        'button_success_color' => '#2ecc71',
        'button_danger_color' => '#e74c3c',
        'button_warning_color' => '#f39c12',
        'custom_css' => '',
        'show_logo' => true,
        'sidebar_position' => 'left',
        'site_name' => 'Sistema de Gestão de Equipes'
    ];
    
    public function __construct() {
        $this->conn = getConnection();
        $this->initThemeSettings();
    }
    
    /**
     * Initialize theme settings in database if they don't exist
     */
    private function initThemeSettings() {
        try {
            // Check if theme settings exist
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM configuracoes WHERE chave = 'theme_settings'");
            $stmt->execute();
            $result = $stmt->fetch();
            
            // If no theme settings, create with defaults
            if ($result['count'] == 0) {
                $stmt = $this->conn->prepare("INSERT INTO configuracoes (chave, valor, descricao) VALUES (?, ?, ?)");
                $stmt->execute([
                    'theme_settings', 
                    json_encode($this->defaultSettings), 
                    'Configurações de tema do sistema'
                ]);
            }
        } catch (PDOException $e) {
            logError('Erro ao inicializar configurações de tema: ' . $e->getMessage());
        }
    }
    
    /**
     * Get current theme settings
     */
    public function getThemeSettings() {
        try {
            $stmt = $this->conn->prepare("SELECT valor FROM configuracoes WHERE chave = 'theme_settings'");
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result) {
                $settings = json_decode($result['valor'], true);
                // Merge with defaults in case any new settings were added
                return array_merge($this->defaultSettings, $settings);
            }
            
            return $this->defaultSettings;
        } catch (PDOException $e) {
            logError('Erro ao obter configurações de tema: ' . $e->getMessage());
            return $this->defaultSettings;
        }
    }
    
    /**
     * Update theme settings
     */
    public function updateThemeSettings($settings) {
        try {
            // Get current settings
            $currentSettings = $this->getThemeSettings();
            
            // Merge new settings with current
            $updatedSettings = array_merge($currentSettings, $settings);
            
            // Update in database
            $stmt = $this->conn->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'theme_settings'");
            $stmt->execute([json_encode($updatedSettings)]);
            
            return true;
        } catch (PDOException $e) {
            logError('Erro ao atualizar configurações de tema: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reset theme to defaults
     */
    public function resetTheme() {
        try {
            $stmt = $this->conn->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'theme_settings'");
            $stmt->execute([json_encode($this->defaultSettings)]);
            
            return true;
        } catch (PDOException $e) {
            logError('Erro ao redefinir configurações de tema: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Upload a logo file
     */
    public function uploadLogo($file) {
        try {
            // Check if upload directory exists, create if not
            $uploadDir = __DIR__ . '/uploads/logos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate a unique filename
            $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'logo_' . time() . '.' . $fileExt;
            $uploadPath = $uploadDir . $fileName;
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
            if (!in_array($file['type'], $allowedTypes)) {
                return [
                    'success' => false,
                    'message' => 'Tipo de arquivo não permitido. Use JPEG, PNG, GIF ou SVG.'
                ];
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Update logo path in settings
                $webPath = '/uploads/logos/' . $fileName;
                $this->updateThemeSettings(['logo_url' => $webPath]);
                
                return [
                    'success' => true,
                    'message' => 'Logo enviado com sucesso.',
                    'logo_url' => $webPath
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Erro ao enviar arquivo.'
                ];
            }
        } catch (Exception $e) {
            logError('Erro ao enviar logo: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao processar arquivo: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate CSS from theme settings
     */
    public function generateThemeCSS() {
        $settings = $this->getThemeSettings();
        
        $css = ":root {\n";
        $css .= "  --primary-color: {$settings['primary_color']};\n";
        $css .= "  --secondary-color: {$settings['secondary_color']};\n";
        $css .= "  --accent-color: {$settings['accent_color']};\n";
        $css .= "  --background-color: {$settings['background_color']};\n";
        $css .= "  --text-color: {$settings['text_color']};\n";
        $css .= "  --header-color: {$settings['header_color']};\n";
        $css .= "  --sidebar-color: {$settings['sidebar_color']};\n";
        $css .= "  --card-color: {$settings['card_color']};\n";
        $css .= "  --button-primary-color: {$settings['button_primary_color']};\n";
        $css .= "  --button-success-color: {$settings['button_success_color']};\n";
        $css .= "  --button-danger-color: {$settings['button_danger_color']};\n";
        $css .= "  --button-warning-color: {$settings['button_warning_color']};\n";
        $css .= "}\n\n";
        
        $css .= "body { background-color: var(--background-color); color: var(--text-color); }\n";
        $css .= "header { background-color: var(--header-color); }\n";
        $css .= ".sidebar { background-color: var(--sidebar-color); }\n";
        $css .= ".card { background-color: var(--card-color); }\n";
        $css .= ".btn { background-color: var(--button-primary-color); }\n";
        $css .= ".btn-success { background-color: var(--button-success-color); }\n";
        $css .= ".btn-danger { background-color: var(--button-danger-color); }\n";
        $css .= ".btn-warning { background-color: var(--button-warning-color); }\n";
        
        // Add custom CSS
        if (!empty($settings['custom_css'])) {
            $css .= "\n/* Custom CSS */\n{$settings['custom_css']}\n";
        }
        
        return $css;
    }
    
    /**
     * Save generated CSS to file
     */
    public function saveThemeCSS() {
        try {
            $css = $this->generateThemeCSS();
            $cssFile = __DIR__ . '/css/theme.css';
            
            file_put_contents($cssFile, $css);
            return true;
        } catch (Exception $e) {
            logError('Erro ao salvar CSS do tema: ' . $e->getMessage());
            return false;
        }
    }
}