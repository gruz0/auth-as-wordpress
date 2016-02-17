<?php
defined( 'ABSPATH' ) or exit;

require_once( dirname( __FILE__ ) . "/functions.php" );

/**
 * @since 0.1
 */
class Auth_As {
	/**
	 * Constructor
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( & $this, 'localization' ) );
		add_action( 'admin_init', array( & $this, 'admin_init' ) );
		add_action( 'admin_menu', array( & $this, 'add_menu' ) );
		add_action( 'login_form', array( & $this, 'login_form' ) );

		add_action( 'show_user_profile', array( & $this, 'add_auth_as_checkbox' ) );
		add_action( 'edit_user_profile', array( & $this, 'add_auth_as_checkbox' ) );
		add_action( 'personal_options_update', array( & $this, 'save_user_profile' ) );
		add_action( 'edit_user_profile_update', array( & $this, 'save_user_profile' ) );

		add_filter( 'authenticate', array( & $this, 'authenticate' ), 10, 3 );
	}

	/**
	 * Activate plugin
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public static function activate() {
		if ( ! current_user_can( 'activate_plugins' ) ) return;

		self::upgrade();
	}

	/**
	 * Deactivate plugin
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public static function deactivate() {
		if ( ! current_user_can( 'activate_plugins' ) ) return;
	}

	/**
	 * Uninstall plugin
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public static function uninstall() {
		if ( ! current_user_can( 'activate_plugins' ) ) return;
		if ( ! get_option( AUTH_AS_PREFIX . 'setting_remove_settings_on_uninstall' ) ) return;

		$options = array(
			// Очищаем версию плагина
			'version',

			// Общие настройки
			'setting_active',
			'setting_api_url',
			'setting_api_key',

			// Прочие настройки
			'setting_remove_settings_on_uninstall',
		);

		for ( $idx = 0; $idx < count( $options ); $idx++ ) {
			delete_option( AUTH_AS_PREFIX . $options[ $idx ] );
		}

		// Удаляем мета-данные пользователей
		$user_query = new WP_User_Query( array( 'role' => 'Administrator', 'search_columns' => array( 'id' ) ) );

		if ( ! empty( $user_query->results ) ) {
			foreach ( $user_query->results as $user ) {
				delete_user_meta( $user->id, 'use_auth_as' );
			}
		}
	}

	/**
	 * Upgrade plugin
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public static function upgrade() {
		$version = AUTH_AS_PREFIX . 'version';

		// Срабатывает только при инсталляции плагина
		if ( ! get_option( $version ) ) update_option( $version, '0.0' );

		if ( '0.1' > get_option( $version ) ) {
			update_option( AUTH_AS_PREFIX . 'setting_remove_settings_on_uninstall',  0 );
			update_option( AUTH_AS_PREFIX . 'setting_active',  0 );
			update_option( AUTH_AS_PREFIX . 'setting_api_url', 'https://console.auth.as/api/v1.0/check_code' );
			update_option( AUTH_AS_PREFIX . 'setting_api_key',  '' );

			update_option( $version, '0.1' );
		}
	}

	/**
	 * Adds localization
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function localization() {
		load_plugin_textdomain( L10N_AUTH_AS_PREFIX, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Hook into WP's admin_init action
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function admin_init() {
		$this->init_settings_main( 'auth_as' );
	}

	/**
	 * Main settings
	 *
	 * @param string $prefix Settings prefix
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function init_settings_main( $prefix ) {

		// Используется в settings_field и do_settings_field
		$group = $options_page = $section = $prefix;

		// Не забывать добавлять новые опции в uninstall()
		register_setting( $group, AUTH_AS_PREFIX . 'setting_active' );
		register_setting( $group, AUTH_AS_PREFIX . 'setting_remove_settings_on_uninstall', 'absint' );
		register_setting( $group, AUTH_AS_PREFIX . 'setting_api_url', 'sanitize_text_field' );
		register_setting( $group, AUTH_AS_PREFIX . 'setting_api_key', 'sanitize_text_field' );

		add_settings_section(
			$section,
			__( 'Common Settings', L10N_AUTH_AS_PREFIX ),
			array( & $this, 'settings_section_main' ),
			$options_page
		);

		// Активен плагин или нет
		add_settings_field(
			$prefix . '-common-active',
			__( 'Active', L10N_AUTH_AS_PREFIX ),
			array( & $this, 'settings_field_checkbox' ),
			$options_page,
			$section,
			array(
				'field' => AUTH_AS_PREFIX . 'setting_active'
			)
		);

		// API URL
		add_settings_field(
			$prefix . '-common-api-url',
			__( 'API URL', L10N_AUTH_AS_PREFIX ),
			array( & $this, 'settings_field_input_text' ),
			$options_page,
			$section,
			array(
				'field' => AUTH_AS_PREFIX . 'setting_api_url'
			)
		);

		// API Key
		add_settings_field(
			$prefix . '-common-api-key',
			__( 'API Key', L10N_AUTH_AS_PREFIX ),
			array( & $this, 'settings_field_input_text' ),
			$options_page,
			$section,
			array(
				'field' => AUTH_AS_PREFIX . 'setting_api_key'
			)
		);

		// Удалять настройки плагина при деинсталляции
		add_settings_field(
			$prefix . '-common-remove-settings-on-uninstall',
			__( 'Remove settings on uninstall', L10N_AUTH_AS_PREFIX ),
			array( & $this, 'settings_field_checkbox' ),
			$options_page,
			$section,
			array(
				'field' => AUTH_AS_PREFIX . 'setting_remove_settings_on_uninstall'
			)
		);
	}

	/**
	 * Describe main settings section
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function settings_section_main() {}

	/**
	 * Callback-template to show textbox field in settings page
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function settings_field_input_text( $args ) {
		$field = esc_attr( $args[ 'field' ] );
		$value = get_option( $field );
		echo sprintf( '<input type="text" name="%s" id="%s" value="%s" />', $field, $field, $value );
	}

	/**
	 * Callback-template to show checkbox field in settings page
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function settings_field_checkbox( $args ) {
		$field = esc_attr( $args[ 'field' ] );
		$value = get_option( $field );
		echo sprintf( '<input type="checkbox" name="%s" id="%s" value="1" %s />', $field, $field, checked( $value, 1, false ) );
	}

	/**
	 * Adds menu item to main WordPress menu
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function add_menu() {
		add_users_page(
			__( 'Two-Factor Authentication for WordPress', L10N_AUTH_AS_PREFIX ), // Название пункта на его странице
			__( 'Configure auth.as', L10N_AUTH_AS_PREFIX ), // Пункт меню
			'administrator',
			'auth_as',
			array( & $this, 'plugin_settings_page' )
		);
	}

	/**
	 * Render main settings page
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function plugin_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		include( sprintf( "%s/templates/settings.php", dirname( __FILE__ ) ) );
	}

	/**
	 * Render plugin's fields to login form
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function login_form() {
	?>
		<p>
			<label for="auth_as_token"><?php _e( 'One-Time Password' ); ?><br>
			<input type="text" tabindex="20" size="6" value="" class="input" id="auth_as_token" name="auth_as_token"></label>
		</p>
	<?php
	}

	/**
	 * Authentication
	 *
	 * @param [null or WP_User or WP_Error] $user User's object
	 * @param string $username Username (not email)
	 * @param string $password Password
	 *
	 * @since 0.1
	 *
	 * @return [WP_Error or null] If error happens return WP_Errors otherwise return null
	 */
	public function authenticate( $user, $username, $password ) {
		$user = get_user_by( 'login', $username );

		// Пользователь не найден? Выходим с ошибкой.
		if ( ! $user ) {
			remove_action( 'authenticate', 'wp_authenticate_username_password', 20 );
			$user = new WP_Error( 'denied', __( '<strong>ERROR</strong>: Invalid credentials.' ) );

			return false;
		}

		$use_auth_as = intval( get_auth_as_option( 'setting_active' ) );

		// Использование плагина не включено? Идём на обычную проверку логина и пароля.
		if ( ! $use_auth_as )
			return null;

		// Флаг использования ОТП пользователем
		$user_uses_otp = intval( get_user_meta( $user->ID, 'use_auth_as', true ) );

		if ( $user_uses_otp ) {
			$token_code = ! empty( $_POST['auth_as_token'] ) ? trim( $_POST['auth_as_token'] ) : '';

			if ( empty( $token_code ) || ! $this->check_code( $user->user_email, $token_code ) ) {
				remove_action( 'authenticate', 'wp_authenticate_username_password', 20 );
				$user = new WP_Error( 'denied', __( '<strong>ERROR</strong>: Invalid credentials.' ) );
			}
		}

		return null;
	}

