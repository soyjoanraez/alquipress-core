<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          ']@]onb;L}?JC[[,cj[~DG^7xtY^+n4m6uyr.$oKH+gf*Yj`Z>zf88q8%SoaI+Sl#' );
define( 'SECURE_AUTH_KEY',   'p49e4Qsm!!L5l:^1mV^d9q6C./] b?Ll<8e,Ga+&IeT;1y1gNvJerKo?Z0I)H>qb' );
define( 'LOGGED_IN_KEY',     ' $$hWepmDZNAO?b;&@D0>hXceQT*r2[`2d{-_PlyX.h!e6 naj9[w|IDuo|eBF3^' );
define( 'NONCE_KEY',         '%hK$o~_iK:1#lh)V|1VRZp*%sg)bBrg%e.?.hqA(Ta06,SYpfT+sous0C^fE%9y!' );
define( 'AUTH_SALT',         '|DM9KjOEQvTi/?40!88B=wm 7D?7xOY}t?$~DEOpI$r/+KH==>Q`u,)f-T]7^nXf' );
define( 'SECURE_AUTH_SALT',  '$gIS;I{:gWoZ/k>+e_@?!;G9 b &8g /^<MD]j~|S!$s?L0H;R|FC/wBD2p^?i64' );
define( 'LOGGED_IN_SALT',    '7bO?WIJ<zC|MOUSJUt,VPXeipZEXk:F8dsi2Olv<AEa&9I+*34N`OHSb:P[70rf6' );
define( 'NONCE_SALT',        'kjxYc;//iVHi3DYpa^O8.zw_^6Az}TkC}O79m4qFpOQ:,EG`uVSR4we,J4WK>(QE' );
define( 'WP_CACHE_KEY_SALT', '0T  8LR!^x>1_{;<ehaCK^0?l^aOMq,p4URr83w}%SwobWHA$ _>?JZ1AZVekqz@' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
