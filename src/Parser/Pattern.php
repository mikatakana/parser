<?php

namespace Parser;

class Pattern {
	
	protected $autoskip = true;
	protected $pattern = '';
	protected $params = [];

	public function find($str) {
		$regexp = '~{(.*?)}~ui';

		preg_match_all($regexp, $str, $m);

		if (!empty($m[1])) {
			array_shift($m);
			$this->params = array_merge($this->params, $m[0]);
			$str = preg_replace($regexp, '(.*?)', $str);
		}

		$this->pattern .= $str;

		if ($this->autoskip) {
			$this->skip();
		}
	} 

	public function autoskip($enable = true) {
		$this->autoskip = $enable;
	}

	public function skip() {
		$this->pattern .= '.*?';
	}

	public function build() {
		return '~' . trim($this->pattern, '.*?') . '~ui';
	}

	public function getKeys() {
		return $this->params;
	}

}