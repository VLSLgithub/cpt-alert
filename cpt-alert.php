<?php
/**
 * Plugin Name: CPT Alert
 * Description: Creates the "Alert" CPT.
 * Author: Real Big Marketing
 * Author URI: https://realbigmarketing.com/
 * Version: 0.1.0
 * Text Domain: vl-cpt-alert
 * GitHub Plugin URI: VLSLgithub/cpt-alert
 * GitHub Branch: master
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Class CPTAlert
 *
 * Initiates the plugin.
 *
 * @since {{VERSION}}
 *
 * @package CPTAlert
 */
final class CPTAlert {

	public $cpt;
	public $ajax;

	/**
	 * @var			array $plugin_data Holds Plugin Header Info
	 * @since		{{VERSION}}
	 */
	public $plugin_data;
	
	/**
	 * @var			array $admin_errors Stores all our Admin Errors to fire at once
	 * @since		{{VERSION}}
	 */
	private $admin_errors;

	private function __clone() { }

	private function __wakeup() { }

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @since {{VERSION}}
	 *
	 * @staticvar Singleton $instance The *Singleton* instances of this class.
	 *
	 * @return CPTAlert The *Singleton* instance.
	 */
	public static function getInstance() {

		static $instance = null;

		if ( null === $instance ) {
			$instance = new static();
		}

		return $instance;
	}

	/**
	 * Initializes the plugin.
	 *
	 * @since {{VERSION}}
	 */
	protected function __construct() {

		$this->setup_constants();
		$this->load_textdomain();

		if ( version_compare( get_bloginfo( 'version' ), '4.4' ) < 0 ) {
				
			$this->admin_errors[] = sprintf( _x( '%s requires v%s of %sWordPress%s or higher to be installed!', 'First string is the plugin name, followed by the required WordPress version and then the anchor tag for a link to the Update screen.', 'vl-cpt-alert' ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '4.4', '<a href="' . admin_url( 'update-core.php' ) . '"><strong>', '</strong></a>' );
			
			if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
				add_action( 'admin_notices', array( $this, 'admin_errors' ) );
			}
			
			return false;
			
		}

		if ( ! class_exists( 'RBM_CPTS' ) ||
			! class_exists( 'RBM_FieldHelpers' ) ) {
			
			$this->admin_errors[] = sprintf( _x( 'To use the %s Plugin, both %s and %s must be active as either a Plugin or a Must Use Plugin!', 'Missing Dependency Error', 'vl-cpt-alert' ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '<a href="//github.com/realbig/rbm-field-helpers-wrapper/" target="_blank">' . __( 'RBM Field Helpers', 'vl-cpt-alert' ) . '</a>', '<a href="//github.com/realbig/rbm-cpts/" target="_blank">' . __( 'RBM Custom Post Types', 'vl-cpt-alert' ) . '</a>' );
			
			if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
				add_action( 'admin_notices', array( $this, 'admin_errors' ) );
			}
			
			return false;
			
		}

		$this->add_base_actions();
		$this->require_necessities();

	}

	/**
	 * Setup plugin constants
	 *
	 * @access	  private
	 * @since	  {{VERSION}}
	 * @return	  void
	 */
	private function setup_constants() {
		
		// WP Loads things so weird. I really want this function.
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}
		
		// Only call this once, accessible always
		$this->plugin_data = get_plugin_data( __FILE__ );

		if ( ! defined( 'VL_CPT_Alert_Plugin_VER' ) ) {
			// Plugin version
			define( 'VL_CPT_Alert_Plugin_VER', $this->plugin_data['Version'] );
		}

		if ( ! defined( 'VL_CPT_Alert_Plugin_DIR' ) ) {
			// Plugin path
			define( 'VL_CPT_Alert_Plugin_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
		}

		if ( ! defined( 'VL_CPT_Alert_Plugin_URL' ) ) {
			// Plugin URL
			define( 'VL_CPT_Alert_Plugin_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
		}
		
		if ( ! defined( 'VL_CPT_Alert_Plugin_FILE' ) ) {
			// Plugin File
			define( 'VL_CPT_Alert_Plugin_FILE', __FILE__ );
		}

	}

	/**
	 * Internationalization
	 *
	 * @access	  private 
	 * @since	  {{VERSION}}
	 * @return	  void
	 */
	private function load_textdomain() {

		// Set filter for language directory
		$lang_dir = VL_CPT_Alert_Plugin_DIR . '/languages/';
		$lang_dir = apply_filters( 'vl_cpt_alert_plugin_languages_directory', $lang_dir );

		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale', get_locale(), 'vl-cpt-alert' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'vl-cpt-alert', $locale );

		// Setup paths to current locale file
		$mofile_local   = $lang_dir . $mofile;
		$mofile_global  = WP_LANG_DIR . '/vl-cpt-alert/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/vl-cpt-alert/ folder
			// This way translations can be overridden via the Theme/Child Theme
			load_textdomain( 'vl-cpt-alert', $mofile_global );
		}
		else if ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/vl-cpt-alert/languages/ folder
			load_textdomain( 'vl-cpt-alert', $mofile_local );
		}
		else {
			// Load the default language files
			load_plugin_textdomain( 'vl-cpt-alert', false, $lang_dir );
		}

	}

	/**
	 * Requires necessary base files.
	 *
	 * @since {{VERSION}}
	 */
	public function require_necessities() {

		// CPT functionality
		require_once __DIR__ . '/core/class-cpt-alert-cpt.php';
		$this->cpt = new CPTAlert_CPT();

		// AJAX
		require_once __DIR__ . '/core/class-vl-alert-ajax.php';
		$this->ajax = new VL_Alert_AJAX();
	}

	/**
	 * Adds global, base functionality actions.
	 *
	 * @since {{VERSION}}
	 */
	private function add_base_actions() {

		add_action( 'init', array( $this, '_register_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, '_enqueue_assets' ) );
	}

	/**
	 * Show admin errors.
	 * 
	 * @access	  public
	 * @since	  {{VERSION}}
	 * @return	  HTML
	 */
	public function admin_errors() {
		?>
		<div class="error">
			<?php foreach ( $this->admin_errors as $notice ) : ?>
				<p>
					<?php echo $notice; ?>
				</p>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Registers the plugin's assets.
	 *
	 * @since {{VERSION}}
	 */
	function _register_assets() {

		wp_register_script(
			'vl-alerts',
			VL_CPT_Alert_Plugin_URL . 'assets/dist/js/script.js',
			array( 'jquery' ),
			defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : VL_CPT_Alert_Plugin_VER,
			true
		);

		wp_localize_script( 'vl-alerts', 'VL_Alerts', array(
			'ajaxurl' => rest_url( 'vibrant-life/v1/alerts/' ),
            'closeButton' => __( 'Close alert', 'vl-cpt-alert' ),
		));
	}

	/**
	 * Enqueues the plugin's assets.
	 *
	 * @since {{VERSION}}
	 */
	function _enqueue_assets() {

		wp_enqueue_script( 'vl-alerts' );
	}
}

/**
 * The main function responsible for returning the one true CPTAlert
 * instance to functions everywhere
 *
 * @since	  {{VERSION}}
 * @return	  void
 */
add_action( 'plugins_loaded', 'vl_cpt_alert_plugin_load' );
function vl_cpt_alert_plugin_load() {

	require_once __DIR__ . '/core/cpt-alert-functions.php';
	CPTAlert();

}