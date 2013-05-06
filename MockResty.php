<?php

/**
 * Extension of Resty where we hijack the bit that actually goes away to do the
 * HTTP fetch. Instead, we just fake the headers and the response. By using the
 * "on" method we can stipulate a response on a URL.
 *
 * @author Adrian Hardy <ah@adrianhardy.co.uk>
 * @since 23rd Feb 2013
 *
 */
class MockResty extends Resty {

	protected $stubbed_urls = array();

	public function setStubbedUrls(array $stubs) {
		$this->stubbed_urls = array();
		foreach ($stubs as $method => $responses) {
			foreach ($responses as $url => $response) {
				$this->on($method, $url, $response);
			}
		}
		return $this;
	}

	/**
	 * Set up a response to a url for a specific method.
	 *
	 * @param string $url
	 * @param string|function $response
	 *
	 * @return MockResty
	 */
	public function on($method, $url, $response) {
		$method = strtoupper($method);
		if (!isset($this->stubbed_urls[$method])) {
			$this->stubbed_urls[$method] = array();
		}
		$this->stubbed_urls[$method][$url] = $response;
		return $this;
	}

	/**
	 * Hijack the bit that sends the request and instead, just make it look
	 * for a stubbed response in an array. We fake the response headers
	 * to fool \Resty::processResponseBody() into thinking everything went
	 * well.
	 *
	 * @param $url
	 * @param $opts
	 * @return array
	 *
	 */
	protected function makeStreamRequest($url, $opts) {

		$resp_data = array(
			'meta' => null,
			'body' => null,
			'error' => false,
			'error_msg' => null,
		);

		if ($this->base_url && strpos($url, $this->base_url) !== false) {
			$url = str_replace($this->base_url, '', $url);
		}

		// --------------- PRETEND TO MAKE THE REQUEST ---------------------------

		// --------------- DONE. NOTHING TO SEE HERE -----------------------------
		
		$default_headers = array(
			'status' => 'HTTP/1.1 200 OK',
			'server' => 'Server: ' . get_class($this) . ' 1.0.0 (PHP)',
			'content_type' => 'Content-Type: application/json; charset=UTF-8',
			'connection' => 'Connection: close',
			'date' => 'Date: ' . date('D, d M Y H:i:s T')
		);

		$method = $opts['http']['method'];


		// the stubbed URLs can either be strings
		if (isset($this->stubbed_urls[$method][$url])) {
			$stub_response = $this->stubbed_urls[$method][$url];
			if (is_callable($stub_response)) {
				$response = $stub_response();
				if (isset($response['headers'])) {
					$default_headers = array_merge(
						$default_headers, 
						$response['headers']
					);
				}
				$resp_data['body'] = $response['body'];
			} else {
				// support a short-hand so that we can say:
				// URL:/path/to/service = 'HTTP/1.1 403 Forbidden';
				if (preg_match('/HTTP\/1.1 [\d{3}]/',$stub_response)) {
					$default_headers['status'] = $stub_response;
				} else {
					$resp_data['body'] = $stub_response;
				}
			}
		} else {
			$default_headers['status'] = 'HTTP/1.1 404 Not Found';
		}

		$resp_data['meta'] = array(
			'wrapper_data' => array_values($default_headers)
		);

		return $resp_data;
	}
}
