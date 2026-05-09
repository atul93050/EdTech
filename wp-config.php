<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'college' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         ';1ducU2+=?49c$X?SoH54y_9HH(g2o[39P}}?I2fjfdNsH3WS*v3]gg%3ek-{P5J' );
define( 'SECURE_AUTH_KEY',  ')wR1)pn!vOOAU oy/Opdf2Azd3uTB>iJwLyF8gHt{vatSu7YJm8_MS!_aUBzaBs-' );
define( 'LOGGED_IN_KEY',    'Ly,M-(tc4~[8_c_6?Mi3gVwK?3``Rk[/z2^Ai9F<&Nzyg$C9$G7M7o4|N}@p2WBn' );
define( 'NONCE_KEY',        '!D+QkMa{JIP(*6H}D|U>:>#/R]C%K6UXkxXm?Xa;C@I?C)w3 BywQmMaFt@R/c=P' );
define( 'AUTH_SALT',        ';;!a(Cyd!u 7>I|3FDA{L{Ma9}aGsPmbI{u>nslU05R-XqP!/gL7+%MOh54;%okW' );
define( 'SECURE_AUTH_SALT', ']|;(E}8MSf6=)q=?&Bp_nRV~S0?KvIoNWl.B<M8?6,ANcSf+OP!`U|xpIgUR^L7o' );
define( 'LOGGED_IN_SALT',   '^B-5A@Sj9x 6%rjgYs&mJO:TpB`^i4vOjgg;Gp{Vmb*5=[V7G{BMbt%}#uESAhnt' );
define( 'NONCE_SALT',       'FLWB@n>L5zEIY*+~cGxvRCB1{]-_tR^DjzeH}/zG4MY(TbI`Yt%.SeDBR)kiPX^K' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
