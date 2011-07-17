<?php

namespace ml\websocket;



class WebSocket {
	
	private $sockets = array();
	private $socket;
	private $address;
	private $port;
	
	public $debug = true;
	
	
	public function __construct($address, $port) {
		$this->address = $address;
		$this->port = $port;
		
		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($this->socket, $address, $port);
		socket_listen($this->socket, 1);
		
		$this->addSocket($this->socket);	
		
		$this->debug('Server created');
		$this->debug("Listening on {$this->address}, port {$this->port}");
				
	}
	
	
	public function debug($msg) {
		echo date('Y-m-d H:i:s ') . $msg . "\n";
	}
	
	
	public function getHandshake($buffer) {
		$header = new Header($buffer);
        $params = $header->getParams();;
        
        if (isset($params['Sec-WebSocket-Key'])) {
	        $response = "HTTP/1.1 101 Switching Protocols\r\n";
	        $response .= "Upgrade: websocket\r\n";
	        $response .= "Connection: Upgrade\r\n";
	        $response .= "Sec-WebSocket-Accept: $acceptKey";
	        $response .= "\0";
        }
        else {
			$pattern = '/[^\d]*/';
			$replacement = '';
			$numkey1 = preg_replace($pattern, $replacement, $params['Sec-WebSocket-Key1']);
			$numkey2 = preg_replace($pattern, $replacement, $params['Sec-WebSocket-Key2']);
			$pattern = '/[^ ]*/';
			$replacement = '';
			$spaces1 = strlen(preg_replace($pattern, $replacement, $params['Sec-WebSocket-Key1']));
			$spaces2 = strlen(preg_replace($pattern, $replacement, $params['Sec-WebSocket-Key2']));
			$hashData = md5( pack("N", $numkey1/$spaces1) . pack("N", $numkey2/$spaces2) . $header->getMessage(), true);
        	
			$response = "HTTP/1.1 101 WebSocket Protocol Handshake\r\n";
			$response .= "Upgrade: WebSocket\r\n";
			$response .= "Connection: Upgrade\r\n";
			$response .= "Sec-WebSocket-Origin: " . $params['Origin'] . "\r\n";
			$response .= "Sec-WebSocket-Location: ws://" . "{$this->address}:{$this->port}" . $header->getResource() . "\r\n";
			$response .= "\r\n";
			$response .= $hashData;
			$response .= "\0";
        }
        return $response;
	}
	
	
	public function getSocket() {
		return $this->socket;
	}
	
	
	public function addSocket($socket) {
		$this->sockets[(string)$socket] = $socket;
	}
	
	
	public function removeSocket($socket) {
		unset($this->sockets[(string)$socket]);
		//$key = array_search($socket, $this->sockets, true);
		//unset($this->sockets[$key]);
	}
	
	
	public function getSockets() {
		return $this->sockets;
	}
	
	
	public function run() {
		$socket = $this->getSocket();
		$users = array();
		
		while (true) {
			$changed = $this->getSockets();
			socket_select($changed, $write = null, $except = null, null);
			foreach($changed as $s){
				if ($s == $socket) { // main socket
					$client = socket_accept($s);
					if ($client < 0) {
						$this->debug("socket_accept() failed");
					}
					else {
						$this->debug("socket_accept()...");
						$this->addSocket($client);
						$users[(string)$client] = new User($client);
					}
				}
				else { // clients
					$bytes = socket_recv($s, $buffer, 2048, 0);
					if ($bytes == 0) {
						$this->debug('Disconnect: ' . $s);
						$this->removeSocket($s);
					}
					else{
				        $this->debug($buffer);
				        $user = $users[(string)$s];
				        
				        if ($user->getHandshaked()) {
				        	foreach ($this->getSockets() as $userSocket) {
				        		if ($userSocket != $socket) {
					        		$response = "$buffer\0";
					        		socket_write($userSocket, $response, strlen($response));
				        		}
				        	}
				        }
				        else {
				        	$response = $this->getHandshake($buffer);
				        	socket_write($s, $response, strlen($response));
				        	$this->debug('Handshake');
				        	$this->debug($response);
				        	$user->setHandshaked();
				        }
					}
				}
			}
		}
				
	}
	
}
