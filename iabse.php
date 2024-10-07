<?php
/*
 * Plugin Name:       IABSE Core
 * Plugin URI:        https://github.com/shakib6472/
 * Description:       This plugin is formatted for IABSE Nonprofit organization.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Shakib Shown
 * Author URI:        https://github.com/shakib6472/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       iabse
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
require_once(__DIR__.'/tutor_f.php');

// Enqueue the admin stylesheet and Bootstrap

function iabse_enqueue_admin_styles()
{
    wp_enqueue_style(
        'iabse-admin-css',
        plugin_dir_url(__FILE__) . 'admin.css',
        false,
        '1.0',
        'all'
    );
    
}
add_action('admin_enqueue_scripts', 'iabse_enqueue_admin_styles');

// Add the IASBE Core menu page
add_action('admin_menu', 'iabse_core_menu');

function iabse_core_menu()
{
    add_menu_page(
        'IASBE Core',      // Page title
        'IASBE Core',      // Menu title
        'manage_options',  // Capability
        'iabse',           // Menu slug
        'iabse_core_page', // Callback function to display content
        'https://academy.academy-iabse.org/wp-content/uploads/2024/09/cropped-Official_IABSE_Logo_Only_Trans.fw_.png', // Icon URL
        -1                 // Menu position
    );
}

// Display the content of the IASBE Core menu page
function iabse_core_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Define the boxes with title, link, and icon URL
    $boxes = [
        [
            'title' => 'Toolbox',
            'link'  => 'edit.php?post_type=tool-box',
            'icon'  => 'https://academy.academy-iabse.org/wp-content/uploads/2024/09/cropped-Official_IABSE_Logo_Only_Trans.fw_.png',
        ],
        [
            'title' => 'Photo Archive',
            'link'  => 'edit.php?post_type=photo-archive',
            'icon'  => 'https://academy.academy-iabse.org/wp-content/uploads/2024/09/cropped-Official_IABSE_Logo_Only_Trans.fw_.png',
        ],
        [
            'title' => 'Repositories',
            'link'  => 'edit.php?post_type=repository',
            'icon'  => 'https://academy.academy-iabse.org/wp-content/uploads/2024/09/cropped-Official_IABSE_Logo_Only_Trans.fw_.png',
        ],
    ];

    echo '<div class="wrap">';
    echo '<h1>IASBE Core</h1>';
    echo '<p>Welcome to the IASBE Core settings page.</p>';

    // Start Bootstrap grid container
    echo '<div class="container mt-5">';
    echo '<div class="row">';

    // Loop through each box
    foreach ($boxes as $box) {
        ?>
        <div class="col-12 col-md-6 col-lg-4 mb-4">
            <a href="<?php echo esc_url($box['link']); ?>" class="text-decoration-none">
                <div class="card text-center shadow-sm p-4">
                    <img src="<?php echo esc_url($box['icon']); ?>" alt="<?php echo esc_attr($box['title']); ?> Icon" class="card-img-top" style="width: 80px; height: 80px; margin: 0 auto;">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo esc_html($box['title']); ?></h3>
                    </div>
                </div>
            </a>
        </div>
        <?php
    }
?> 
<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' integrity='sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN' crossorigin='anonymous'>
</div>
</div>
</div>
<?php 
}

// Remove unwanted admin pages
add_action('admin_menu', 'iabse_remove_unwanted_admin_pages', 999);

function iabse_remove_unwanted_admin_pages()
{
    remove_menu_page('envato-market');
    remove_menu_page('wc-admin');
}
