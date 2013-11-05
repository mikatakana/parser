<?php 

namespace Parser;

class Parser {

	protected $ch;
	protected $cookies = [];

	public function __construct() {
		$this->ch = curl_init();

		// Default settings:
		$this->set('ReturnTransfer', true);
		$this->set('FollowLocation', true);

		$this->set('SSL_VerifyHost', false);
		$this->set('SSL_VerifyPeer', false);

		$this->set('Header', true);
		$this->set('AutoReferer', true);
		$this->set('UserAgent', $_SERVER['HTTP_USER_AGENT']);
	}

	public function set($param, $value) {
		curl_setopt($this->ch, constant('CURLOPT_' . strtoupper($param)), $value);
	}

	public function get($url, $callback = false) {
		$content = $this->execute($url);

		return $callback
			? $this->parse($content, $callback)
			: $content;
	}

	public function parse($str, $callback) {
		if (!$str) {
			return false;
		}

		$pattern = new Pattern;
		call_user_func_array($callback, [$pattern]);

		preg_match_all($pattern->build(), $str, $m);
		$keys = $pattern->getKeys();
		array_shift($m);

		$rows = [];
		foreach ($m as $param => $values) {
			$key = $keys[$param];
			
			foreach ($values as $num => $value) {
				if (!isset($rows[$num])) {
					$rows[$num] = new \stdClass;
				}

				$rows[$num]->$key = $value;
			}
		}

		return count($rows) === 1 ? reset($rows) : $rows;
	}

	public function send($url, $data) {
		$this->set('Post', true);
		$this->set('PostFields', http_build_query($data));

		$response = $this->execute($url);

		$this->set('Post', false);
		$this->set('PostFields', false);

		return $response;
	}

	protected function execute($url) {
		$this->set('Url', $url);

		// Get cookies:
		$cookies = [];
		foreach ($this->cookies as $name => $value) {
			$cookies[] = $name.'='.$value;
		}

		if ($cookies) {
			$this->set('Cookie', implode('; ', $cookies));
		} else {
			$this->set('Cookie', false);
		}

		$response = curl_exec($this->ch);

		// Manually work with cookies:
		$cookies = $this->parse($response, function($pattern) {
			$pattern->find('Set-Cookie: {name}={value};');
		});

		// Store cookies:
		foreach ($cookies as $cookie) {
			$this->cookies[$cookie->name] = $cookie->value;
		}

		// return $response;
		return substr($response, curl_getinfo($this->ch, CURLINFO_HEADER_SIZE));
	}

	public function cookie($key, $value = false) {
		if ($value) {
			$this->cookies[$key] = $value;
		}

		return $this->cookies[$key] ? : false;
	}

	public function cookies() {
		return $this->cookies;
	}

	public function close() {
		curl_close($this->ch);
	}
	
}