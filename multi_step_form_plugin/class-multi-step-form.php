<?php
class Multi_Step_Form {
    private $plugin_name;
    public function __construct() {   
        $this->register_hooks();
        $this->plugin_name = get_option('wporg_setting_name', 'multi_step_form_plugin');
        
    }

    public function register_hooks() {
        add_shortcode('multi_step_form', [$this, 'render_form']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_submit_form_content', [$this, 'handle_form_submission']);
        add_action('wp_ajax_nopriv_submit_form_content', [$this, 'handle_form_submission']);
        add_action('init', [$this, 'custom_post']);
        add_action('admin_menu', [$this, 'my_plugin_menu_contents']);
        add_action('admin_init', [$this, 'wporg_settings_init']);
        add_action('admin_post_upload_csv', [$this, 'handle_csv_upload']);

    }

    public function enqueue_scripts() {
        wp_enqueue_script('my-plugin-script', plugin_dir_url(__FILE__) . 'admin/js/form-script.js', ['jquery'], null, true);
        wp_localize_script('my-plugin-script', 'myAjaxcustom', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('form_nonce')
        ]);
        wp_enqueue_style('my-plugin-style', plugin_dir_url(__FILE__) . 'admin/css/form-style.css');
    }

    public function render_form() {
        ob_start();
        ?>
        <form id="multi-step-form" method="post">
            <input type="hidden" name="action" value="submit_form_content" />
            <input type="hidden" name="security" value="<?php echo esc_attr(wp_create_nonce('form_nonce')); ?>" />
            <div class="step step-1">
                <h2>Step 1: Personal Information</h2>
                <input type="text" name="first_name" placeholder="First Name" required><br>
                <input type="text" name="last_name" placeholder="Last Name" required><br>
                <input type="email" name="email" placeholder="Email" required><br>
                <input type="text" name="address" placeholder="Address" required><br>
                <input type="text" name="country" placeholder="Country" class="autocomplete" required><br>
                <input type="text" name="state" placeholder="State" required><br>
                <input type="text" name="city" placeholder="City" required><br>
                <button type="button" class="next-step">Next</button>
            </div>
            <div class="step step-2" id="step-2">
                <h2>Step 2: Company Info</h2>
                <input type="text" name="company_name" placeholder="Company Name" required><br>
                <div id="company-address-container">
                    <input type="text" name="company_address[]" placeholder="Company Address" required>
                </div>
                <button type="button" id="add-address">Add Another Address</button>
                <button type="button" class="prev-step">Previous</button>
                <button type="button" class="next-step">Next</button>
            </div>
            <div class="step step-3">
                <h2>Step 3: Card Info</h2>
                <input type="text" name="card_number" placeholder="Card Number" required><br>
                <input type="text" name="expiry_date" placeholder="Expiry Date (MM/YY)" required><br>
                <input type="text" name="cvv" placeholder="CVV" required><br>
                <button type="button" class="prev-step">Previous</button>
                <button type="submit" id="submit-button">Submit</button>
            </div>
        </form>
        <div id="display-content"></div>
        <?php
        return ob_get_clean();
    }

    public function handle_form_submission() {
        check_ajax_referer('form_nonce', 'security');

        // Sanitize and process the data
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $address = sanitize_text_field($_POST['address']);
        $country = sanitize_text_field($_POST['country']);
        $state = sanitize_text_field($_POST['state']);
        $city = sanitize_text_field($_POST['city']);
        $company_name = sanitize_text_field($_POST['company_name']);
        $company_addresses = array_map('sanitize_text_field', $_POST['company_address'] ?? []);
        $card_number = sanitize_text_field($_POST['card_number']);
        $expiry_date = sanitize_text_field($_POST['expiry_date']);
        $cvv = sanitize_text_field($_POST['cvv']);

        // Insert the post
        $post_id = wp_insert_post([
            'post_type' => 'form_submission',
            'post_title' => $first_name . ' ' . $last_name,
            'post_content' => '',
            'post_status' => 'publish'
        ]);

        if (is_wp_error($post_id)) {
            wp_send_json_error(['message' => 'Error creating post: ' . $post_id->get_error_message()]);
            return;
        }

        // Update post meta
        update_post_meta($post_id, 'first_name', $first_name);
        update_post_meta($post_id, 'last_name', $last_name);
        update_post_meta($post_id, 'email', $email);
        update_post_meta($post_id, 'address', $address);
        update_post_meta($post_id, 'country', $country);
        update_post_meta($post_id, 'state', $state);
        update_post_meta($post_id, 'city', $city);
        update_post_meta($post_id, 'company_name', $company_name);
        update_post_meta($post_id, 'company_addresses', $company_addresses);
        update_post_meta($post_id, 'card_number', $card_number);
        update_post_meta($post_id, 'expiry_date', $expiry_date);
        update_post_meta($post_id, 'cvv', $cvv);

        wp_send_json_success([
            'message' => 'Form submitted successfully!',
            'data' => $_POST
        ]);
    }

