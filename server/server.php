<?php

require 'websocket/WebSocket.php';
require 'websocket/Header.php';
require 'websocket/User.php';

use ml\websocket\WebSocket;
use ml\websocket\Header;
use ml\websocket\User;


date_default_timezone_set('Europe/Warsaw');

$address = 'localhost';
$port = 12345;


$websocket = new WebSocket($address, $port);
$websocket->run();


