<?php
function extractWord($jbus_frame, $word_number /*, $offset=0*/) {
  //Dato un vettore di dati ricevuto via JBUS, restituisco la word richiesta, considerando che i primi
  //tre byte di qualsiasi pacchetto JBUS sono dati "non interessanti" per noi.
  
  //TODO: controllare se il jbus_frame in analisi contiene una risposta negativa dell'host.
  
  $word_number=$word_number*2; //perchè si ragiona a 16 bit, non a 8 bit
  $word_number=$word_number+3; //perchè i primi tre byte fanno parte del protocollo JBUS
  return (ord($jbus_frame[$word_number])*255) + (ord($jbus_frame[$word_number+1]));
}
function frameGeneratorRead($address, $quantity=1, $node=1) {
  $frame = '';
  //Slave number / Function READ / Address High / Address Low / 0 / Nb of word to read / CRC low / CRC high
  $frame = chr($node & 0xFF);
  $frame .= chr(0x03);
  $frame .= chr($address >> 8 & 0xFF) . chr($address & 0xFF);
  $frame .= chr(0x00);
  $frame .= chr($quantity & 0xFF);
  $checksum = crc16($frame);
  $frame = $frame . chr($checksum & 0xFF) . chr($checksum >> 8 & 0xFF); 
  return $frame;
}
function frameGeneratorCommandWrite($address, $data, $node=1) {
  $frame = '';
  $frame = chr($node & 0xFF) . chr(0x06) . chr($address >> 8 & 0xFF) . chr($address & 0xFF) . chr($data >> 8 & 0xFF) . chr($data & 0xFF) ;
  $checksum = crc16($frame);
  $frame = $frame . chr($checksum & 0xFF) . chr($checksum >> 8 & 0xFF); 
  return $frame;
}
function frameGeneratorDataWrite($address, $data, $node=1) {
  //Si scrivono word, quindi $data deve essere di lunghezza pari.
  $frame = '';
  //Slave n. / 0x10 / First Addr. High / First Addr. Low / 0x00 / N. of word / N. of bytes / Data to write High / Data to write Low / ... / ... / CRC low / CRC high
  $frame  = chr($node & 0xFF); 
  $frame .= chr(0x10);
  $frame .= chr($address >> 8 & 0xFF) . chr($address & 0xFF);
  $frame .= chr(0x00);
  $frame .= chr(strlen($data)/2) . chr(strlen($data));
  $frame .= $data;
  $checksum = crc16($frame);
  $frame = $frame . chr($checksum & 0xFF) . chr($checksum >> 8 & 0xFF); 
  return $frame;
}
function jbusResponseCheck($question_binary_frame, $answer_binary_frame) {
  $question = bin2hex($question_binary_frame);
  $answer   = bin2hex($answer_binary_frame);
  
  $question = substr($question, 0, 12);
  $answer   = substr($answer  , 0, 12);
  
  if ($question == $answer) return 1; //Risposta corretta
  
  return 0;  //Risposta sbagliata
}
function crc16($data)
 {
   $crc = 0xFFFF;
   for ($i = 0; $i < strlen($data); $i++)
   {
     //$x = (($crc >> 8) ^ ord($data[$i])) & 0xFF;
     $crc = $crc ^ (ord($data[$i]) & 0xFF);
     for ( $n = 1 ; $n <= 8 ; $n++) {
       if (($crc % 2) == 0) {
         $crc >>= 1;
       } else {
         $crc >>= 1;
         $crc ^= 0xA001;
       }
     }
   }
   return $crc; //una word, due byte.
 }
 
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
  socket_close($socket) or die("Impossibile chiudere la connessione socket (7)");
}

 
?>
