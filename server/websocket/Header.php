<?php

namespace ml\websocket;



class Header {
	
	private $header;
	private $parsed = array(); 
	
	
	public function __construct($header) {
		$this->header = $header;
		$this->parse();
	}
	
		
	public function parse() {
		$arr = explode("\r\n", $this->header);
		$method = array_shift($arr);
		
		$methodArr = explode(' ', $method);
		$params = array();
		
		foreach ($arr as $header) {
			if ($header) {
				$tmp = explode(':', $header);
				$key = array_shift($tmp);
				$params[$key] = trim(implode(':', $tmp));
			}
			else {
				break;
			}
		}
		
		$this->parsed = array(
			'method' => $method,
			'resource' => $methodArr[1],
			'params' => $params,
			'msg' => array_pop($arr),
		);
	}
	
	
	public function getResource() {
		return $this->parsed['resource'];
	}
	
	
	public function getParams() {
		return $this->parsed['params'];
	}
	
	
	public function getMessage() {
		return $this->parsed['msg'];
	}
	
	
}


