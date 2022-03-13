<?php
namespace Upnrunn;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Aws\S3\S3Client;

class Formidable_Digitalocean_Spaces_API {
	public $options;
	private $client;

	public function __construct() {
		$this->options = get_option( 'formidable_digitalocean_spaces_options' );

		if ( $this->has_api_credentials() ) {
			try {
				$this->client = new S3Client(
					[
						'version'     => 'latest',
						'region'      => 'us-east-1',
						'endpoint'    => $this->options['endpoint'],
						'credentials' => [
							'key'    => $this->options['key'],
							'secret' => $this->options['secret'],
						],
					]
				);
			} catch ( Exception $e ) {
				$this->client = null;
			}
		}
	}

	public function has_api_credentials() {
		if ( ! isset( $this->options['endpoint'], $this->options['key'], $this->options['secret'] ) || empty( $this->options['endpoint'] ) || empty( $this->options['key'] ) || empty( $this->options['secret'] ) ) {
			return false;
		}

		return true;
	}

	public function has_endpoint() {
		if ( ! isset( $this->options['endpoint'] ) || empty( $this->options['endpoint'] ) ) {
			return false;
		}

		return true;
	}

	public function has_bucket_name() {
		if ( ! isset( $this->options['bucket'] ) || empty( $this->options['bucket'] ) ) {
			return false;
		}

		return true;
	}

	public function has_wait_message() {
		if ( ! isset( $this->options['wait_message'] ) || empty( $this->options['wait_message'] ) ) {
			return false;
		}

		return true;
	}

	public function get_bucket() {
		if ( $this->has_bucket_name() ) {
			return $this->options['bucket'];
		}

		return false;
	}

	public function get_endpoint() {
		if ( $this->has_endpoint() ) {
			return $this->options['endpoint'];
		}

		return false;
	}

	public function upload_file( $key = '', $attached_file = '' ) {
		if ( $this->has_api_credentials() && $this->has_bucket_name() && $this->client ) {
			try {
				$bucket        = $this->get_bucket();
				$bucket_exists = false;

				$spaces = $this->list_buckets();

				foreach ( $spaces['Buckets'] as $space ) {
					if ( $bucket === $space['Name'] ) {
						$bucket_exists = true;
					}
				}

				if ( ! $bucket_exists ) {
					$this->create_bucket();
				}

				$object = $this->client->putObject(
					[
						'Bucket'     => $this->get_bucket(),
						'Key'        => $key,
						'SourceFile' => $attached_file,
						'ACL'        => 'public-read',
					]
				);

				return $object->get( 'ObjectURL' );
			} catch ( Exception $e ) {
				return false;
			}
		}

		return false;
	}

	public function create_bucket() {
		$this->client->createBucket(
			[ 'Bucket' => $this->get_bucket() ]
		);
	}

	public function list_files() {
		if ( $this->has_api_credentials() && $this->has_bucket_name() && $this->client ) {
			return $this->client->listObjects(
				[ 'Bucket' => $this->get_bucket() ]
			);
		}

		return [];
	}

	public function list_buckets() {
		if ( $this->has_api_credentials() ) {
			return $this->client->listBuckets();
		}

		return [];
	}
}
