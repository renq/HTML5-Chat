<?php

date_default_timezone_set('Europe/Warsaw');

$address = 'localhost';
$port = 12345;

$websocket = new WebSocket($address, $port);


$socket = $websocket->getSocket();

$users = array();

while(true){
	$changed = $websocket->getSockets();
	socket_select($changed, $write=NULL, $except=NULL, NULL);
	print_r($changed);
	foreach($changed as $s){
		if ($s == $socket) {
			$client = socket_accept($s);
			if ($client <0 ) {
				echo "socket_accept() failed";
				continue;
			}
			else {
				echo "socket_accept()...";
				echo $client;
				echo "\n";
				$websocket->addSocket($client);
				$users[(string)$client] = new User($client);
				print_r($users); 
			}
		}
		else{
			$bytes = socket_recv($s, $buffer, 2048, 0);
			if ($bytes == 0) {
				echo 'Disconnect: ' . $s;
				$websocket->removeSocket($s);
			}
			else{
		        echo $buffer;
		        $user = $users[(string)$s];
		        
		        if ($user->getHandshaked()) {
		        	//socket_write($s, $buffer, strlen($buffer));
		        	foreach ($websocket->getSockets() as $userSocket) {
		        		$response = "$buffer\0";
		        		socket_write($userSocket, $response, strlen($response));
		        	}
		        }
		        else {
		        	$response = $websocket->getHandshake($buffer);
		        	socket_write($s, $response, strlen($response));
		        	$user->setHandshaked();
		        }
			}
		}
	}
}



function parseHeaders($headers) {
	$arr = explode("\r\n", $headers);
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
	return array(
		'method' => $method,
		'resource' => $methodArr[1],
		'params' => $params,
		'msg' => array_pop($arr),
	);
}


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



class WebSocket {
	
	private $sockets = array();
	private $socket;
	private $address;
	private $port;
	
	
	public function __construct($address, $port) {
		$this->address = $address;
		$this->port = $port;
		
		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($this->socket, $address, $port);
		socket_listen($this->socket, 1);
		
		$this->addSocket($this->socket);	
		
		echo "Server created : ".date('Y-m-d H:i:s')."\n";
		echo "Socket         : ".$this->socket."\n";
		echo "Listening on   : ".$this->address." port ".$this->port."\n\n";
				
	}
	
	
	public function getHandshake($buffer) {
	    $parsed = parseHeaders($buffer);
        $params = $parsed['params'];
        if (isset($params['Sec-WebSocket-Key'])) {
        	echo "draft-ietf-hybi-thewebsocketprotocol-06\n";
	        echo $acceptKey = base64_encode(sha1($params['Sec-WebSocket-Key'] . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true));
	        echo "\n";
		        
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
			$hashData = md5( pack("N", $numkey1/$spaces1) . pack("N", $numkey2/$spaces2) . $parsed['msg'], true);
        	
			$response = "HTTP/1.1 101 WebSocket Protocol Handshake\r\n";
			$response .= "Upgrade: WebSocket\r\n";
			$response .= "Connection: Upgrade\r\n";
			$response .= "Sec-WebSocket-Origin: " . $params['Origin'] . "\r\n";
			$response .= "Sec-WebSocket-Location: ws://" . "{$this->address}:{$this->port}" . $parsed['resource'] . "\r\n";
			$response .= "\r\n";
			$response .= $hashData;
			$response .= "\0";
			
			echo "--------------\n\n$response";
        }
        return $response;
	}
	
	
	public function getSocket() {
		return $this->socket;
	}
	
	
	public function addSocket($socket) {
		$this->sockets[] = $socket;
	}
	
	
	public function removeSocket($socket) {
		$key = array_search($socket, $this->sockets, true);
		unset($this->sockets[$key]);
	}
	
	
	public function getSockets() {
		return $this->sockets;
	}
	
}


