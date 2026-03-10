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
define( 'AUTH_KEY',          '@wkm*5 ,,5*~M3E!2/[5VWQ8*wn/M&6QTCbcwkvKG%M~H9t[I(3q{ewzoin5E(*0' );
define( 'SECURE_AUTH_KEY',   '=C$NK`P3=mx^BmDwa5_^{4z5^Yr);Fl|Xy~]~Q)bv}C1799ii.MqqOl=?NYt.|{Q' );
define( 'LOGGED_IN_KEY',     '3rPVsHqXgE}Cb<:Jx8UqTXh$86wO|YQ=C{$f?BMqL& <pCJu`[s#wQ%A40%*#F,r' );
define( 'NONCE_KEY',         'Ia9IVu{Z`bLF#VEu+x19Oph}s@}cEJK8jZe iT_Df&=Q.va(E%lqe_y*8TRuvD. ' );
define( 'AUTH_SALT',         'y@M:l>gPU`sSF=BUyg_}8*M*y]XF^b7T&]_>/(?qoKD&{M.8R*W1G!y.VghR3t*(' );
define( 'SECURE_AUTH_SALT',  'pg#yeHLOQ=U7l]pnR rLYaXrxo|h5?P>0J{^h4+Wbrp5-ei{d!Zt;Y>p5N6c  F~' );
define( 'LOGGED_IN_SALT',    'h=L:^%K||-?{GiC`_:*4hd@1^<GYmOq9nB**6+g]hqB5xe&m;#0K4 jKXR66Z8LR' );
define( 'NONCE_SALT',        'Y 3.r!7)M}cTE@- RV:woCBgdi`,eB*[t!m*9]~>)2@{-9V7b@kgDJ+ttH-6]?by' );
define( 'WP_CACHE_KEY_SALT', 'NsJ6f*G,M*C}%)pD45 a+:]d#x+>M5v jJ$9T6X2vaY]FeejLmgs^wenR;.x)UQW' );


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
