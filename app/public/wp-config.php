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

// Optimización de memoria
define('WP_MEMORY_LIMIT', '512M');
define('WP_MAX_MEMORY_LIMIT', '1024M');

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'local');

/** Database username */
define('DB_USER', 'root');

/** Database password */
define('DB_PASSWORD', 'root');

/** Database hostname */
define('DB_HOST', 'localhost:/Users/joanraez/Library/Application Support/Local/run/i21rxr0eF/mysql/mysqld.sock');

/** Database charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The database collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

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
define('AUTH_KEY', 'E|?+9,?/yp2{:fs.<QbWR@Oel`HX.1Z>z]}s!tNFw)di&x 8[mo}4v=6Vw01[Oy,');
define('SECURE_AUTH_KEY', 'a*R=?c82kV7tENtQwJFf:f,%v@OD=eNK90.3_WlqtTiR760qJz8He2|pmV#Lp9eT');
define('LOGGED_IN_KEY', 'DS1|r.eH]~K.;&)H{<wu6M{@gxq8Cu*k!V9R/|LiE5~}]j]-).&ip0hjSTKuh Mj');
define('NONCE_KEY', '}y_cw6g]p]pN3~HWm>IAo2w67tm05^88sI;TAKkR:32{uA|(;*O:{)?q&%gyas2c');
define('AUTH_SALT', 'JwYRhAO03{]`mr#_ph$N!$iD};b%R15JYy:U-8nK_4?Sv^iao6T}bPw_$nAZ.C%[');
define('SECURE_AUTH_SALT', 'c5(jY)1GsnR:D~fcb%CRH1SR:&.5y:ir)1pF<*?u EmTL@L.=^hbb~KvrZ!hW31,');
define('LOGGED_IN_SALT', '/@GkD95Opki~NO<wZAGTS^6[pC~X(0My=(:^+ODW$dSYCC8@F8-~;_wE+f|#!)`5');
define('NONCE_SALT', '/1<nW|>0sS&1H#P{A`@2[W&-Kiw#&V]$!YF1rX%mN55+f]e3&).2JC}Ng9CXV6u4');
define('WP_CACHE_KEY_SALT', '{49#,X,frBv :h`{< tjFjYG0J.xe~9jfc[$?7+2d4.C=BRD4S)U:sbKkY6S%*hr');


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
// Enable WP_DEBUG mode
define('WP_DEBUG', true);

// Enable Debug logging to /wp-content/debug.log
define('WP_DEBUG_LOG', true);

// Disable display of errors and warnings (optional, but often good for local dev if you want to see them in log only, though user might want them on screen)
define('WP_DEBUG_DISPLAY', true);
@ini_set('display_errors', 1);

// Keep minified core/theme/plugin assets to avoid dev-only admin warnings/noisy missing files.
define('SCRIPT_DEBUG', false);

define('WP_ENVIRONMENT_TYPE', 'local');
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if (!defined('ABSPATH')) {
	define('ABSPATH', __DIR__ . '/');
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
