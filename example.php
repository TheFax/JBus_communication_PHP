/* Create a TCP/IP socket. */
  /*http://php.net/manual/en/function.socket-create.php*/
  $socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket<br>"); 
  socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 1, 'usec' => 500));
  socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 500));

//echo "Attempting to connect...";
  $result = @socket_connect($socket, $host, $port) or die("Could not connect socket<br>"); 
  
  //echo "Sending request... ";
  //Richiedo 48 byte (=0x30) partendo dall'indirizzo 0x1450;
  //Il frame diventerà questo: chr(0x01) . chr(0x03) .chr(0x14) .chr(0x50) .chr(0x00) .chr(0x30) .chr(0x40) .chr(0x3F);
  $in = frameGeneratorRead(0x1450, 0x30); 
  socket_write($socket, $in, strlen($in)) or die("Could not write into the socket<br>");
  
  echo bin2hex($in);
  
//echo "Reading response:<br>";
  //Riceverò una risposta Jbus che salverò dentro la variabile $out
  $out = '';
  $out = socket_read($socket, 2048) or die("Could not read from socket<br>");
  
  echo bin2hex($out);
  
//Ora converto la risposta in un array
  $splittata_rectifier = str_split($out);
  
//In questo punto del programma ho a disposizione l'array $splittata_rectifier, che è a tutti gli effetti un array
//corrispondente byte-per-byte alla risposta Jbus.
  
//Ora estraggo ogni misura ricevuta e la salvo dentro un altro array
  $data = array();
  for ($i = 0; $i <= 47; $i++) {  //per 48 misure...
    //All'interno di questo ciclo for, assegno all'array tutte le misure del raddrizzatore
    $data['MA' . ($i) ]=extractWord($splittata_rectifier,$i);
  }
  
//In questo punto del programma avrò a disposizione tutte le misure effettuate
//dentro un array: $data['MA0'] ... $data['MA47']
