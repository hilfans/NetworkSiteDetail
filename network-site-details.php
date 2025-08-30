<?php
/**
 * Plugin Name: Network Site Details
 * Plugin URI: https://it.telkomuniversity.ac.id/
 * Description: Adds Post Count column to the Network Admin's All Sites screen and provides a shortcode for a detailed network report dashboard with caching.
 * Version: 3.9.0
 * Author: Rihansen Purba, Ryan Gusman Banjarnahor, Zafran, Muhammad Kafaby, <a href="https://msp.web.id" target="_blank">Hilfan</a>
 * Author URI: https://github.com/rihansen11/NetworkSiteDetails
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: network-site-details
 */

if (!defined('WPINC')) {
    die;
}

class Network_Site_Details_Enhancer {

    private $transient_name = 'nsd_dashboard_data';
    private $settings_page_slug = 'network-site-details-settings';
    private $option_group = 'nsd_settings_group';

    public function __construct() {
        // Admin hooks
        add_filter('wpmu_blogs_columns', array($this, 'add_custom_sites_columns'));
        add_action('manage_sites_custom_column', array($this, 'render_custom_sites_columns'), 10, 2);
        add_filter('manage_sites-network_sortable_columns', array($this, 'make_columns_sortable'));
        add_action('pre_get_sites', array($this, 'handle_custom_sorting'));
        add_action('admin_init', array($this, 'handle_csv_export'));
        add_action('manage_sites_extra_tablenav', array($this, 'add_export_button'));
        
        // Settings Page and Links
        add_action('network_admin_menu', array($this, 'add_settings_page'));
        add_action('network_admin_edit_update_nsd_settings', array($this, 'save_settings'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
        add_filter('network_admin_plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));

        // Shortcode hooks
        add_shortcode('network_site_details_report', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_shortcode_assets'));
        add_action('wp_ajax_nsd_refresh_cache', array($this, 'ajax_refresh_cache'));
        add_action('wp_ajax_nopriv_nsd_refresh_cache', array($this, 'ajax_refresh_cache'));
    }

    public function add_settings_link($links) {
        $settings_link = '<a href="' . network_admin_url('sites.php?page=' . $this->settings_page_slug) . '">' . __('Settings') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function add_settings_page() {
        add_submenu_page('sites.php', 'Network Site Details Settings', 'Site Details Report', 'manage_sites', $this->settings_page_slug, array($this, 'render_settings_page'));
    }

    public function save_settings() {
        check_admin_referer('nsd-settings-nonce');
        
        if (!current_user_can('manage_sites')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $show_chart = isset($_POST['nsd_show_chart']) ? '1' : '0';
        $show_stats = isset($_POST['nsd_show_stats']) ? '1' : '0';

        update_site_option('nsd_show_chart', $show_chart);
        update_site_option('nsd_show_stats', $show_stats);

        // Clear the cache so changes are reflected immediately
        delete_site_transient($this->transient_name);

        wp_redirect(add_query_arg('updated', 'true', network_admin_url('sites.php?page=' . $this->settings_page_slug)));
        exit;
    }

    public function render_settings_page() {
        ?>
        <style>
            .nsd-settings-wrap { display: flex; flex-wrap: wrap; gap: 20px; }
            .nsd-settings-main { flex: 1; min-width: 60%; }
            .nsd-settings-sidebar { flex-basis: 300px; flex-grow: 1; }
            .nsd-settings-sidebar .card { margin-bottom: 20px; }
            .nsd-donate-button { display: inline-block; background-color: #0073aa; color: #fff !important; padding: 10px 15px; text-decoration: none; border-radius: 4px; text-align: center; font-weight: 600; margin-top: 10px; }
            .nsd-donate-button:hover { background-color: #005a87; }
            .nsd-settings-form-table th { width: 200px; }
        </style>
        <div class="wrap">
            <h1><?php _e('Network Site Details - Settings & Information', 'network-site-details'); ?></h1>
            
            <?php if (isset($_GET['updated']) && $_GET['updated'] === 'true') : ?>
                <div id="message" class="updated notice is-dismissible"><p><?php _e('Settings saved and cache cleared.', 'network-site-details'); ?></p></div>
            <?php endif; ?>

            <div class="nsd-settings-wrap">
                <div class="nsd-settings-main">
                    <form method="post" action="edit.php?action=update_nsd_settings">
                        <?php wp_nonce_field('nsd-settings-nonce'); ?>
                        
                        <div class="card">
                            <h2><?php _e('Shortcode Dashboard Settings', 'network-site-details'); ?></h2>
                            <p><?php _e('Use these options to control which components are displayed on the shortcode dashboard. Disabling components will also prevent their data from being calculated, improving performance.', 'network-site-details'); ?></p>
                            <table class="form-table nsd-settings-form-table">
                                <tr valign="top">
                                    <th scope="row"><?php _e('Display Growth Chart', 'network-site-details'); ?></th>
                                    <td>
                                        <label for="nsd_show_chart">
                                            <input name="nsd_show_chart" type="checkbox" id="nsd_show_chart" value="1" <?php checked(get_site_option('nsd_show_chart', '1'), '1'); ?> />
                                            <?php _e('Show the "Content Growth per Year" chart.', 'network-site-details'); ?>
                                        </label>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e('Display Statistics Cards', 'network-site-details'); ?></th>
                                    <td>
                                        <label for="nsd_show_stats">
                                            <input name="nsd_show_stats" type="checkbox" id="nsd_show_stats" value="1" <?php checked(get_site_option('nsd_show_stats', '1'), '1'); ?> />
                                            <?php _e('Show the key statistics cards (Total Sites, Total Posts, etc.).', 'network-site-details'); ?>
                                        </label>
                                    </td>
                                </tr>
                            </table>
                            <?php submit_button(); ?>
                        </div>
                    </form>

                     <div class="card">
                        <h2><?php _e('Shortcode Usage', 'network-site-details'); ?></h2>
                        <p><?php _e('You can display a beautiful, interactive dashboard of your network\'s statistics on any page or post by using the following shortcode:', 'network-site-details'); ?></p>
                        <p><input type="text" value="[network_site_details_report]" readonly="readonly" class="large-text code"></p>
                    </div>

                </div>

                <div class="nsd-settings-sidebar">
                    <div class="card">
                        <h2 class="title"><strong>Support & Donation</strong></h2>
                        <div class="inside">
                            <p>This plugin is proudly supported by Telkom University. Your donations help us to continue development and support for this plugin.</p>
                            <a href="https://endowment.telkomuniversity.ac.id/donasi-langsung/" target="_blank" rel="dofollow" class="nsd-donate-button"><?php _e('♥ Donate Here ♥', 'network-site-details'); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function add_export_button($which) { if ($which === 'top') { $export_url = wp_nonce_url(network_admin_url('sites.php?export_sites_csv=true'), 'nsd_export_nonce'); echo '<div class="alignleft actions"><a href="' . esc_url($export_url) . '" class="button">' . esc_html__('Export to CSV', 'network-site-details') . '</a></div>'; } }
    public function handle_csv_export() { if (isset($_GET['export_sites_csv']) && $_GET['export_sites_csv'] === 'true') { if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'nsd_export_nonce')) { wp_die(__('Invalid export request.', 'network-site-details')); } if (!current_user_can('manage_sites')) { wp_die(__('Sorry, you are not allowed to export this data.')); } $sites = get_sites(['number' => 0]); if (empty($sites)) { return; } $filename = 'network-sites-export-' . date('Y-m-d') . '.csv'; header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename=' . $filename); $output = fopen('php://output', 'w'); fputcsv($output, array('Site Name', 'URL', 'Last Updated', 'Registered', 'Users', 'Post Count')); foreach ($sites as $site) { $site_details = get_site($site->blog_id); switch_to_blog($site->blog_id); $post_count = wp_count_posts()->publish; $user_count = count_users()['total_users']; restore_current_blog(); fputcsv($output, array( $site_details->blogname, $site_details->domain . $site_details->path, $site_details->last_updated, $site_details->registered, $user_count, $post_count )); } fclose($output); exit; } }
    public function add_custom_sites_columns($columns) { $columns['post_count'] = __('Post Count', 'network-site-details'); return $columns; }
    public function render_custom_sites_columns($column_name, $blog_id) { if ($column_name === 'post_count') { switch_to_blog($blog_id); $count = wp_count_posts(); echo (isset($count->publish)) ? number_format_i18n($count->publish) : '0'; restore_current_blog(); } }
    public function make_columns_sortable($sortable_columns) { $sortable_columns['post_count'] = 'post_count'; return $sortable_columns; }
    
    public function handle_custom_sorting($query) {
        if (!is_admin() || !function_exists('get_current_screen')) return;
        
        $screen = get_current_screen();
        if ($screen && $screen->id === 'sites-network') {
            $orderby = $query->query_vars['orderby'] ?? null;
            $order = $query->query_vars['order'] ?? 'DESC';

            if ($orderby === 'post_count') {
                $sites = get_sites(['fields' => 'ids', 'number' => 0]);
                $site_post_counts = [];

                foreach ($sites as $site_id) {
                    switch_to_blog($site_id);
                    $count = wp_count_posts();
                    $site_post_counts[$site_id] = isset($count->publish) ? (int)$count->publish : 0;
                    restore_current_blog();
                }

                if (strtoupper($order) === 'ASC') {
                    asort($site_post_counts);
                } else {
                    arsort($site_post_counts);
                }
                
                $sorted_ids = array_keys($site_post_counts);
                
                $query->query_vars['site__in'] = $sorted_ids;
                $query->query_vars['orderby'] = 'site__in';
            }
        }
    }

    public function enqueue_shortcode_assets() {
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'network_site_details_report')) {
            wp_enqueue_style('nsd-shortcode-styles', plugin_dir_url(__FILE__) . 'assets/css/shortcode-style.css', array(), '3.9.0');
            $show_chart = get_site_option('nsd_show_chart', '1') === '1';
            if ($show_chart) {
                wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.0', true);
            }
            wp_enqueue_script('nsd-shortcode-script', plugin_dir_url(__FILE__) . 'assets/js/shortcode-dashboard.js', array('jquery'), '3.9.0', true);
            
            wp_localize_script('nsd-shortcode-script', 'nsd_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('nsd_refresh_nonce')
            ));
        }
    }

    public function ajax_refresh_cache() {
        check_ajax_referer('nsd_refresh_nonce', 'nonce');
        delete_site_transient($this->transient_name);
        $new_data = $this->get_all_sites_data();
        wp_send_json_success($new_data);
    }

    private function get_all_sites_data() {
        $cached_data = get_site_transient($this->transient_name);
        if (false !== $cached_data) {
            return $cached_data;
        }
        
        $show_chart = get_site_option('nsd_show_chart', '1') === '1';
        $show_stats = get_site_option('nsd_show_stats', '1') === '1';

        $all_sites_data = [];
        $total_posts = 0;
        $active_sites_count = 0;
        $three_months_ago = strtotime('-3 months');
        $posts_by_year = [];
        $sites = get_sites(['number' => 0]);

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            $site_details = get_site($site->blog_id);
            $post_count = (int) wp_count_posts()->publish;
            $user_count = count_users()['total_users'];
            
            if ($show_chart) {
                $posts_query = new WP_Query(['posts_per_page' => -1, 'post_status' => 'publish', 'fields' => 'ids']);
                if ($posts_query->have_posts()) {
                    foreach($posts_query->posts as $post_id) {
                        $year = get_the_date('Y', $post_id);
                        if (!isset($posts_by_year[$year])) { $posts_by_year[$year] = 0; }
                        $posts_by_year[$year]++;
                    }
                }
                wp_reset_postdata();
            }
            restore_current_blog();

            if ($show_stats) {
                $total_posts += $post_count;
                $last_updated_timestamp = strtotime($site_details->last_updated);
                if ($last_updated_timestamp > $three_months_ago) {
                    $active_sites_count++;
                }
            }

            $all_sites_data[] = [
                'name' => $site_details->blogname, 'url' => $site_details->domain . $site_details->path,
                'home_url' => $site_details->home, 'last_updated' => $site_details->last_updated,
                'registered' => $site_details->registered, 'users' => $user_count, 'post_count' => $post_count,
            ];
        }
        
        $data_to_cache = ['sites' => $all_sites_data];

        if ($show_stats) {
            $total_sites = count($sites);
            $avg_posts = ($total_sites > 0) ? round($total_posts / $total_sites) : 0;
            $data_to_cache['stats'] = [
                'total_sites' => $total_sites, 'total_posts' => $total_posts,
                'active_sites' => $active_sites_count, 'avg_posts' => $avg_posts,
            ];
        }
        
        if ($show_chart) {
            ksort($posts_by_year);
            $data_to_cache['chart_data'] = [
                'labels' => array_keys($posts_by_year), 'data' => array_values($posts_by_year),
            ];
        }

        set_site_transient($this->transient_name, $data_to_cache, MONTH_IN_SECONDS);
        return $data_to_cache;
    }

    public function render_shortcode() {
        $dashboard_data = $this->get_all_sites_data();
        wp_add_inline_script('nsd-shortcode-script', 'const nsd_data = ' . wp_json_encode($dashboard_data) . ';', 'before');
        
        $show_chart = get_site_option('nsd_show_chart', '1') === '1';
        $show_stats = get_site_option('nsd_show_stats', '1') === '1';

        ob_start();
        ?>
        <div id="nsd-dashboard-app">
            <?php if ($show_chart): ?>
                <div class="nsd-chart-container"><canvas id="nsd-posts-chart"></canvas></div>
            <?php endif; ?>
            
            <?php if ($show_stats): ?>
                <div id="nsd-summary-cards" class="nsd-summary-grid"></div>
            <?php endif; ?>

            <div class="nsd-controls-wrapper">
                <div class="nsd-search-wrapper"><input type="text" id="nsd-search-input" placeholder="Search sites..."></div>
                <div class="nsd-filters-wrapper">
                    <select id="nsd-sort-by"><option value="post_count">Sort by Post Count</option><option value="last_updated">Sort by Last Update</option><option value="name">Sort by Site Name</option></select>
                    <select id="nsd-sort-order"><option value="desc">Desc</option><option value="asc">Asc</option></select>
                    <select id="nsd-filter-year"></select>
                    <select id="nsd-per-page"><option>10</option><option>50</option><option>100</option></select>
                    <span>per page</span>
                </div>
                <div class="nsd-actions-wrapper">
                    <button id="nsd-refresh-btn" class="nsd-btn nsd-btn-primary">Refresh Data</button>
                    <div class="nsd-view-toggle">
                        <button id="nsd-grid-view-btn" class="nsd-btn nsd-btn-icon active">Grid</button>
                        <button id="nsd-list-view-btn" class="nsd-btn nsd-btn-icon">List</button>
                    </div>
                </div>
            </div>
            <div id="nsd-data-container" class="nsd-grid-view"></div>
            <div id="nsd-pagination" class="nsd-pagination-wrapper"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new Network_Site_Details_Enhancer();
