<?php
/*
Plugin Name: Multiple Link Redirector
Plugin URI: https://github.com/KuyaMecky/multiple-link-redirector
Description: Manage multiple link redirections with a modern, user-friendly admin interface
Version: 1.1
Author: MeckyMouse
Author URI: https://github.com/KuyaMecky
*/

class MultiLinkRedirector {
    private $option_name = 'multi_link_redirects';
    private $nonce_action = 'mlr_redirect_nonce';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('template_redirect', [$this, 'perform_redirects']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_mlr_edit_redirect', [$this, 'ajax_edit_redirect']);
        add_action('wp_ajax_mlr_delete_redirect', [$this, 'ajax_delete_redirect']);
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_link-redirector') return;

        wp_enqueue_style('mlr-admin-style', plugin_dir_url(__FILE__) . 'assets/admin-style.css');
        wp_enqueue_script('mlr-admin-script', plugin_dir_url(__FILE__) . 'assets/admin-script.js', ['jquery'], '1.1', true);
        wp_localize_script('mlr-admin-script', 'mlrAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce($this->nonce_action)
        ]);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Link Redirector', 
            'Link Redirector', 
            'manage_options', 
            'link-redirector', 
            [$this, 'admin_page_html'],
            'dashicons-randomize',
            60
        );
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name, [$this, 'sanitize_redirects']);
    }

    public function sanitize_redirects($input) {
        $new_input = [];
        if (is_array($input)) {
            foreach ($input as $key => $redirect) {
                if (!empty($redirect['original']) && !empty($redirect['target'])) {
                    $new_input[] = [
                        'original' => sanitize_text_field($redirect['original']),
                        'target' => sanitize_text_field($redirect['target'])
                    ];
                }
            }
        }
        return $new_input;
    }

    public function ajax_edit_redirect() {
        check_ajax_referer($this->nonce_action, 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $index = intval($_POST['index']);
        $original = sanitize_text_field($_POST['original']);
        $target = sanitize_text_field($_POST['target']);

        $redirects = get_option($this->option_name, []);

        if (isset($redirects[$index])) {
            $redirects[$index] = [
                'original' => $original,
                'target' => $target
            ];

            update_option($this->option_name, $redirects);
            wp_send_json_success($redirects);
        }

        wp_send_json_error('Redirect not found');
    }

    public function ajax_delete_redirect() {
        check_ajax_referer($this->nonce_action, 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $index = intval($_POST['index']);
        $redirects = get_option($this->option_name, []);

        if (isset($redirects[$index])) {
            unset($redirects[$index]);
            $redirects = array_values($redirects);
            update_option($this->option_name, $redirects);
            wp_send_json_success($redirects);
        }

        wp_send_json_error('Redirect not found');
    }

    public function admin_page_html() {
        ?>
        <div class="mlr-container">
            <div class="mlr-header">
                <h1>Link Redirector</h1>
                <button id="add-redirect-btn" class="mlr-button primary">
                    <span class="dashicons dashicons-plus"></span> Add New Redirect
                </button>
            </div>

            <div class="mlr-table-container">
                <table class="mlr-table" id="redirects-table">
                    <thead>
                        <tr>
                            <th>Original URL</th>
                            <th>Target URL</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="redirect-rows">
                        <?php 
                        $redirects = get_option($this->option_name, []);
                        foreach ($redirects as $index => $redirect): 
                        ?>
                        <tr data-index="<?php echo $index; ?>">
                            <td class="original-url"><?php echo esc_html($redirect['original']); ?></td>
                            <td class="target-url"><?php echo esc_html($redirect['target']); ?></td>
                            <td class="actions">
                                <button class="mlr-button edit-redirect" data-index="<?php echo $index; ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button class="mlr-button delete-redirect" data-index="<?php echo $index; ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Modal for Add/Edit Redirect -->
            <div id="redirect-modal" class="mlr-modal">
                <div class="mlr-modal-content">
                    <span class="mlr-close-modal">&times;</span>
                    <h2 id="modal-title">Add New Redirect</h2>
                    <form id="redirect-form">
                        <input type="hidden" id="redirect-index" name="index" value="-1">
                        <div class="mlr-form-group">
                            <label for="original-url">Original URL</label>
                            <input type="text" id="original-url" name="original" required>
                        </div>
                        <div class="mlr-form-group">
                            <label for="target-url">Target URL</label>
                            <input type="text" id="target-url" name="target" required>
                        </div>
                        <div class="mlr-form-actions">
                            <button type="submit" class="mlr-button primary">Save Redirect</button>
                            <button type="button" class="mlr-button secondary mlr-close-modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        // JavaScript will be in a separate file (assets/admin-script.js)
        </script>
        <?php
    }

    public function perform_redirects() {
        $redirects = get_option($this->option_name, []);
        $current_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        foreach ($redirects as $redirect) {
            if (!empty($redirect['original']) && !empty($redirect['target'])) {
                // Support for partial URL matching
                if (strpos($current_url, $redirect['original']) !== false) {
                    wp_redirect($redirect['target'], 301);
                    exit();
                }
            }
        }
    }

    public static function activate() {
        // Activation hook if needed
    }

    public static function deactivate() {
        delete_option('multi_link_redirects');
    }
}

// Plugin initialization
add_action('plugins_loaded', function() {
    new MultiLinkRedirector();
});

register_activation_hook(__FILE__, ['MultiLinkRedirector', 'activate']);
register_deactivation_hook(__FILE__, ['MultiLinkRedirector', 'deactivate']);