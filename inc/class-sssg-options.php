<?php

class SSSG_Options
{
    private $general_options;
    private $export_options;

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
    }

    public function add_plugin_page()
    {
        add_options_page(
            __('Simple Static Site Generator', 'sssg'),
            __('SSSG', 'sssg'),
            'manage_options',
            'sssg',
            array($this, 'create_admin_page')
        );
    }

    public function create_admin_page()
    {
        // Fetch options from the database
        $this->general_options = get_option('sssg_general');
        $this->export_options = get_option('sssg_export');
        $active_tab = isset($_GET["tab"]) ? $_GET["tab"] : "general";

        $active_general = ($active_tab == 'general' ? ' nav-tab-active' : '');
        $active_export = ($active_tab == 'export' ? ' nav-tab-active' : '');
        // Start buffer for settings
        ob_start();
        echo '<div class="wrap">';
        echo '<h1>' . __('Simple Static Site Generator', 'sssg') . '</h1>';
        // echo '<h3>'.__DIR__.'</h3>';
        echo '<form method="post" action="options.php">';
        settings_fields('sssg_option_group');
        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="?page=sssg&tab=general" class="nav-tab' . $active_general . '">General</a>';
        echo '<a href="?page=sssg&tab=export" class="nav-tab' . $active_export . '">Export</a>';
        echo '</h2>';
        echo '<div class="tab-content' . $active_general . '">';
        do_settings_sections('sssg_general');
        echo '</div>'; // End tab-content
        echo '<div class="tab-content' . $active_export . '">';
        do_settings_sections('sssg_export');
        echo '</div>'; // End tab-content

        submit_button();
        echo '<button type="button" id="reset_default" class="button button-secondary">' . __('Reset to Default', 'sssg') . '</button>';

        echo '</form>';
        echo '</div>'; // End wrap
        // End buffer and output settings
        $settings = ob_get_clean();
        echo $settings;
    }

    public function page_init()
    {
        $plugin_dir = rtrim( plugin_dir_path( __DIR__ ), '/\\' );
        $plugin_url  = rtrim( plugin_dir_url( __DIR__ ), '/\\' );
        $assets_url  = $plugin_url . '/assets';
		wp_enqueue_style(
			'sssg-admin',
			$assets_url . '/admin.css',
			[],
			filemtime( $plugin_dir . '/assets/admin.css' )
		);

		wp_enqueue_script(
			'ssss-admin',
			$assets_url . '/admin.js',
			[ 'jquery' ],
			filemtime( $plugin_dir . '/assets/admin.js' ),
			true
		);

        // Define default values
        $general_defaults = array(
            'base_path' => 'static',
            'assets_path' => '/assets',
            'base_url' => get_option('siteurl'),
            'additional_files' => ''
        );

        $export_defaults = array(
            'export_mode' => 'automatic',
            'export_target' => 'full'
        );

        // Register options with default values
        register_setting('sssg_option_group', 'sssg_general', array('default' => $general_defaults));
        // Add sections and fields for the 'General' tab...
        add_settings_section('sssg_general_section', __('General', 'sssg'), null, 'sssg_general');
        add_settings_field('sssg_base_path', __('Base Path', 'sssg'), array($this, 'sssg_base_path_callback'), 'sssg_general', 'sssg_general_section');
        add_settings_field('sssg_assets_path', __('Assets Path', 'sssg'), array($this, 'sssg_assets_path_callback'), 'sssg_general', 'sssg_general_section');
        add_settings_field('sssg_base_url', __('Base URL', 'sssg'), array($this, 'sssg_base_url_callback'), 'sssg_general', 'sssg_general_section');
        add_settings_field('sssg_additional_files', __('Additional files', 'sssg'), array($this, 'sssg_additional_callback'), 'sssg_general', 'sssg_general_section');

        // Register options with default values for export
        register_setting('sssg_option_group', 'sssg_export', array('default' => $export_defaults));
        // Add sections and fields for the 'Export' tab...
        add_settings_section('sssg_export_section', __('Export', 'sssg'), null, 'sssg_export');
        add_settings_field('sssg_export_mode', __('Export Mode', 'sssg'), array($this, 'sssg_export_mode_callback'), 'sssg_export', 'sssg_export_section');
        add_settings_field('sssg_export_target', __('Export Target', 'sssg'), array($this, 'sssg_export_target_callback'), 'sssg_export', 'sssg_export_section');
    }

    // Callback functions to display the fields
    public function sssg_base_path_callback()
    {
        printf(
            '<input type="text" id="sssg_base_path" name="sssg_general[base_path]" value="%s" />',
            isset($this->general_options['base_path']) ? esc_attr($this->general_options['base_path']) : ''
        );
    }

    public function sssg_assets_path_callback()
    {
        printf(
            '<input type="text" id="sssg_assets_path" name="sssg_general[assets_path]" value="%s" />',
            isset($this->general_options['assets_path']) ? esc_attr($this->general_options['assets_path']) : ''
        );
    }

    public function sssg_base_url_callback()
    {
        printf(
            '<input type="text" id="sssg_base_url" name="sssg_general[base_url]" value="%s" />',
            isset($this->general_options['base_url']) ? esc_url($this->general_options['base_url']) : ''
        );
        echo '<a href="' . esc_url($this->general_options['base_url']) . '" target="_blank">' . __('Open Static Site', 'sssg') . '</a>';
    }

    public function sssg_additional_callback()
    {
        printf(
            '<textarea id="sssg_additional_files" name="sssg_general[additional_files]" rows="10">%s</textarea>',
            isset($this->general_options['additional_files']) ? esc_attr($this->general_options['additional_files']) : ''
        );
        echo "<br>Please enter the files you want to copy to the static site, one per line. <br>Example: <code>wp-content/themes/my-theme/style.css</code>";
    }

    public function sssg_export_mode_callback()
    {
        printf(
            '<select id="sssg_export_mode" name="sssg_export[export_mode]">
                <option value="automatic" %1$s>%2$s</option>
                <option value="manual" %3$s>%4$s</option>
            </select>',
            selected($this->export_options['export_mode'], 'automatic', false),
            __('Automatic', 'sssg'),
            selected($this->export_options['export_mode'], 'manual', false),
            __('Manual', 'sssg')
        );
    }

    public function sssg_export_target_callback()
    {
        // You will have to populate this dropdown with actual post types or pages
        echo '<select id="sssg_export_target" name="sssg_export[export_target]">';
        echo '<option value="full">' . __('Full Site', 'sssg') . '</option>';
        echo '<option value="partial">' . __('Partial Site', 'sssg') . '</option>';
        echo '</select>';
    }
}
