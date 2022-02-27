<?php
namespace Upnrunn;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Aws\S3\S3Client;

class Formidable_Digitalocean_Spaces_Settings {
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;

	/**
	 * Start up
	 */
	public function __construct() {
		// Set class property
		$this->options = get_option( 'formidable_digitalocean_spaces_options' );
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page() {
		// This page will be under "Settings"
		add_options_page(
			__( 'Digitalocean Spaces Settings' ),
			__( 'Digitalocean Spaces' ),
			'manage_options',
			'formidable-digitalocean-spaces',
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
			<?php
				// This prints out all hidden setting fields
				settings_fields( 'formidable_digitalocean_spaces' );
				do_settings_sections( 'formidable-digitalocean-spaces' );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init() {
		register_setting(
			'formidable_digitalocean_spaces', // Option group
			'formidable_digitalocean_spaces_options', // Option name
			array( $this, 'sanitize' ) // Sanitize
		);

		add_settings_section(
			'formidable_digitalocean_spaces_section_api', // ID
			__( 'Spaces API' ), // Title
			array( $this, 'section_api_callback' ), // Callback
			'formidable-digitalocean-spaces' // Page
		);

		add_settings_section(
			'formidable_digitalocean_spaces_section_formidable_fields', // ID
			__( 'Formidable Fields' ), // Title
			array( $this, 'section_formidable_fields_callback' ), // Callback
			'formidable-digitalocean-spaces' // Page
		);

		add_settings_field(
			'endpoint', // ID
			__( 'Endpoint' ), // Title
			array( $this, 'field_endpoint_callback' ), // Callback
			'formidable-digitalocean-spaces', // Page
			'formidable_digitalocean_spaces_section_api' // Section
		);

		add_settings_field(
			'key', // ID
			__( 'Key' ), // Title
			array( $this, 'field_key_callback' ), // Callback
			'formidable-digitalocean-spaces', // Page
			'formidable_digitalocean_spaces_section_api' // Section
		);

		add_settings_field(
			'secret', // ID
			__( 'Secret' ), // Title
			array( $this, 'field_secret_callback' ), // Callback
			'formidable-digitalocean-spaces', // Page
			'formidable_digitalocean_spaces_section_api' // Section
		);

		add_settings_field(
			'bucket', // ID
			__( 'Bucket' ), // Title
			array( $this, 'field_bucket_callback' ), // Callback
			'formidable-digitalocean-spaces', // Page
			'formidable_digitalocean_spaces_section_api' // Section
		);

		add_settings_field(
			'upload', // ID
			__( 'Upload Field' ), // Title
			array( $this, 'field_upload_callback' ), // Callback
			'formidable-digitalocean-spaces', // Page
			'formidable_digitalocean_spaces_section_formidable_fields' // Section
		);

		add_settings_field(
			'file', // ID
			__( 'File Field' ), // Title
			array( $this, 'field_file_callback' ), // Callback
			'formidable-digitalocean-spaces', // Page
			'formidable_digitalocean_spaces_section_formidable_fields' // Section
		);

		add_settings_field(
			'wait_message', // ID
			__( 'Wait Message' ), // Title
			array( $this, 'field_wait_message_callback' ), // Callback
			'formidable-digitalocean-spaces', // Page
			'formidable_digitalocean_spaces_section_formidable_fields' // Section
		);
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 */
	public function sanitize( $input ) {
		$new_input = array();
		if ( isset( $input['endpoint'] ) ) {
			$new_input['endpoint'] = sanitize_text_field( $input['endpoint'] );
		}

		if ( isset( $input['key'] ) ) {
			$new_input['key'] = sanitize_text_field( $input['key'] );
		}

		if ( isset( $input['secret'] ) ) {
			$new_input['secret'] = sanitize_text_field( $input['secret'] );
		}

		if ( isset( $input['bucket'] ) ) {
			$new_input['bucket'] = sanitize_text_field( $input['bucket'] );
		}

		if ( isset( $input['upload'] ) ) {
			$new_input['upload'] = absint( $input['upload'] );
		}

		if ( isset( $input['file'] ) ) {
			$new_input['file'] = absint( $input['file'] );
		}

		if ( isset( $input['wait_message'] ) ) {
			$new_input['wait_message'] = sanitize_text_field( $input['wait_message'] );
		}

		return $new_input;
	}


	/**
	 * Print the Section text
	 */
	public function section_api_callback( $args ) {
		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Please enter API Key and API Secret below.', 'wporg' ); ?></p>
		<?php
	}

	public function section_formidable_fields_callback( $args ) {
		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Please enter Formidable field IDs below.', 'wporg' ); ?></p>
		<?php
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function field_endpoint_callback( $args ) {
		printf(
			'<input type="text" class="regular-text" id="endpoint" name="formidable_digitalocean_spaces_options[endpoint]" value="%s" />',
			isset( $this->options['endpoint'] ) ? esc_attr( $this->options['endpoint'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function field_key_callback( $args ) {
		printf(
			'<input type="text" class="regular-text" id="key" name="formidable_digitalocean_spaces_options[key]" value="%s" />',
			isset( $this->options['key'] ) ? esc_attr( $this->options['key'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function field_secret_callback( $args ) {
		printf(
			'<input type="text" class="regular-text" id="secret" name="formidable_digitalocean_spaces_options[secret]" value="%s" />',
			isset( $this->options['secret'] ) ? esc_attr( $this->options['secret'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function field_bucket_callback( $args ) {
		printf(
			'<input type="text" class="regular-text" id="bucket" name="formidable_digitalocean_spaces_options[bucket]" value="%s" />',
			isset( $this->options['bucket'] ) ? esc_attr( $this->options['bucket'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function field_upload_callback( $args ) {
		printf(
			'<input type="text" class="regular-text" id="upload" name="formidable_digitalocean_spaces_options[upload]" value="%s" />',
			isset( $this->options['upload'] ) ? esc_attr( $this->options['upload'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function field_file_callback( $args ) {
		printf(
			'<input type="text" class="regular-text" id="file" name="formidable_digitalocean_spaces_options[file]" value="%s" />',
			isset( $this->options['file'] ) ? esc_attr( $this->options['file'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function field_wait_message_callback( $args ) {
		printf(
			'<textarea type="text" class="large-text" id="wait_message" name="formidable_digitalocean_spaces_options[wait_message]">%s</textarea>',
			isset( $this->options['wait_message'] ) ? esc_attr( $this->options['wait_message'] ) : ''
		);
	}
}
