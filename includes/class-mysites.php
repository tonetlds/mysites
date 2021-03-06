<?php
use \Firebase\FirebaseLib as Firebase;
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://lucianotonet.com
 * @since      1.0.0
 *
 * @package    Mysites
 * @subpackage Mysites/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Mysites
 * @subpackage Mysites/includes
 * @author     Luciano Tonet <contato@lucianotonet.com>
 */
class Mysites {

	protected $mysites;
	private   $firebase;
	protected $firebase_url;
	protected $firebase_token;
	protected $firebase_sites_path;
	protected $firebase_users_path;

	protected $site_path;
	protected $user_path;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Mysites_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'mysites';
		$this->version = '1.0.0';

		$this->firebase_url 		= 'https://mysites.firebaseio.com/';
	    $this->firebase_token 		= '9TqzyKp9uAhdEGcSax1NDaMdr0XORfhfDYn6OnC8';	    
	    $this->firebase_sites_path 	= 'sites/';	
	    $this->firebase_users_path  = 'users/';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();		

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Mysites_Loader. Orchestrates the hooks of the plugin.
	 * - Mysites_i18n. Defines internationalization functionality.
	 * - Mysites_Admin. Defines all hooks for the admin area.
	 * - Mysites_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-mysites-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-mysites-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-mysites-admin.php';		

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-mysites-public.php';		

		$this->loader = new Mysites_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Mysites_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Mysites_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Mysites_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// $this->loader->add_action( 'admin_menu', $plugin_admin, 'mysites_add_options_page' );
		//$this->loader->add_action( 'admin_init', $plugin_admin, 'mysites_settings_init' );		
		
		$this->loader->add_action( 'admin_bar_menu', $this, 'mysites_build_menu',  100 );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {		

		$plugin_public = new Mysites_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$this->loader->add_action( 'init', $this, 'setup_firebase' );

	}

	public function setup_firebase(){			
		$this->firebase  = new Firebase( $this->firebase_url, $this->firebase_token );		

		$this->site_path = $this->get_site_path();
	    $this->user_path = $this->get_user_path();
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Mysites_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}	

	public function get_mysites() {
		
		$this->set_site();
		$this->set_user();

		$sites  = $this->firebase->get( $this->user_path . '/sites'  );	
		$sites  = json_decode( $sites );		

		$mysites = array();

		foreach ($sites as $sitehash => $value) {
			$mysite = $this->firebase->get( $this->firebase_sites_path . $sitehash );		
			$mysites[] = json_decode( $mysite );		
		}			

		return $mysites; 

	}

	public function set_site(){
				
		$dateTime   = new DateTime();		
		$mysite 	= array(				
					    "url" 			=> get_bloginfo( 'url' ),
					    "title" 		=> get_bloginfo( 'name' ),
					    "description" 	=> get_bloginfo( 'description' ),
						"updated_at"    => $dateTime->format('d-m-Y H:i:s')											
					);		
				
		$this->firebase->update( $this->get_site_path(), $mysite );		

	}

	public function set_user(){

		global $current_user;
		get_currentuserinfo();
		$userhash = rtrim(strtr(base64_encode( $current_user->user_email ), '+/', '-_'), '=');

		$dateTime = new DateTime();			
		$sitehash = rtrim(strtr(base64_encode( get_bloginfo( 'url' ) ), '+/', '-_'), '=');		
		$userdata 	= array(	
						'email' 	    => $current_user->user_email,
						'userlevel'     => $current_user->user_level,
						'updated_at'    => $dateTime->format('d-m-Y H:i:s'),						
					);
				
		$this->firebase->update( $this->get_user_path(), $userdata );
		$this->firebase->update( $this->get_user_path().'/sites', array( $sitehash => true ) );

	}

	public function mysites_build_menu( $admin_bar ){
		
		$mysites = $this->get_mysites();
		
		$protocol 	 = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$current_url = $protocol . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];		

		if( isset($mysites) and !empty($mysites) ){

			foreach ($mysites as $site) {
				$args = array(
		            'id'    => sanitize_file_name( strtolower( $site->title ) ),
		            'title' => $site->title,
		            'href'  => str_replace( get_bloginfo('url'), $site->url, $current_url),
		            'parent' => 'my-account',
		            'meta'  => array(
		                    'title' => __( $site->url ),
		                    ),
		            );		
		    	$admin_bar->add_menu( $args );
			}

		}else{
			$args = array(
	            'id'    => '',
	            'title' => 'No sites...',
	            'href'  => '#',
	            'parent' => 'my-account'	            
	            );		
	    	$admin_bar->add_menu( $args );
		}
		
	}

	public function get_user_path(){
		global $current_user;
		get_currentuserinfo();
		$userhash = rtrim(strtr(base64_encode( $current_user->user_email ), '+/', '-_'), '=');			
		$user_path = $this->firebase_users_path . $userhash;
		
		$thisuser = $this->firebase->get( $user_path );
		if( !$thisuser ){
			$this->firebase->set( $this->firebase_users_path, $userhash );   // push data to Firebase			
		}

		return $user_path;
	}


	public function get_site_path(){

		$sitehash  = rtrim(strtr(base64_encode( get_bloginfo( 'url' ) ), '+/', '-_'), '=');
		$site_path = $this->firebase_sites_path . $sitehash;

		$thissite = $this->firebase->get( $site_path );
		if( !$thissite ){
			$this->firebase->set( $this->firebase_sites_path, $sitehash );   // push data to Firebase			
		}
		return $site_path;	

	}

}
