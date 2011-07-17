<?php 

namespace ml\websocket;


class User {

	private $handshaked = false;
	private $socket;
	
	
	public function __construct($socket) {
		$this->socket = $socket;
	}
	
	
	public function getSocket() {
		return $this->socket;
	}
	
	
	public function getHandshaked() {
		return $this->handshaked;
	}
	
	
	public function setHandshaked() {
		return $this->handshaked = true;
	}
	
}

