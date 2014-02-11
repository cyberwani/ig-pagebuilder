<?php
/**
 * Main class for third-party
 *
 * Define properties & methods
 *
 * @author		InnoGears Team <support@www.innogears.com>
 * @package		IGPGBLDR
 * @version		$Id$
 */
class Ig_Pb_Third_Party {

	// prodiver name
	protected $provider;
	// register assets (js/css)
	protected $assets_register;
	// enqueue assets for Admin pages
	protected $assets_enqueue_admin;
	// enqueue assets for Modal setting iframe
	protected $assets_enqueue_modal;
	// enqueue assets for Frontend
	protected $assets_enqueue_frontend;

	// GET functions
	public function get_provider(){
		return $this->provider;
	}
	public function get_assets_register(){
		return $this->assets_register;
	}
	public function get_assets_enqueue_admin(){
		return $this->assets_enqueue_admin;
	}
	public function get_assets_enqueue_modal(){
		return $this->assets_enqueue_modal;
	}
	public function get_assets_enqueue_frontend(){
		return $this->assets_enqueue_frontend;
	}

	// SET FUNCTIONS
	/**
     *
     * @param type $provider
     */
	public function set_provider( $provider ){
		$this->provider = $provider;
	}
	/**
	 *
	 * @param array $assets
	 */
	public function set_assets_register( $assets ){
		$this->assets_register = $assets;
	}
	/**
	 *
	 * @param array $assets
	 */
	public function set_assets_enqueue_admin( $assets ){
		$this->assets_enqueue_admin = $assets;
	}
	/**
	 *
	 * @param array $assets
	 */
	public function set_assets_enqueue_modal( $assets ){
		$this->assets_enqueue_modal = $assets;
	}
	/**
	 *
	 * @param array $assets
	 */
	public function set_assets_enqueue_frontend( $assets ){
		$this->assets_enqueue_frontend = $assets;
	}

	// constructor
	public function __construct() {
		add_filter( 'ig_pb_provider', array( &$this, 'this_provider' ) );
		add_filter( 'ig_pb_assets_register', array( &$this, 'this_assets_register' ) );
		add_filter( 'ig_pb_assets_enqueue_admin', array( &$this, 'this_assets_enqueue_admin' ) );
		add_filter( 'ig_pb_assets_enqueue_modal', array( &$this, 'this_assets_enqueue_modal' ) );
		add_filter( 'ig_pb_assets_enqueue_frontend', array( &$this, 'this_assets_enqueue_frontend' ) );
	}

	// filter providers
	public function this_provider( $providers ){
		$provider = $this->get_provider();
		if ( empty ( $provider ) || empty ( $provider['file'] ) ){
			return $providers;
		}
		$file = $provider['file'];
		$path = plugin_dir_path( $file );
		$uri  = plugin_dir_url( $file );
		$shortcode_dir    = empty ( $provider['shortcode_dir'] ) ? 'shortcodes' : $provider['shortcode_dir'];
		$js_shortcode_dir = empty ( $provider['js_shortcode_dir'] ) ? 'assets/js/shortcodes' : $provider['js_shortcode_dir'];

		//get plugin name & main file
		$main_file = pathinfo( $file );
		$folder    = basename( $main_file['dirname'] );
		$main_file = $folder . '/' . $main_file['basename'];
		$providers[$path] = array(
			'path' => $path,
			'uri' => $uri,
			'file' => $main_file,
			'folder' => $folder,
			'name' => $provider['name'],
			'shortcode_dir' => array( $path . $shortcode_dir ),
			'js_shortcode_dir' => array( 'path' => $path . $js_shortcode_dir, 'uri' => $uri . $js_shortcode_dir ),
		);
		return $providers;
	}
	// register assets
	public function this_assets_register( $assets ){
		$this_asset = $this->get_assets_register();
		$assets     = array_merge( $assets, empty ( $this_asset ) ? array() : $this_asset );
		return $assets;
	}
	// assets enqueue for admin
	public function this_assets_enqueue_admin( $assets ){
		$this_asset = $this->get_assets_enqueue_admin();
		$assets     = array_merge( $assets, empty ( $this_asset ) ? array() : $this_asset );
		return $assets;
	}
	// assets enqueue for modal
	public function this_assets_enqueue_modal( $assets ){
		$this_asset = $this->get_assets_enqueue_modal();
		$assets     = array_merge( $assets, empty ( $this_asset ) ? array() : $this_asset );
		return $assets;
	}
	// assets enqueue for frontend
	public function this_assets_enqueue_frontend( $assets ){
		$this_asset = $this->get_assets_enqueue_frontend();
		$assets     = array_merge( $assets, empty ( $this_asset ) ? array() : $this_asset );
		return $assets;
	}

	/**
	 * Register Path to extended Parameter type
	 * @param string $path
	 */
	public function register_extended_parameter_path( $path ) {
		IG_Pb_Loader::register( $path, 'IG_Pb_Helper_Html_' );
	}

}