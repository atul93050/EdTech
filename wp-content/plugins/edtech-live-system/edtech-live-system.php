<?php
/**
 * Plugin Name: EdTech Live System
 * Plugin URI: https://example.com/edtech-live-system
 * Description: Custom EdTech SaaS business logic plugin for student, teacher and live class management.
 * Version: 1.0.0
 * Author: Premium EdTech Team
 * Author URI: https://example.com
 * License: GPL2+
 * Text Domain: edtech-live-system
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'EDTECH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EDTECH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EDTECH_PLUGIN_VERSION', '1.2.0' );

require_once EDTECH_PLUGIN_DIR . 'database/class-edtech-db.php';
require_once EDTECH_PLUGIN_DIR . 'helpers/class-edtech-helpers.php';
require_once EDTECH_PLUGIN_DIR . 'auth/validators/class-edtech-auth-validator.php';
require_once EDTECH_PLUGIN_DIR . 'auth/services/class-edtech-auth-service.php';
require_once EDTECH_PLUGIN_DIR . 'auth/controllers/class-edtech-auth-controller.php';
require_once EDTECH_PLUGIN_DIR . 'auth/routes/class-edtech-auth-routes.php';
require_once EDTECH_PLUGIN_DIR . 'auth/middleware/class-edtech-auth-middleware.php';
require_once EDTECH_PLUGIN_DIR . 'services/class-edtech-auth.php';
require_once EDTECH_PLUGIN_DIR . 'services/class-edtech-dashboard.php';
require_once EDTECH_PLUGIN_DIR . 'services/class-edtech-subjects.php';
require_once EDTECH_PLUGIN_DIR . 'services/class-edtech-liveclasses.php';
require_once EDTECH_PLUGIN_DIR . 'services/class-edtech-recorded-classes.php';
require_once EDTECH_PLUGIN_DIR . 'ajax/class-edtech-ajax.php';

class Edtech_Live_System {
    private static $instance;
    public $db;
    public $helpers;
    public $auth;
    public $auth_routes;
    public $auth_middleware;
    public $dashboard;
    public $subjects;
    public $liveclasses;
    public $recorded_classes;
    public $ajax;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->setup();
        }
        return self::$instance;
    }

    private function setup() {
        $this->db = new Edtech_DB();
        $this->helpers = new Edtech_Helpers();
        $this->auth = new Edtech_Auth( $this->db, $this->helpers );
        $this->auth_routes = new Edtech_Auth_Routes();
        $this->auth_middleware = new Edtech_Auth_Middleware( $this->db, $this->helpers, $this->auth_routes );
        $this->dashboard = new Edtech_Dashboard( $this->db, $this->helpers );
        $this->subjects = new Edtech_Subjects( $this->db, $this->helpers );
        $this->liveclasses = new Edtech_Live_Classes( $this->db, $this->helpers );
        $this->recorded_classes = new Edtech_Recorded_Classes( $this->db, $this->helpers );
        $this->ajax = new Edtech_Ajax( $this );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'init', array( 'Edtech_Live_System', 'create_roles' ), 5 );
        add_action( 'init', array( $this, 'register_shortcodes' ) );
        add_action( 'init', array( $this->auth_routes, 'register_routes' ), 6 );
        add_action( 'init', array( $this->auth_routes, 'maybe_flush_rewrite_rules' ), 20 );
        add_filter( 'query_vars', array( $this->auth_routes, 'register_query_vars' ) );
        add_filter( 'template_include', array( $this->auth_routes, 'template_include' ) );
        add_filter( 'show_admin_bar', array( $this->auth_middleware, 'show_admin_bar_conditionally' ) );
        add_action( 'parse_request', array( $this->auth_routes, 'catch_routes' ) );
        add_action( 'template_redirect', array( $this->auth_middleware, 'protect_frontend_routes' ), 1 );
        add_action( 'template_redirect', array( $this->auth_routes, 'render_route_on_404' ), 2 );
        add_action( 'admin_init', array( $this->auth_middleware, 'restrict_wp_admin' ) );
    }

    public function register_shortcodes() {
        add_shortcode( 'edtech_auth', array( $this->auth, 'render_auth_form' ) );
        add_shortcode( 'edtech_dashboard', array( $this->dashboard, 'render_dashboard' ) );
        add_shortcode( 'edtech_teacher_videos', array( $this->recorded_classes, 'render_teacher_recorded_classes' ) );
        add_shortcode( 'edtech_video_library', array( $this->recorded_classes, 'render_video_library' ) );
        add_shortcode( 'edtech_video_player', array( $this->recorded_classes, 'render_video_player' ) );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'edtech-ui-notifications', EDTECH_PLUGIN_URL . 'assets/css/ui/notifications.css', array(), EDTECH_PLUGIN_VERSION );
        wp_enqueue_style( 'edtech-ui-modals', EDTECH_PLUGIN_URL . 'assets/css/ui/modals.css', array( 'edtech-ui-notifications' ), EDTECH_PLUGIN_VERSION );
        wp_enqueue_style( 'edtech-ui-loaders', EDTECH_PLUGIN_URL . 'assets/css/ui/loaders.css', array( 'edtech-ui-modals' ), EDTECH_PLUGIN_VERSION );
        wp_enqueue_style( 'edtech-plugin-style', EDTECH_PLUGIN_URL . 'assets/css/plugin.css', array( 'edtech-ui-loaders' ), EDTECH_PLUGIN_VERSION );
        wp_enqueue_script( 'chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', array(), '4.4.0', true );
        wp_enqueue_script( 'edtech-ui-notifications', EDTECH_PLUGIN_URL . 'assets/js/ui/notifications.js', array(), EDTECH_PLUGIN_VERSION, true );
        wp_enqueue_script( 'edtech-ui-modals', EDTECH_PLUGIN_URL . 'assets/js/ui/modals.js', array( 'edtech-ui-notifications' ), EDTECH_PLUGIN_VERSION, true );
        wp_enqueue_script( 'edtech-ui-loaders', EDTECH_PLUGIN_URL . 'assets/js/ui/loaders.js', array( 'edtech-ui-modals' ), EDTECH_PLUGIN_VERSION, true );
        wp_enqueue_script( 'edtech-plugin-js', EDTECH_PLUGIN_URL . 'assets/js/plugin.js', array( 'jquery', 'edtech-ui-loaders' ), EDTECH_PLUGIN_VERSION, true );
        wp_localize_script( 'edtech-plugin-js', 'EDTECH_AJAX', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'edtech_live_nonce' ),
            'home_url' => home_url( '/' ),
            'admin_dashboard' => home_url( '/admin-dashboard' ),
            'teacher_dashboard' => home_url( '/teacher-dashboard' ),
            'student_dashboard' => home_url( '/student-dashboard' ),
        ) );
    }

    public function restrict_admin_access() {
        $this->auth_middleware->restrict_wp_admin();
    }

    public function register_routes() {
        $this->auth_routes->register_routes();
    }

    public function register_query_vars( $vars ) {
        return $this->auth_routes->register_query_vars( $vars );
    }

    public function maybe_load_auth_template( $template ) {
        return $this->auth_routes->template_include( $template );
    }

    private function get_auth_type_from_path( $path ) {
        return $this->auth_routes->get_auth_type_from_path( $path );
    }

    public function show_admin_bar_conditionally( $show ) {
        return $this->auth_middleware->show_admin_bar_conditionally( $show );
    }

    public function render_auth_template_on_404() {
        $this->auth_routes->render_route_on_404();
    }

    public function catch_auth_routes( $wp ) {
        $this->auth_routes->catch_routes( $wp );
    }

    public static function activate() {
        $db = new Edtech_DB();
        $db->create_tables();
        self::create_roles();
        $routes = new Edtech_Auth_Routes();
        $routes->register_routes();
        flush_rewrite_rules();
        update_option( 'edtech_auth_route_version', Edtech_Auth_Routes::ROUTE_VERSION );
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public static function create_roles() {
        $roles = array(
            'edtech_student' => array(
                'label' => 'Student',
                'caps'  => array( 'read' => true ),
            ),
            'edtech_teacher' => array(
                'label' => 'Teacher',
                'caps'  => array( 'read' => true ),
            ),
            'edtech_super_admin' => array(
                'label' => 'Super Admin',
                'caps'  => array(
                    'read'           => true,
                    'edit_posts'     => true,
                    'manage_options' => true,
                ),
            ),
        );

        foreach ( $roles as $role_key => $role_data ) {
            $role = get_role( $role_key );
            if ( ! $role ) {
                add_role( $role_key, $role_data['label'], $role_data['caps'] );
                continue;
            }

            foreach ( $role_data['caps'] as $cap => $grant ) {
                $role->add_cap( $cap, $grant );
            }
        }
    }
}

register_activation_hook( __FILE__, array( 'Edtech_Live_System', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Edtech_Live_System', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Edtech_Live_System', 'instance' ) );
