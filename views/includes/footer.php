<script>
        // Constantes para JS
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const CSRF_TOKEN = '<?php echo $_SESSION[SESSION_PREFIX . 'csrf_token']; ?>';
        const SESSION_ERROR = '<?php echo isset($_SESSION[SESSION_PREFIX . 'error']) ? $_SESSION[SESSION_PREFIX . 'error'] : ''; ?>';
        const SESSION_SUCCESS = '<?php echo isset($_SESSION[SESSION_PREFIX . 'success']) ? $_SESSION[SESSION_PREFIX . 'success'] : ''; ?>';
    </script>
    <script src="<?php echo BASE_URL; ?>/js/main.js"></script>
</body>
</html>