    public function custom_post() {
        register_post_type('form_submission', [
            'labels' => [
                'name' => __('Form Submissions'),
                'singular_name' => __('Form Submission')
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor']
        ]);
    }

    public function my_plugin_menu_contents() {
        // Add a new top-level menu
        add_menu_page(
            'My Plugin Page',          // Page title
            'My Plugin Settings',        // Menu title
            'manage_options',          // Capability required
            'my-plugin',               // Menu slug
            [$this, 'my_plugin_page_content'] // Function to display the page content
        );
    }

    public function my_plugin_page_content() {
        ?>
        <div class="wrap">
            <h1>My Plugin Settings</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_csv">
                <?php
                // Output security fields
                settings_fields('my_plugin_options_group');
                
                // Output settings sections
                do_settings_sections('my-plugin');
                
                // Output save settings button
                submit_button('Upload CSV');
                ?>
            </form>
        </div>
        <?php
    }



  public function wporg_settings_init() {
    // Register a new setting for "my_plugin" page
    register_setting('my_plugin_options_group', 'wporg_setting_name');
    register_setting('my_plugin_options_group', 'csv_file_path');

    // Register a new section in the "my_plugin" page
    add_settings_section(
        'wporg_settings_section',
        'WPOrg Settings Section',
        [$this, 'wporg_settings_section_callback'],
        'my-plugin'
    );

    // Register a new field in the "wporg_settings_section" section, inside the "my_plugin" page
    add_settings_field(
        'wporg_settings_field',
        'WPOrg Setting',
        [$this, 'wporg_settings_field_callback'],
        'my-plugin',
        'wporg_settings_section'
    );

    // Add a new field for file upload
    add_settings_field(
        'csv_file_upload_field',
        'Upload CSV File',
        [$this, 'csv_file_upload_field_callback'],
        'my-plugin',
        'wporg_settings_section'
    );
}
    public function csv_file_upload_field_callback() {
        // Get the saved file path if available
        $file_path = get_option('csv_file_path', '');
        ?>
        <input type="file" name="csv_file_upload" id="csv_file_upload" />
        <?php if ($file_path): ?>
            <p><a href="<?php echo esc_url($file_path); ?>" target="_blank">View Uploaded CSV</a></p>
        <?php endif; ?>
        <?php
    }


    // section content cb
    public function wporg_settings_section_callback() {
        echo '<h2>Plugin Title</h2>';
    }

    // field content cb
    public function wporg_settings_field_callback() {
        // get the value of the setting we've registered with register_setting()
       $setting = get_option('wporg_setting_name', 'multi_step_form_plugin');
        // output the field
        ?>
          <input type="text" name="wporg_setting_name" value="<?php echo esc_attr($setting); ?>">
        <?php
    }

public function handle_csv_upload() {
    if (isset($_FILES['csv_file_upload']) && !empty($_FILES['csv_file_upload']['tmp_name'])) {
        $file = $_FILES['csv_file_upload'];

        // Check file type and size
        if ($file['type'] === 'text/csv' || $file['type'] === 'application/vnd.ms-excel') {
            // Handle file upload
            $upload_dir = wp_upload_dir();
            $target_file = $upload_dir['path'] . '/' . basename($file['name']);

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                // Update option with file path
                update_option('csv_file_path', $upload_dir['url'] . '/' . basename($file['name']));

                // Process the CSV file
                $this->process_csv_file($target_file);
            } else {
                wp_die('File upload failed.');
            }
        } else {
            wp_die('Invalid file type. Please upload a CSV file.');
        }
    }

    // Redirect back to settings page
    wp_redirect(admin_url('admin.php?page=my-plugin'));
    exit;
}
private function process_csv_file($file_path) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'my_custom_table';

    if (($handle = fopen($file_path, 'r')) !== FALSE) {
        // Skip the header row if needed
        fgetcsv($handle);

        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            // Adjust the indices based on the structure of your CSV and custom table
            $post_title = isset($data[0]) ? sanitize_text_field($data[0]) : ''; // Column 1
            $post_name = isset($data[1]) ? sanitize_text_field($data[1]) : ''; // Column 2
            $post_content = isset($data[2]) ? sanitize_textarea_field($data[2]) : ''; // Column 3
            $post_status = isset($data[3]) ? sanitize_text_field($data[3]) : ''; // Column 4

            // Insert data into the custom table
            $wpdb->insert(
                $table_name,
                [
                    'post_title'   => $post_title,
                    'post_name'    => $post_name,
                    'post_content' => $post_content,
                    'post_status'  => $post_status,
                ]
            );
        }

        fclose($handle);
    } else {
        wp_die('Error reading the CSV file.');
    }
}




}

// Instantiate the class
//new Multi_Step_Form();