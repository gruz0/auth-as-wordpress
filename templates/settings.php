<?php defined( 'ABSPATH' ) or exit; ?>

<div class="wrap auth-as-settings">
	<h1><?php _e( 'Two-Factor Authentication via auth.as', L10N_SCP_PREFIX ); ?></h1>
	<form method="post" action="options.php">
		<?php wp_nonce_field( 'auth-as-update-options' ); ?>
		<?php settings_fields( 'auth_as' ); ?>
		<?php do_settings_sections( 'auth_as' ); ?>
		<?php submit_button(); ?>
	</form>
	<?php require( sprintf( "%s/copyright.php", dirname( __FILE__ ) ) ); ?>
</div>

