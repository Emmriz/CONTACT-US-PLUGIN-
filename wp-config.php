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
define( 'DB_NAME', 'contact_form' );

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
define( 'AUTH_KEY',         'f`n/08oP%uK,a@^03.gVoo*GLv9MXQ;)Jn_@=}!]^wweXsUa?<Pj$ivS-h$!6Or&' );
define( 'SECURE_AUTH_KEY',  'X~.I{rNez)PiQv#d0*.i>=B:Z&5~~ESlMF-D^|5=&hRSsj^I!B#)9M``4N&y 4]p' );
define( 'LOGGED_IN_KEY',    'D#{4*>Ijs;yosr^=#1/gle%wcmE~M$Jw|N$o#)[[lA6DZ@;JA$rP.>k*;6@USy;W' );
define( 'NONCE_KEY',        '%YI)wnb[y6M8]}=Y#VXo*|P6fsH~_SENEJX$jC.)Z)/Z3g>Ttm~&81jl/VApb-}P' );
define( 'AUTH_SALT',        '58Qp0*`1;pUnH<3O3Wlzw_TQ$?7|G~i4k pJ=-Yu$$Wvis$q{~x?f.8h!IePm?#d' );
define( 'SECURE_AUTH_SALT', 'hA P+1#5umn?nO>l @Dwzov7uX /oVI*?Vuw,4AWbOl_dpsz GAO_@17)gL}Z|-o' );
define( 'LOGGED_IN_SALT',   'oDJ]s2tL)gPXg2*T >BynSuR_6>$xCOC1+Z)lOKozD{9@UJ0G@|z`L8Cs~bCaLpz' );
define( 'NONCE_SALT',       '~XB$LLPr7(e!JYVWgfd)[D>M<@gG?fi$N8emjlr&(2]bHdMD{)}7}w{-07o:iS9Z' );

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
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', true );

/* Add any custom values between this line and the "stop editing" line. */


define('WP_MEMORY_LIMIT', '256M');

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
