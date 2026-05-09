<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Edtech_Auth_Routes {
    const ROUTE_VERSION = '1.2.0';

    public function register_routes() {
        foreach ( $this->get_auth_routes() as $slug => $auth_type ) {
            add_rewrite_rule( '^' . preg_quote( $slug, '/' ) . '/?$', 'index.php?edtech_auth=' . $auth_type, 'top' );
        }

        foreach ( $this->get_dashboard_routes() as $slug => $dashboard_type ) {
            add_rewrite_rule( '^' . preg_quote( $slug, '/' ) . '/?$', 'index.php?edtech_dashboard=' . $dashboard_type, 'top' );
        }

        add_rewrite_rule( '^video-player/([0-9]+)/?$', 'index.php?edtech_video_id=$matches[1]', 'top' );
    }

    public function maybe_flush_rewrite_rules() {
        if ( get_option( 'edtech_auth_route_version' ) === self::ROUTE_VERSION ) {
            return;
        }

        $this->register_routes();
        flush_rewrite_rules( false );
        update_option( 'edtech_auth_route_version', self::ROUTE_VERSION );
    }

    public function register_query_vars( $vars ) {
        $vars[] = 'edtech_auth';
        $vars[] = 'edtech_dashboard';
        $vars[] = 'edtech_video_id';
        return $vars;
    }

    public function catch_routes( $wp ) {
        $path = $this->get_current_path();

        if ( empty( $wp->query_vars['edtech_auth'] ) ) {
            $auth_type = $this->get_auth_type_from_path( $path );
            if ( $auth_type ) {
                $wp->query_vars['edtech_auth'] = $auth_type;
                $wp->is_404 = false;
            }
        }

        if ( empty( $wp->query_vars['edtech_dashboard'] ) ) {
            $dashboard_type = $this->get_dashboard_type_from_path( $path );
            if ( $dashboard_type ) {
                $wp->query_vars['edtech_dashboard'] = $dashboard_type;
                $wp->is_404 = false;
            }
        }

        if ( empty( $wp->query_vars['edtech_video_id'] ) && preg_match( '#^video-player/([0-9]+)$#', $path, $matches ) ) {
            $wp->query_vars['edtech_video_id'] = absint( $matches[1] );
            $wp->is_404 = false;
        }
    }

    public function template_include( $template ) {
        $auth_type = get_query_var( 'edtech_auth', false );
        if ( ! $auth_type ) {
            $auth_type = $this->get_auth_type_from_path( $this->get_current_path() );
            if ( $auth_type ) {
                $this->set_query_var_200( 'edtech_auth', $auth_type );
            }
        }

        if ( $auth_type ) {
            $theme_template = locate_template( 'page-auth.php' );
            if ( $theme_template ) {
                return $theme_template;
            }

            $plugin_template = EDTECH_PLUGIN_DIR . 'templates/page-auth.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }

        $dashboard_type = get_query_var( 'edtech_dashboard', false );
        if ( ! $dashboard_type ) {
            $dashboard_type = $this->get_dashboard_type_from_path( $this->get_current_path() );
            if ( $dashboard_type ) {
                $this->set_query_var_200( 'edtech_dashboard', $dashboard_type );
            }
        }

        if ( $dashboard_type ) {
            $theme_template = locate_template( 'page-dashboard.php' );
            if ( $theme_template ) {
                return $theme_template;
            }

            $plugin_template = EDTECH_PLUGIN_DIR . 'templates/page-dashboard.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }

        $video_id = absint( get_query_var( 'edtech_video_id', 0 ) );
        if ( ! $video_id && preg_match( '#^video-player/([0-9]+)$#', $this->get_current_path(), $matches ) ) {
            $video_id = absint( $matches[1] );
            $this->set_query_var_200( 'edtech_video_id', $video_id );
        }

        if ( $video_id ) {
            $plugin_template = EDTECH_PLUGIN_DIR . 'templates/page-video-player.php';
            if ( file_exists( $plugin_template ) ) {
                $this->set_query_var_200( 'edtech_video_id', $video_id );
                return $plugin_template;
            }
        }

        return $template;
    }

    public function render_route_on_404() {
        if ( ! is_404() ) {
            return;
        }

        $path = $this->get_current_path();

        if ( $this->get_auth_type_from_path( $path ) || $this->get_dashboard_type_from_path( $path ) || preg_match( '#^video-player/([0-9]+)$#', $path ) ) {
            status_header( 200 );
            nocache_headers();
        }
    }

    public function get_auth_routes() {
        return array(
            'ed-admin-login'   => 'ed_admin_login',
            'admin-login'      => 'admin_login',
            'teacher-login'    => 'teacher_login',
            'teacher-register' => 'teacher_register',
            'student-login'    => 'student_login',
            'student-register' => 'student_register',
            'forgot-password'  => 'forgot_password',
            'reset-password'   => 'reset_password',
        );
    }

    public function get_dashboard_routes() {
        return array(
            'admin-dashboard'   => 'admin',
            'teacher-dashboard' => 'teacher',
            'student-dashboard' => 'student',
            'dashboard'         => 'any',
        );
    }

    public function get_auth_type_from_path( $path ) {
        $routes = $this->get_auth_routes();
        return $routes[ $path ] ?? false;
    }

    public function get_dashboard_type_from_path( $path ) {
        $routes = $this->get_dashboard_routes();
        return $routes[ $path ] ?? false;
    }

    public function get_current_path() {
        $path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
        $path = trim( (string) $path, '/' );

        $home_path = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
        if ( $home_path && 0 === strpos( $path, $home_path ) ) {
            $path = ltrim( substr( $path, strlen( $home_path ) ), '/' );
        }

        return trim( $path, '/' );
    }

    private function set_query_var_200( $var, $value ) {
        global $wp_query;

        $wp_query->query_vars[ $var ] = $value;
        $wp_query->is_404 = false;
        status_header( 200 );
    }
}