	/**
	 * Try to check code in auth.as API
	 *
	 * @param string $email Email
	 * @param string $token_code One-time password from login form
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function check_code( $email, $token_code ) {
		$api_url = get_auth_as_option( 'setting_api_url' );
		$api_key = get_auth_as_option( 'setting_api_key' );

		if ( empty( $api_url ) || empty( $api_key ) ) return false;

		$data = array(
			'api_key' => $api_key,
			'email'   => $email,
			'code'    => $token_code
		);

		$params = http_build_query( $data );

		if( function_exists( 'curl_init' ) ) {
			$curl = curl_init();
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $curl, CURLOPT_HEADER, false );
			curl_setopt( $curl, CURLOPT_URL, $api_url );
			curl_setopt( $curl, CURLOPT_REFERER, get_bloginfo( 'url' ) );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 10 );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $params );

			$result = curl_exec( $curl );

			curl_close( $curl );

			if ( ! $result )
				return false;

			return ( intval( $result ) == 200 );
		}

		return false;
	}

	/**
	 * Render "Use auth.as" checkbox in user profile
	 *
	 * @param WP_User $user User's object
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function add_auth_as_checkbox( $user ) {
	?>
		<h3><?php _e( 'Two-Factor Authentication for WordPress', L10N_AUTH_AS_PREFIX ); ?></h3>

		<table class="form-table">
			<tr>
				<th><label for="use_auth_as"><?php _e( 'Use auth.as', L10N_AUTH_AS_PREFIX ); ?></label></th>
				<td><input type="checkbox" id="use_auth_as" name="use_auth_as" value="1" <?php checked( get_the_author_meta( 'use_auth_as', $user->ID ), 1, true ); ?> /></td>
			</tr>
		</table>
	<?php
	}

	/**
	 * Action to store plugin meta information in user's profile
	 *
	 * @param integer $user_id User ID
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function save_user_profile( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) )
			return FALSE;

		update_user_meta( $user_id, 'use_auth_as', $_POST['use_auth_as'] );
	}
}

