<?php
/*
Plugin Name: Multiple Link Redirector
Plugin URI: https://github.com/KuyaMecky/multiple-link-redirector
Description: Manage multiple link redirections with a modern, user-friendly admin interface
Version: 1.2
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
        add_action('wp_ajax_mlr_save_redirects', [$this, 'ajax_save_redirects']);
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_link-redirector') return;

        wp_enqueue_style('mlr-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
        wp_enqueue_script('mlr-admin-script', plugin_dir_url(__FILE__) . 'admin-script.js', ['jquery'], '1.2', true);
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
        register_setting(
            $this->option_name, 
            $this->option_name, 
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_redirects']
            ]
        );
    }

    public function sanitize_redirects($input) {
        $new_input = [];
        
        if (!is_array($input)) {
            return $new_input;
        }

        foreach ($input as $key => $redirect) {
            // Ensure both original and target URLs are not empty
            if (!empty($redirect['original']) && !empty($redirect['target'])) {
                $new_input[] = [
                    'original' => esc_url_raw($redirect['original']),
                    'target' => esc_url_raw($redirect['target'])
                ];
            }
        }

        return $new_input;
    }

    public function ajax_save_redirects() {
        // Security checks
        check_ajax_referer($this->nonce_action, 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        // Retrieve and sanitize input
        $redirects = isset($_POST['redirects']) ? stripslashes_deep($_POST['redirects']) : [];
        
        // Sanitize and validate redirects
        $sanitized_redirects = $this->sanitize_redirects($redirects);

        // Update option
        $result = update_option($this->option_name, $sanitized_redirects);

        if ($result) {
            wp_send_json_success($sanitized_redirects);
        } else {
            wp_send_json_error('Failed to save redirects');
        }
    }

    public function admin_page_html() {
        $redirects = get_option($this->option_name, []);
        ?>
        <div class="wrap">
            <h1>Multiple Link Redirector</h1>
            <form id="redirects-form">
                <?php wp_nonce_field($this->nonce_action, 'mlr_nonce'); ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Original URL</th>
                            <th>Target URL</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="redirects-list">
                        <?php foreach ($redirects as $index => $redirect): ?>
                        <tr>
                            <td>
                                <input type="text" 
                                    name="redirects[<?php echo $index; ?>][original]" 
                                    value="<?php echo esc_attr($redirect['original']); ?>" 
                                    class="widefat"
                                >
                            </td>
                            <td>
                                <input type="text" 
                                    name="redirects[<?php echo $index; ?>][target]" 
                                    value="<?php echo esc_attr($redirect['target']); ?>" 
                                    class="widefat"
                                >
                            </td>
                            <td>
                                <button type="button" class="button remove-redirect">Remove</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="actions">
                    <button type="button" id="add-redirect" class="button button-primary">Add Redirect</button>
                    <button type="submit" id="save-redirects" class="button button-secondary">Save Redirects</button>
                </div>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            let redirectIndex = <?php echo count($redirects); ?>;

            // Add new redirect row
            $('#add-redirect').on('click', function() {
                const newRow = `
                    <tr>
                        <td>
                            <input type="text" 
                                name="redirects[${redirectIndex}][original]" 
                                class="widefat"
                            >
                        </td>
                        <td>
                            <input type="text" 
                                name="redirects[${redirectIndex}][target]" 
                                class="widefat"
                            >
                        </td>
                        <td>
                            <button type="button" class="button remove-redirect">Remove</button>
                        </td>
                    </tr>
                `;
                $('#redirects-list').append(newRow);
                redirectIndex++;
            });

            // Remove redirect row
            $(document).on('click', '.remove-redirect', function() {
                $(this).closest('tr').remove();
            });

            // Save redirects via AJAX
            $('#redirects-form').on('submit', function(e) {
                e.preventDefault();
                
                const formData = $(this).serializeArray();
                const redirects = [];

                // Organize form data into redirects array
                for (let i = 0; i < formData.length; i += 3) {
                    if (formData[i].name.includes('[original]') && 
                        formData[i+1].name.includes('[target]')) {
                        redirects.push({
                            original: formData[i].value,
                            target: formData[i+1].value
                        });
                    }
                }

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'mlr_save_redirects',
                        nonce: $('#mlr_nonce').val(),
                        redirects: redirects
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Redirects saved successfully!');
                            location.reload();
                        } else {
                            alert('Failed to save redirects: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('An error occurred while saving redirects.');
                    }
                });
            });
        });
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

// Initialize the plugin
add_action('plugins_loaded', function() {
    new MultiLinkRedirector();
});

register_activation_hook(__FILE__, ['MultiLinkRedirector', 'activate']);
register_deactivation_hook(__FILE__, ['MultiLinkRedirector', 'deactivate']);