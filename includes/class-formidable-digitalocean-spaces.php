<?php
namespace Upnrunn;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce_Grow_Cart class.
 * @var [type]
 */
final class Formidable_Digitalocean_Spaces {
	/**
	 * The single instance of the class.
	 * @var [type]
	 */
	protected static $_instance = null;

	/**
	 * Main Container instance.
	 * Ensures only one instance of Formidable_Digitalocean_Spaces is loaded or can be loaded.
	 *
	 * @return [type] [description]
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Container constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->hooks();
	}

	/**
	 * Define WooCommerce_Grow_Cart constants.
	 */
	private function define_constants() {
		$this->define( 'FORMIDABLE_DIGITALOCEAN_SPACES_ABSPATH', dirname( FORMIDABLE_DIGITALOCEAN_SPACES_FILE ) . '/' );
	}

	/**
	 * Include required files used in admin and on the frontend.
	 * @return [type] [description]
	 */
	private function includes() {
		// Require the Composer autoloader.
		require FORMIDABLE_DIGITALOCEAN_SPACES_ABSPATH . 'vendor/autoload.php';
		include_once FORMIDABLE_DIGITALOCEAN_SPACES_ABSPATH . 'includes/functions.php';
		include_once FORMIDABLE_DIGITALOCEAN_SPACES_ABSPATH . 'includes/class-formidable-digitalocean-spaces-api.php';
		include_once FORMIDABLE_DIGITALOCEAN_SPACES_ABSPATH . 'includes/class-formidable-digitalocean-spaces-api.php';

		if ( is_admin() ) {
			include_once FORMIDABLE_DIGITALOCEAN_SPACES_ABSPATH . 'includes/class-formidable-digitalocean-spaces-settings.php';
		}
	}

	/**
	 * Init hooks.
	 * @return [type] [description]
	 */
	private function hooks() {
		$this->api = new Formidable_Digitalocean_Spaces_API();
		if ( is_admin() ) {
			$this->settings = new Formidable_Digitalocean_Spaces_Settings();
		}

		add_action( 'wp_ajax_test_do_spaces', [ $this, 'test_do_spaces' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'frm_after_create_entry', [ $this, 'upload_file' ], 30, 2 );
	}

	public function test_do_spaces() {
		// print_r( $this->api->get_bucket() );
		// $this->api->list_files();
		// formidable_digitalocean_spaces()->upload_file( 267, 11 );
		wp_send_json( [] );
	}

	public function enqueue_scripts() {
		if ( formidable_digitalocean_spaces()->api->has_api_credentials()
			&& formidable_digitalocean_spaces()->api->has_bucket_name()
			&& formidable_digitalocean_spaces()->api->has_ff_field_ids()
			&& formidable_digitalocean_spaces()->api->has_wait_message()
		) {
			$asset_file = include FORMIDABLE_DIGITALOCEAN_SPACES_ABSPATH . 'build/index.asset.php';

			wp_enqueue_script(
				'formidable-digitalocean-spaces',
				plugins_url( 'build/index.js', FORMIDABLE_DIGITALOCEAN_SPACES_FILE ),
				array_merge( $asset_file['dependencies'], [ 'formidable' ] ),
				$asset_file['version'],
				true
			);

			wp_localize_script(
				'formidable-digitalocean-spaces',
				'formidable_digitalocean_spaces',
				array(
					'upload_field_id' => formidable_digitalocean_spaces()->api->options['upload'],
					'wait_message'    => formidable_digitalocean_spaces()->api->options['wait_message'],
				)
			);
		}
	}

	public function upload_file( $entry_id, $form_id ) {
		if ( formidable_digitalocean_spaces()->api->has_api_credentials()
			&& formidable_digitalocean_spaces()->api->has_bucket_name()
			&& formidable_digitalocean_spaces()->api->has_ff_field_ids()
		) {
			$file_field_id = formidable_digitalocean_spaces()->api->options['upload'];
			$text_field_id = formidable_digitalocean_spaces()->api->options['file'];

			$entry        = \FrmEntry::getOne( $entry_id );
			$entry_values = new \FrmEntryValues( $entry_id );
			$fields       = \FrmField::getAll(
				array(
					'fi.form_id'  => (int) $form_id,
					'fi.type not' => \FrmField::no_save_fields(),
				)
			);

			$field_values = $entry_values->get_field_values();

			foreach ( $fields as $field ) {
				if ( absint( $field->id ) === $file_field_id ) {
					$field_value   = $field_values[ $field->id ];
					$attachment_id = $field_value->get_saved_value();
					$attachment    = get_attached_file( $attachment_id );
					if ( $attachment ) {
						$file_name     = basename( $attachment );
						$file_name     = $attachment_id . '-' . $file_name;
						$file_contents = file_get_contents( $attachment );
						$object_url    = $this->api->upload_file( $file_name, $file_contents );

						$added = \FrmEntryMeta::add_entry_meta( $entry_id, $text_field_id, null, $object_url );
						if ( ! $added ) {
							\FrmEntryMeta::update_entry_meta( $entry_id, $text_field_id, null, $object_url );
						}

						wp_delete_attachment( $attachment_id, true );
						\FrmEntryMeta::delete_entry_meta( $entry_id, $file_field_id );
					}
				}
			}
		}
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}
}
