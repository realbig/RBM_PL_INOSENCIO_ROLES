<?php
/**
 * Plugin Name: Inosencio User Roles
 * Description: Adds additional User Roles for Inosencio
 * Version: 1.0.0
 * Text Domain: inosencio-roles
 * Author: Eric Defore
 * Author URI: http://realbigmarketing.com/
 * Contributors: d4mation
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Inosencio_Roles' ) ) {

	/**
	 * Main Inosencio_Roles class
	 *
	 * @since	  1.0.0
	 */
	class Inosencio_Roles {
		
		/**
		 * @var			Inosencio_Roles $plugin_data Holds Plugin Header Info
		 * @since		1.0.0
		 */
		public $plugin_data;
		
		/**
		 * @var			Inosencio_Roles $admin_errors Stores all our Admin Errors to fire at once
		 * @since		1.0.0
		 */
		private $admin_errors;
		
		/**
		 * @var			Inosencio_Roles $roles the new Roles
		 * @since		1.0.0
		 */
		public $roles = array();
		
		/**
		 * @var			Inosencio_Roles $current_role The current user's role
		 * @since		1.0.0
		 */
		public $current_role = false;

		/**
		 * Get active instance
		 *
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  object self::$instance The one true Inosencio_Roles
		 */
		public static function instance() {
			
			static $instance = null;
			
			if ( null === $instance ) {
				$instance = new static();
			}
			
			return $instance;

		}
		
		protected function __construct() {
			
			$this->setup_constants();
			$this->load_textdomain();
			
			if ( version_compare( get_bloginfo( 'version' ), '4.4' ) < 0 ) {
				
				$this->admin_errors[] = sprintf( _x( '%s requires v%s of %s or higher to be installed!', 'Outdated Dependency Error', 'inosencio-roles' ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '4.4', '<a href="' . admin_url( 'update-core.php' ) . '"><strong>WordPress</strong></a>' );
				
				if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
					add_action( 'admin_notices', array( $this, 'admin_errors' ) );
				}
				
				return false;
				
			}
			
			$this->require_necessities();
			
			// Register our CSS/JS for the whole plugin
			add_action( 'init', array( $this, 'register_scripts' ) );
			
			// Add our Roles
			add_action( 'init', array( $this, 'add_roles' ) );
			
			// Store the current Role in our Object
			add_action( 'init', array( $this, 'get_current_role' ) );
			
			// Removes added Roles on Plugin Deactivation
			register_deactivation_hook( __FILE__, array( $this, 'remove_roles' ) );
			
		}

		/**
		 * Setup plugin constants
		 *
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function setup_constants() {
			
			// WP Loads things so weird. I really want this function.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			
			// Only call this once, accessible always
			$this->plugin_data = get_plugin_data( __FILE__ );

			if ( ! defined( 'Inosencio_Roles_VER' ) ) {
				// Plugin version
				define( 'Inosencio_Roles_VER', $this->plugin_data['Version'] );
			}

			if ( ! defined( 'Inosencio_Roles_DIR' ) ) {
				// Plugin path
				define( 'Inosencio_Roles_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'Inosencio_Roles_URL' ) ) {
				// Plugin URL
				define( 'Inosencio_Roles_URL', plugin_dir_url( __FILE__ ) );
			}
			
			if ( ! defined( 'Inosencio_Roles_FILE' ) ) {
				// Plugin File
				define( 'Inosencio_Roles_FILE', __FILE__ );
			}

		}

		/**
		 * Internationalization
		 *
		 * @access	  private 
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function load_textdomain() {

			// Set filter for language directory
			$lang_dir = Inosencio_Roles_DIR . '/languages/';
			$lang_dir = apply_filters( 'inosencio_roles_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'inosencio-roles' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'inosencio-roles', $locale );

			// Setup paths to current locale file
			$mofile_local   = $lang_dir . $mofile;
			$mofile_global  = WP_LANG_DIR . '/inosencio-roles/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/inosencio-roles/ folder
				// This way translations can be overridden via the Theme/Child Theme
				load_textdomain( 'inosencio-roles', $mofile_global );
			}
			else if ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/inosencio-roles/languages/ folder
				load_textdomain( 'inosencio-roles', $mofile_local );
			}
			else {
				// Load the default language files
				load_plugin_textdomain( 'inosencio-roles', false, $lang_dir );
			}

		}
		
		/**
		 * Include different aspects of the Plugin
		 * 
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function require_necessities() {
			
			// Base Class
			require_once __DIR__ . '/core/class-inosencio-user-role.php';
			
			if ( function_exists( 'inosencio_is_live_site' ) && 
				inosencio_is_live_site() ) {
				
				// Force Approval Workflow for certain Roles and CPTs
				require_once __DIR__ . '/core/class-inosencio-roles-approval.php';
			
				new Inosencio_User_Roles_Approval( 
					array(
					),
					array(
					)
				);
				
			}
			
		}
		
		/**
		 * Gets the current user's role.
		 * 
		 * @access		private
		 * @since		1.0.0
		 * @return		void
		 */
		public function get_current_role() {
			
			if ( is_user_logged_in() ) {
				$current_user       = wp_get_current_user();
				$roles              = $current_user->roles;
				$this->current_role = array_shift( $roles );
			}

			// Staging for some reason always had NULL as the Role. This fixes it.
			// My Local environment worked just fine though, so maybe in most cases this won't be needed
			if ( $this->current_role === NULL ) {

				global $user_ID;

				$user_data = get_userdata( $user_ID );
				$user_role = array_shift( $user_data->roles );
				$this->current_role = $user_role;

			}

		}
		
		public function add_roles() {
			
			$this->roles['site_manager'] = new Inosencio_User_Role(
				'site_manager',
				__( 'Site Manager', 'inosencio-roles' ),
				array(
					'read',
					'edit_theme_options',
				),
				array(
					'base_role' => 'editor',
				)
			);
			
		}
		
		/**
		 * Show admin errors.
		 * 
		 * @access	  public
		 * @since	  1.0.0
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
		 * Register our CSS/JS to use later
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  void
		 */
		public function register_scripts() {
			
			wp_register_style(
				'inosencio-roles',
				Inosencio_Roles_URL . 'assets/css/style.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Inosencio_Roles_VER
			);
			
			wp_register_script(
				'inosencio-roles',
				Inosencio_Roles_URL . 'assets/js/script.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Inosencio_Roles_VER,
				true
			);
			
			wp_localize_script( 
				'inosencio-roles',
				'inosencioRoles',
				apply_filters( 'inosencio_roles_localize_script', array() )
			);
			
			wp_register_style(
				'inosencio-roles-admin',
				Inosencio_Roles_URL . 'assets/css/admin.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Inosencio_Roles_VER
			);
			
			wp_register_script(
				'inosencio-roles-admin',
				Inosencio_Roles_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Inosencio_Roles_VER,
				true
			);
			
			wp_localize_script( 
				'inosencio-roles-admin',
				'inosencioRoles',
				apply_filters( 'inosencio_roles_localize_admin_script', array() )
			);
			
		}
		
		public function remove_roles() {
			
			remove_role( 'site_manager' );

		}
		
	}
	
} // End Class Exists Check

/**
 * The main function responsible for returning the one true Inosencio_Roles
 * instance to functions everywhere
 *
 * @since	  1.0.0
 * @return	  \Inosencio_Roles The one true Inosencio_Roles
 */
add_action( 'after_setup_theme', 'inosencio_roles_load' );
function inosencio_roles_load() {

	require_once __DIR__ . '/core/inosencio-roles-functions.php';
	INOSENCIOROLES();

}