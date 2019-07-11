<?php
function jbusSocketOpen($host, $port) {
//Create a TCP/IP socket
  /*http://php.net/manual/en/function.socket-create.php*/
  $socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Impossibile creare una connessione socket (1)"); 
  socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 1, 'usec' => 500));
  socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 500));

  socket_set_nonblock($socket) or die("Impossibile impostare la connessione socket come non bloccante (2)");
//Attempting to connect
//  $result = @socket_connect($socket, $host, $port) or die("Could not connect socket (2)"); 
  $timeout = 2;
  $time = time();

  // loop until a connection is gained or timeout reached
  while (!@socket_connect($socket, $host, $port)) {
    $err = socket_last_error($socket);

    // success!
    if($err === 56 || $err ===10056) {
        break;
    }

    // if timeout reaches then call exit();
    if ((time() - $time) >= $timeout) {
        socket_close($socket);
        die("Timeout nella comunicazione ethernet (3)");
    }

    // sleep for a bit
    usleep(250000);
}
socket_set_block($socket) or die("Impossibile impostare la connessione come bloccante (4)");

  return $socket;
}

function jbusSocketCommunication($socket, $data) {
//Sending request
  socket_write($socket, $data, strlen($data)) or die("Impossibile spedire dati sulla connessione di rete (5)");
  
//Reading response
  //Riceverò una risposta Jbus che salverò dentro la variabile $out
  $out = '';
  $out = socket_read($socket, 2048) or die("Impossibile ricevere dati dalla connessione di rete (6)");

  return $out;
}

function jbusSocketClose($socket) {
//Close the socket
  sleep (1);
  socket_shutdown($socket,2) or die("Impossibile spegnere socket (7)");
  socket_close($socket);
}

?>
