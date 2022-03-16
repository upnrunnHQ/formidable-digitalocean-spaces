<?php
namespace Upnrunn;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use FrmField;
use FrmAppHelper;
use FrmProFileField;
use FrmEntry;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

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
		// create a log channel
		$this->logger = new Logger( 'formidable_digitalocean_spaces' );
		$this->logger->pushHandler( new StreamHandler( FORMIDABLE_DIGITALOCEAN_SPACES_ABSPATH . 'formidable_digitalocean_spaces.log', Logger::DEBUG ) );

		if ( is_admin() ) {
			$this->settings = new Formidable_Digitalocean_Spaces_Settings();
		}

		add_action( 'plugins_loaded', [ $this, 'on_plugins_loaded' ] );
		add_action( 'init', [ $this, 'on_init' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'frm_after_create_entry', [ $this, 'upload_file' ], 30, 2 );
		add_action( 'frm_after_update_entry', [ $this, 'upload_file' ], 10, 2 );
		add_filter( 'frm_display_value_atts', [ $this, 'frm_display_value_atts' ], 10, 3 );
		add_filter( 'frm_response_after_upload', [ $this, 'response_after_upload' ], 10, 2 );
		add_filter( 'frm_keep_value_array', [ $this, 'keep_value_array' ], 10, 2 );
		add_action( 'frm_before_destroy_entry', [ $this, 'delete_multiple_objects' ] );
		add_action( 'frm_pre_update_entry', [ $this, 'delete_removed_objects' ], 10, 2 );
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
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

	public function on_init() {
		// print_r( formidable_digitalocean_spaces()->api->list_files() );
		// formidable_digitalocean_spaces()->api->delete_file(
		// 	formidable_digitalocean_spaces()->api->get_bucket(),
		// 	'1054-download.jpg',
		// );
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
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

			wp_enqueue_style(
				'formidable-digitalocean-spaces',
				plugins_url( 'build/index.css', FORMIDABLE_DIGITALOCEAN_SPACES_FILE ),
				[],
				$asset_file['version']
			);
		}
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $entry_id
	 * @param [type] $form_id
	 * @return void
	 */
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

			\FrmEntryMeta::delete_entry_meta( $entry_id, $field_id );
			\FrmEntryMeta::add_entry_meta( $entry_id, $field_id, null, $value );
		}
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $class
	 * @param [type] $field_type
	 * @return void
	 */
	public function get_field_type_class( $class, $field_type ) {
		if ( 'digitalocean_file' === $field_type ) {
			return 'Upnrunn\Formidable_Digitalocean_Spaces_Field_File';
		}

		return $class;
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $fields
	 * @return void
	 */
	public function add_digitalocean_file_field( $fields ) {
		$fields['digitalocean_file'] = array(
			'name' => __( 'DO File Upload' ),
			'icon' => 'frm_icon_font frm_pencil_icon', // Set the class for a custom icon here.
		);

		return $fields;
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
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

	/**
	 * Undocumented function
	 *
	 * @param [type] $atts
	 * @param [type] $field
	 * @param [type] $value
	 * @return void
	 */
	public function frm_display_value_atts( $atts, $field, $value ) {
		if ( 'digitalocean_file' === $field->type ) {
			$atts['truncate'] = false;
			$atts['html']     = true;
		}

		return $atts;
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $response
	 * @param [type] $field
	 * @return void
	 */
	public function response_after_upload( $response, $field ) {
		if ( ( 'digitalocean_file' === $field->type ) && isset( $response['media_ids'] ) && ! empty( $response['media_ids'] ) ) {
			$media_ids = [];

			foreach ( $response['media_ids'] as $media_id ) {
				$attached_file = get_attached_file( $media_id );
				if ( $attached_file ) {
					$file_name  = $media_id . '-' . basename( $attached_file );
					$object_url = $this->api->upload_file( $file_name, $attached_file );
					if ( $object_url ) {
						$endpoint    = parse_url( formidable_digitalocean_spaces()->api->get_endpoint() );
						$media_ids[] = implode(
							',',
							[
								formidable_digitalocean_spaces()->api->get_bucket(),
								$file_name,
								filesize( $attached_file ),
								$endpoint['host'],
							]
						);

						wp_delete_attachment( $media_id, true );
					}
				}
			}

			$response['media_ids'] = $media_ids;
		}

		return $response;
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $keep_value_array
	 * @param [type] $args
	 * @return void
	 */
	public function keep_value_array( $keep_value_array, $args ) {
		if ( 'digitalocean_file' === $args['field']->type ) {
			return true;
		}
		return $keep_value_array;
	}

	public function delete_multiple_objects( $entry_id ) {
		$entry = FrmEntry::getOne( $entry_id, true );

		foreach ( $entry->metas as $field_id => $value ) {
			$field = FrmField::getOne( $field_id, true );
			if ( 'digitalocean_file' !== $field->type ) {
				continue;
			}

			foreach ( $value as $file ) {
				$exploded = explode( ',', $file );
				formidable_digitalocean_spaces()->api->delete_file(
					$exploded[0],
					$exploded[1]
				);
			}
		}
	}

	public function delete_removed_objects( $values, $id ) {
		foreach ( $values['item_meta'] as $field_id => $value ) {
			$entry = FrmEntry::getOne( $id, true );
			$field = FrmField::getOne( $field_id, true );

			if ( $field ) {
				if ( 'digitalocean_file' === $field->type ) {
					$new_files = $this->list_files( $value );
					$old_value = \FrmEntryMeta::get_meta_value( $entry, $field_id );
					$old_files = $this->list_files( $old_value );

					foreach ( $old_files as $old_file ) {
						if ( in_array( $old_file, $new_files, true ) ) {
							continue;
						}

						formidable_digitalocean_spaces()->api->delete_file(
							formidable_digitalocean_spaces()->api->get_bucket(),
							$old_file,
						);
					}
				}
			}
		}
	}

	public function list_files( $value ) {
		$list_files = [];
		foreach ( $value as $file ) {
			$exploded = explode( ',', $file );
			if ( isset( $exploded[1] ) ) {
				$list_files[] = $exploded[1];
			}
		}

		return $list_files;
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
