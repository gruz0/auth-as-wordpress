<?php
defined( 'ABSPATH' ) or exit;

require_once( dirname( __FILE__ ) . "/functions.php" );

class Auth_As {

	/**
	 * Конструктор
	 */
	public function __construct() {

		// Register action
		add_action( 'init', array( & $this, 'localization' ) );
		add_action( 'admin_init', array( & $this, 'admin_init' ) );
		add_action( 'admin_menu', array( & $this, 'add_menu' ) );
		add_action( 'login_form', array( & $this, 'login_form' ) );
	}

	/**
	 * Активация плагина
	 */
	public static function activate() {
		if ( ! current_user_can( 'activate_plugins' ) ) return;

		self::upgrade();
	}

	/**
	 * Деактивация плагина
	 */
	public static function deactivate() {
		if ( ! current_user_can( 'activate_plugins' ) ) return;
	}

	/**
	 * Деинсталляция плагина
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
		);

		for ( $idx = 0; $idx < count( $options ); $idx++ ) {
			delete_option( AUTH_AS_PREFIX . $options[ $idx ] );
		}
	}

	/**
	 * Обновление плагина
	 */
	public static function upgrade() {
		$version = AUTH_AS_PREFIX . 'version';

		// Срабатывает только при инсталляции плагина
		if ( ! get_option( $version ) ) update_option( $version, '0.0' );

		if ( '0.1' > get_option( $version ) ) {
			update_option( AUTH_AS_PREFIX . 'setting_remove_settings_on_uninstall',  0 );
			update_option( AUTH_AS_PREFIX . 'setting_active',  0 );
			update_option( AUTH_AS_PREFIX . 'setting_api_url', 'https://console.auth.as/api/v1.0/check_code' );
			update_option( AUTH_AS_PREFIX . 'setting_active',  '' );

			update_option( $version, '0.1' );
		}
	}

	/**
	 * Подключаем локализацию к плагину
	 */
	public function localization() {
		load_plugin_textdomain( L10N_AUTH_AS_PREFIX, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Hook into WP's admin_init action hook
	 */
	public function admin_init() {
		$this->init_settings_main( 'auth_as' );
	}

	/**
	 * Общие настройки
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
	 * Описание общих настроек
	 */
	public function settings_section_main() {
		_e( 'Main settings description', L10N_AUTH_AS_PREFIX );
	}

	/**
	 * Callback-шаблон для формирования текстового поля на странице настроек
	 */
	public function settings_field_input_text( $args ) {
		$field = esc_attr( $args[ 'field' ] );
		$value = get_option( $field );
		echo sprintf( '<input type="text" name="%s" id="%s" value="%s" />', $field, $field, $value );
	}

	/**
	 * Callback-шаблон для формирования чекбокса на странице настроек
	 */
	public function settings_field_checkbox( $args ) {
		$field = esc_attr( $args[ 'field' ] );
		$value = get_option( $field );
		echo sprintf( '<input type="checkbox" name="%s" id="%s" value="1" %s />', $field, $field, checked( $value, 1, false ) );
	}

	/**
	 * Добавление пункта меню
	 */
	public function add_menu() {
		add_users_page(
			__( 'Two-Factor Authentication via auth.as', L10N_AUTH_AS_PREFIX ), // Название пункта на его странице
			__( 'Auth.AS Options', L10N_AUTH_AS_PREFIX ), // Пункт меню
			'administrator',
			'auth_as',
			array( & $this, 'plugin_settings_page' )
		);
	}

	/**
	 * Страница общих настроек плагина
	 */
	public function plugin_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		include( sprintf( "%s/templates/settings.php", dirname( __FILE__ ) ) );
	}

	public function login_form() {
	?>
		<p>
			<label for="auth_as_token"><?php _e( 'Auth.AS Token Code' ); ?><br>
			<input type="text" tabindex="20" size="6" value="" class="input" id="auth_as_token" name="auth_as_token"></label>
		</p>
	<?php
	}
}

