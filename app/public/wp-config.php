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
define( 'AUTH_KEY',          'tuqx(E<gNdh!fVfb%^(eMa$:vja0n-+Eg&)%4/L6#2u~,4=eHSQpPd1[-j{0=Wl[' );
define( 'SECURE_AUTH_KEY',   'c1cZvTgc%gY>o-)4)xq4VN1wq7ac&=HW1mc.l/!gBH7K8m|5!+7w{@I9[z[.3)h&' );
define( 'LOGGED_IN_KEY',     'kS&Dl+DnA0lS4Kw3|fMrx8~isL`(I;5U`$ J<&1 RwB*gpL%-93a,fZ}v^/o^%3o' );
define( 'NONCE_KEY',         'dD2m_JfrKKSB!-BQ5X9=o)cC&S]z?tsh1(-u%/@Q,EG{em%:},y<1O_19^e$(ZF|' );
define( 'AUTH_SALT',         'u8]T~MAfs{4FG56r$3@nUO)(rst4x&j^[g_jfi+>@s0@y3- Yry6K8>3JujYRk^Y' );
define( 'SECURE_AUTH_SALT',  '|N}]J#zjS5y?l]r#mqh Fna&wW:2@,J6HJra#ZFX<O&&Ppn8tMF`cK8hLL;=NC#s' );
define( 'LOGGED_IN_SALT',    'I_XhP?QF9H|J4c/TTV-?nECL9loV7i;r8o_,/eEMP%P_}}mDCm4W)q/rst]6t!dN' );
define( 'NONCE_SALT',        'J`rn@%}CGDjH,l}Ndr)iO;4Zpo/%S`TbF/mGNgur>)?n)?BggGNU%Z1|_K/_4fwq' );
define( 'WP_CACHE_KEY_SALT', 'sqPJ)gn~|oH_xeND{S,_a4mNN QPJ|ob|6ivTgARogmR)z`rB3Ht.B+~Tv2B8YA&' );


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
