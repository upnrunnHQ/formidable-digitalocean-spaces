<?php
namespace Upnrunn;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use FrmField;
use FrmAppHelper;
use FrmProFileField;

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
		add_action( 'plugins_loaded', [ $this, 'on_plugins_loaded' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'frm_after_create_entry', [ $this, 'upload_file' ], 30, 2 );
		add_action( 'frm_after_update_entry', [ $this, 'upload_file' ], 10, 2 );
		add_filter( 'frm_display_value_atts', [$this, 'frm_display_value_atts'], 10, 3);
	}

	public function test_do_spaces() {
		wp_send_json(
			[
				formidable_digitalocean_spaces()->api->upload_file(
					'test.png',
					'test'
				),
			]
		);
	}

	public function on_plugins_loaded() {
		include_once FORMIDABLE_DIGITALOCEAN_SPACES_ABSPATH . 'includes/class-formidable-digitalocean-spaces-field-file.php';
		include_once FORMIDABLE_DIGITALOCEAN_SPACES_ABSPATH . 'includes/class-formidable-digitalocean-spaces-file-field.php';
		add_filter( 'frm_get_field_type_class', [ $this, 'get_field_type_class' ], 10, 2 );
		add_filter( 'frm_available_fields', [ $this, 'add_digitalocean_file_field' ] );

		remove_action( 'wp_ajax_nopriv_frm_submit_dropzone', 'FrmProFieldsController::ajax_upload' );
		remove_action( 'wp_ajax_frm_submit_dropzone', 'FrmProFieldsController::ajax_upload' );

		add_action( 'wp_ajax_nopriv_frm_submit_dropzone', [ $this, 'ajax_upload' ] );
		add_action( 'wp_ajax_frm_submit_dropzone', [ $this, 'ajax_upload' ] );
	}

	public function enqueue_scripts() {
		if ( formidable_digitalocean_spaces()->api->has_api_credentials()
			&& formidable_digitalocean_spaces()->api->has_bucket_name()
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
					'wait_message' => formidable_digitalocean_spaces()->api->options['wait_message'],
				)
			);
		}
	}

	public function upload_file( $entry_id, $form_id ) {
		if ( ! formidable_digitalocean_spaces()->api->has_api_credentials()
			|| ! formidable_digitalocean_spaces()->api->has_bucket_name()
		) {
			return;
		}

		foreach ( $_POST['item_meta'] as $field_id => $value ) {
			if ( ! $field_id ) {
				continue;
			}

			$field       = FrmField::getOne( $field_id, true );
			$is_multiple = FrmField::is_option_true( $field, 'multiple' );

			if ( 'digitalocean_file' !== $field->type ) {
				continue;
			}

			if ( $value ) {
				if ( $is_multiple ) {
					$new_value = [];

					foreach ( $value as $p ) {
						if ( strpos( $p, ',' ) === false ) {
							$attached_file = get_attached_file( $p );
							if ( $attached_file ) {
								$file_name = $p . '-' . basename( $attached_file );

								$object_url = $this->api->upload_file( $file_name, $attached_file );
								if ( $object_url ) {
									$new_value[] = implode(
										',',
										[
											formidable_digitalocean_spaces()->api->get_bucket(),
											$file_name,
											filesize( $attached_file ),
										]
									);

									wp_delete_attachment( $p, true );
								}
							}
						} else {
							$new_value[] = $p;
						}
					}
				} else {
					$new_value = '';
					if ( strpos( $value, ',' ) === false ) {
						$attached_file = get_attached_file( $value );
						if ( $attached_file ) {
							$file_name = $value . '-' . basename( $attached_file );

							$object_url = $this->api->upload_file( $file_name, $attached_file );
							if ( $object_url ) {
								$new_value = implode(
									',',
									[
										formidable_digitalocean_spaces()->api->get_bucket(),
										$file_name,
										filesize( $attached_file ),
									]
								);

								wp_delete_attachment( $value, true );
							}
						}
					}
				}
			}

			if ( empty( $new_value ) ) {
				continue;
			}

			$meta_added = \FrmEntryMeta::add_entry_meta( $entry_id, $field_id, null, $new_value );
			if ( ! $meta_added ) {
				\FrmEntryMeta::update_entry_meta( $entry_id, $field_id, null, $new_value );
			}
		}
	}

	public function get_field_type_class( $class, $field_type ) {
		if ( 'digitalocean_file' === $field_type ) {
			return 'Upnrunn\Formidable_Digitalocean_Spaces_Field_File';
		}

		return $class;
	}

	public function add_digitalocean_file_field( $fields ) {
		$fields['digitalocean_file'] = array(
			'name' => __( 'DO File Upload' ),
			'icon' => 'frm_icon_font frm_pencil_icon', // Set the class for a custom icon here.
		);

		return $fields;
	}

	public static function ajax_upload() {
		// Skip nonce for caching.
		$response = Formidable_Digitalocean_Spaces_File_Field::ajax_upload();

		if ( ! empty( $response['errors'] ) ) {
			status_header( 403 );
			$status = 403;
			echo implode( ' ', $response['errors'] );
		} else {
			$status = 200;
			echo json_encode( $response['media_ids'] );
		}

		wp_die( '', '', array( 'response' => $status ) );
	}

	public function frm_display_value_atts($atts, $field, $value) {
		if ( 'digitalocean_file' === $field->type ) {
			$atts['truncate'] = false;
			$atts['html']     = true;
		}
		
		return $atts;
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
