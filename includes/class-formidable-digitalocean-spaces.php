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
	}

	/**
	 * Init hooks.
	 * @return [type] [description]
	 */
	private function hooks() {
		$this->api = new Formidable_Digitalocean_Spaces_API();

		add_action( 'wp_ajax_test_do_spaces', [ $this, 'test_do_spaces' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'frm_after_create_entry', [ $this, 'upload_file' ], 30, 2 );
	}

	public function test_do_spaces() {
		// print_r( $this->api->get_bucket() );
		// $this->api->list_files();
		formidable_digitalocean_spaces()->upload_file( 267, 11 );
		wp_send_json( [] );
	}

	public function enqueue_scripts() {
		$asset_file = include FORMIDABLE_DIGITALOCEAN_SPACES_ABSPATH . 'build/index.asset.php';

		wp_enqueue_script(
			'formidable-digitalocean-spaces',
			plugins_url( 'build/index.js', FORMIDABLE_DIGITALOCEAN_SPACES_FILE ),
			array_merge( $asset_file['dependencies'], [ 'formidable' ] ),
			$asset_file['version'],
			true
		);
	}

	public function upload_file( $entry_id, $form_id ) {
		$file_field_id = 207;
		$text_field_id = 210;

		$entry  = \FrmEntry::getOne( $entry_id );
		$fields = \FrmField::getAll(
			array(
				'fi.form_id'  => (int) $form_id,
				'fi.type not' => \FrmField::no_save_fields(),
			)
		);

		$entry_values = new \FrmEntryValues( $entry_id );
		$field_values = $entry_values->get_field_values();

		foreach ( $fields as $field ) {
			if ( intval( $field->id ) === $file_field_id ) {
				$field_value   = $field_values[ $field->id ];
				$attachment_id = $field_value->get_saved_value();
				$attachment    = get_attached_file( $attachment_id );
				if ( $attachment ) {
					$file_name     = basename( $attachment );
					$file_name     = $attachment_id . '-' . $file_name;
					$file_contents = file_get_contents( $attachment );

					$uploaded = $this->api->upload_file(
						[
							'Bucket' => $this->api->get_bucket(),
							'Key'    => $file_name,
							'Body'   => $file_contents,
							'ACL'    => 'public-read',
						]
					);

					$object_url = $uploaded->get( 'ObjectURL' );

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
