<?php
// ===================================================
// Load database info and local development parameters
// ===================================================

if ( file_exists( dirname( __FILE__ ) . '/wp-config-local.php' ) ) {

    // Local Environment
    define('WP_ENV', 'local');
    define('WP_DEBUG', true);

    require( 'wp-config-local.php' );

} elseif ( file_exists( dirname( __FILE__ ) . '/wp-config-staging.php' ) ) {

    // Playground Environment
    define('WP_ENV', 'staging');
    define('WP_DEBUG', true);

    require( 'wp-config-staging.php' );

} elseif ( file_exists( dirname( __FILE__ ) . '/wp-config-production.php' ) ) {

    // Production Environment
    define('WP_ENV', 'production');
    define('WP_DEBUG', false);

    require( 'wp-config-production.php' );
}


// ========================
// Custom Content Directory
// ========================
define( 'WP_CONTENT_DIR', dirname( __FILE__ ) . '/staging_content' );
define( 'WP_CONTENT_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/staging_content' );

// ===============================
// Switch SITEURL & HOME Constants
// ===============================

define( 'RELOCATE', true);

// ================================================
// You almost certainly do not want to change these
// ================================================
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

// ==============================================================
// Salts, for security
// Grab these from: https://api.wordpress.org/secret-key/1.1/salt
// ==============================================================
define('AUTH_KEY',         'o)b-V/`JwfFXkS)_uAFBY|_6IQY$V$S oON`p_2RZGJzz~&Zn9WEfreq4U$[yf{[');
define('SECURE_AUTH_KEY',  ';GRDG7DV~z#ImE-}F))oZBXCmS&aLK928?^H<b$ehB)Pz%.&bSxU 9AfhXGg/:pp');
define('LOGGED_IN_KEY',    'nmuXBAE80&|Dr1Y0??<|<r Bu-L(Qub(G{W%g{E|C%Xq1fcO^cB*w]tL9w/^)GY%');
define('NONCE_KEY',        '>^qt$xUM%UYT2?e@JPQw[WEyL+BZua.7+Why!S|9YPx/1#PE=!qmOio [2miQzZ?');
define('AUTH_SALT',        'x_q&moG_4WpsQz|fY-S#|V$64]u5UaU}yt!t|^Mt0(eCiRx7XruL#H$H{-0-/;vd');
define('SECURE_AUTH_SALT', '|H0<T+S>cWD668P|jzEw1|T{+xeZ/IL[I?}fsf/23?8$i2,b9Ti3d!:ivv`Bb03z');
define('LOGGED_IN_SALT',   'V%P+W@x+7Ga6Q70p/jes_j|s151N#ipqSy|S+f5.T1C(l4RO&Vm3CU e11+k)XW_');
define('NONCE_SALT',       '8F45[j3x,j,Oe&vLKMS_Wl1u4,?r8!ox>.[jnv|A!d^ ~ [Mz|Q|zw||:Ums)nZM');

// ==============================================================
// Table prefix
// Change this if you have multiple installs in the same database
// ==============================================================
$table_prefix  = 'wp_';

// ================================
// Language
// Leave blank for American English
// ================================
define( 'WPLANG', '' );

// ===========
// Hide errors
// ===========
ini_set( 'display_errors', 0 );
define( 'WP_DEBUG_DISPLAY', false );

// =================================================================
// Debug mode
// Debugging? Enable these. Can also enable them in local-config.php
// =================================================================
// define( 'SAVEQUERIES', true );
// define( 'WP_DEBUG', true );

// ======================================
// Load a Memcached config if we have one
// ======================================
if ( file_exists( dirname( __FILE__ ) . '/memcached.php' ) )
	$memcached_servers = include( dirname( __FILE__ ) . '/memcached.php' );

// ===========================================================================================
// This can be used to programatically set the stage when deploying (e.g. production, staging)
// ===========================================================================================
define( 'WP_STAGE', '%%WP_STAGE%%' );
define( 'STAGING_DOMAIN', '%%WP_STAGING_DOMAIN%%' ); // Does magic in WP Stack to handle staging domain rewriting

// ===================
// Bootstrap WordPress
// ===================
if ( !defined( 'ABSPATH' ) )
	define( 'ABSPATH', dirname( __FILE__ ) . '/wp/' );
require_once( ABSPATH . 'wp-settings.php' );


//==================================
//Bypass FTP connection credentials:
//==================================
define('FS_METHOD','direct');
