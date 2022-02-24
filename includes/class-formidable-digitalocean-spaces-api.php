<?php
namespace Upnrunn;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Aws\S3\S3Client;

class Formidable_Digitalocean_Spaces_API {
	private $client;
	private $bucket = 'formidable-forms';

	public function __construct() {
		$this->client = new S3Client(
			[
				'version'     => 'latest',
				'region'      => 'us-east-1',
				'endpoint'    => 'https://nyc3.digitaloceanspaces.com',
				'credentials' => [
					'key'    => '76IH6EFMUFUY4ONZLYI3',
					'secret' => 'bDXY0xpw02s2/IZiW808s3uZN5r4uRD7wZU06Kws3f0',
				],
			]
		);
	}

	public function get_bucket() {
		return $this->bucket;
	}

	public function upload_file( $file = [] ) {
		return $this->client->putObject(
			$file
		);
	}

	public function list_files() {
		return $this->client->listObjects(
			[ 'Bucket' => $this->bucket ]
		);
	}
}
