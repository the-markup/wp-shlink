<?php
/**
 * Class API
 *
 * @package   Shlink
 * @author    The Markup
 * @license   GPL-2.0-or-later
 * @link      https://themarkup.org/
 * @copyright 2022 The Markup
 */

namespace WP_Shlink;

use \WP_Shlink\Options;

/**
 * Interface to the Shlink REST API
 */
class API {

	static $instance;

	static function init() {
		if (! self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	function __construct() {
		$this->options = Options::init();
	}

	function create_shlink($args) {
		$base_url = $this->options->get('base_url');
		$endpoint = "$base_url/rest/v2/short-urls";
		return $this->request('POST', $endpoint, $args);
	}

	function update_shlink($short_code, $args) {
		$base_url = $this->options->get('base_url');
		$endpoint = "$base_url/rest/v2/short-urls/$short_code";
		return $this->request('PATCH', $endpoint, $args);
	}

	function get_shlinks($args = null) {
		$base_url = $this->options->get('base_url');
		$endpoint = "$base_url/rest/v2/short-urls";
		return $this->request('GET', $endpoint, $args);
	}

	function get_domains() {
		$base_url = $this->options->get('base_url');
		$endpoint = "$base_url/rest/v2/domains";
		return $this->request('GET', $endpoint);
	}

	function request($method, $endpoint, $args = null) {
		$url = $endpoint;
		$request = [
			'method'  => $method,
			'headers' => [
				'Accept'       => 'application/json',
				'X-Api-Key'    => $this->options->get('api_key')
			]
		];
		if ($args) {
			if (strtoupper($method) == 'GET') {
				$url .= '?' . build_query($args);
			} else {
				$request['headers']['Content-Type'] = 'application/json';
				$request['body'] = wp_json_encode($args);
			}
		}
		$response = wp_remote_request($url, $request);
		$status = wp_remote_retrieve_response_code($response);
		if (is_wp_error($response)) {
			throw new \Exception('wp-shlink: ' . $response->get_error_message());
		} else if ($status != 200) {
			throw new \Exception("wp-shlink: HTTP $status {$response['body']}");
		} else if (! empty($response['body'])) {
			return json_decode($response['body'], 'array');
		} else {
			throw new \Exception("wp-shlink: error loading $endpoint");
		}
	}

}
