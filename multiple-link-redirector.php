<?php
/*
Plugin Name: Multiple Link Redirector
Plugin URI: https://github.com/KuyaMecky/multiple-link-redirector
Description: Manage multiple link redirections
Version: 1.4
Author: MeckyMouse
*/

class MultiLinkRedirector {
    private $option_name = 'multi_link_redirects';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('template_redirect', [$this, 'perform_redirects']);
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
        register_setting($this->option_name, $this->option_name, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_redirects']
        ]);
    }

    public function sanitize_redirects($input) {
        $new_redirects = [];
        
        if (!is_array($input)) return $new_redirects;

        foreach ($input as $redirect) {
            if (!empty($redirect['original']) && !empty($redirect['target'])) {
                $new_redirects[] = [
                    'original' => sanitize_text_field($redirect['original']),
                    'target' => sanitize_text_field($redirect['target'])
                ];
            }
        }

        return $new_redirects;
    }

    public function perform_redirects() {
        $redirects = get_option($this->option_name, []);
        $current_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        foreach ($redirects as $redirect) {
            if (strpos($current_url, $redirect['original']) !== false) {
                wp_redirect($redirect['target'], 301);
                exit();
            }
        }
    }

    public function admin_page_html() {
        $redirects = get_option($this->option_name, []);
        ?>
        <div class="wrap">
            <h1>Link Redirector</h1>
            <form method="post" action="options.php">
                <?php 
                settings_fields($this->option_name);
                do_settings_sections($this->option_name);
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Original URL</th>
                            <th>Target URL</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="redirects-list">
                        <?php 
                        $total_redirects = max(count($redirects), 1);
                        for ($i = 0; $i < $total_redirects; $i++): 
                        ?>
                        <tr>
                            <td>
                                <input 
                                    type="text" 
                                    name="<?php echo $this->option_name; ?>[<?php echo $i; ?>][original]" 
                                    value="<?php echo isset($redirects[$i]['original']) ? esc_attr($redirects[$i]['original']) : ''; ?>" 
                                    class="widefat"
                                >
                            </td>
                            <td>
                                <input 
                                    type="text" 
                                    name="<?php echo $this->option_name; ?>[<?php echo $i; ?>][target]" 
                                    value="<?php echo isset($redirects[$i]['target']) ? esc_attr($redirects[$i]['target']) : ''; ?>" 
                                    class="widefat"
                                >
                            </td>
                            <td>
                                <button type="button" class="button remove-redirect">Remove</button>
                            </td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
                <div class="submit">
                    <button type="button" id="add-redirect" class="button button-secondary">Add Redirect</button>
                    <?php submit_button('Save Redirects'); ?>
                </div>
            </form>

            <script>
            jQuery(document).ready(function($) {
                let redirectCount = <?php echo count($redirects); ?>;

                $('#add-redirect').on('click', function() {
                    const newRow = `
                        <tr>
                            <td>
                                <input 
                                    type="text" 
                                    name="<?php echo $this->option_name; ?>[${redirectCount}][original]" 
                                    class="widefat"
                                >
                            </td>
                            <td>
                                <input 
                                    type="text" 
                                    name="<?php echo $this->option_name; ?>[${redirectCount}][target]" 
                                    class="widefat"
                                >
                            </td>
                            <td>
                                <button type="button" class="button remove-redirect">Remove</button>
                            </td>
                        </tr>
                    `;
                    $('#redirects-list').append(newRow);
                    redirectCount++;
                });

                $(document).on('click', '.remove-redirect', function() {
                    $(this).closest('tr').remove();
                });
            });
            </script>
        </div>
        <?php
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    new MultiLinkRedirector();
});