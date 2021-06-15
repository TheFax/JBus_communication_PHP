<?php
/*
# --------------------------------------------------------------------------------- #
# JBUS Class for PHP
#
# Descrizione: A class for JBUS communication via TCP/IP, in PHP
#
# Public functions:
#	extractWordFromFrame
#   frameGeneratorRead
#   frameGeneratorCommandWrite
#   frameGeneratorDataWrite
#   jbusResponseFrameCheck
#   communicationOpen($host, $port=1025)
#   communicationSend($data)
#   communicationClose()
#
# Data primo sviluppo: 15/06/2021
# Autore: FXO
# eMail: f****.****a@s*****c.com
#
# Versioni successive:
# Ver   Data     Descrizione
# --------------------------------------------------------------------------------- #
*/

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

class jbus {

	var $socket;
	
	public $frame_generated;
	public $frame_received;
	public $answer;
	public $error;
	public $ip;
	//$this->name = $name;
	//return $this->name;

	function extractWordFromFrame($jbus_frame, $word_number) {
		//Dato un vettore di dati ricevuto via JBUS, restituisco la word richiesta, considerando che i primi
		//tre byte di qualsiasi pacchetto JBUS sono dati "non interessanti" per noi.
				
		//strlen($jbus_frame)
		//ord($jbus_frame)
		//$jbus_frame[]
		
		// echo getType($jbus_frame); ---> restituisce "string"
		
		$this->error = "";

		$word_number = $word_number*2; //perchè si ragiona a 16 bit, non a 8 bit
		$word_number = $word_number+3; //perchè i primi tre byte fanno parte del protocollo

		
		// Verificho che il frame che mi hai dato non contenga una risposta negativa da parte dell'host
		if ( ord($jbus_frame[1]) & 0x80 ) {
			//Risposta negativa da parte dell'host
			$this->error = "ERR - Risposta negativa da parte dell'host.";
		    return false;
		}
		
		// Verifico che il frame che mi hai abbia una lunghezza sufficiente a contenere la word richiesta
		$minimum_lenght = $word_number+1+2; //+1 perchè è il secondo byte della word, +2 perchè deve esserci almeno anche il checksum
		if ( strlen($jbus_frame) < $minimum_lenght ) {
			//La risposta non è inclusa nel jbus_frame
			$this->error = "ERR - Frame JBUS non contiene la word richiesta.";
		    return false;
		}
		
		// Verifico che il frame che mi hai dato abbia il checksum corretto
		$frame_senza_checksum = substr ( $jbus_frame , 0 , strlen($jbus_frame)-2 );
		$checksum = $this->crc16($frame_senza_checksum);
		$frame_con_checksum = $frame_senza_checksum . chr($checksum & 0xFF) . chr($checksum >> 8 & 0xFF); 
		if ( $frame_con_checksum != $jbus_frame ) {
			//Checksum errato nel frame in analisi
			$this->error = "ERR - Checksum errato nel frame JBUS.";
		    return false;
		}
	    
		return (ord($jbus_frame[$word_number])*255) + (ord($jbus_frame[$word_number+1]));
	}
	
	function frameGeneratorRead($address, $quantity, $node=1) {
		// Slave number (node)
		// Function READ (0x03)
		// Address High
		// Address Low
		// 0
		// Number of word to read
		// CRC low
		// CRC high
		
		$frame = '';
		$frame = chr($node & 0xFF);
		$frame .= chr(0x03);
		$frame .= chr($address >> 8 & 0xFF) . chr($address & 0xFF);
		$frame .= chr(0x00);
		$frame .= chr($quantity & 0xFF);
		$checksum = $this->crc16($frame);
		$frame = $frame . chr($checksum & 0xFF) . chr($checksum >> 8 & 0xFF); 
		return $frame;
	}
	
	function frameGeneratorCommandWrite($address, $data, $node=1) {
		$frame  = '';
		$frame  = chr($node & 0xFF);
		$frame .= chr(0x06);
		$frame .= chr($address >> 8 & 0xFF) . chr($address & 0xFF);
		$frame .= chr($data >> 8 & 0xFF) . chr($data & 0xFF);
		$checksum = $this->crc16($frame);
		$frame = $frame . chr($checksum & 0xFF) . chr($checksum >> 8 & 0xFF); 
		return $frame;
	}
	
	function frameGeneratorDataWrite($address, $data, $node=1) {
		//TODO: Si scrivono word, quindi $data deve essere di lunghezza pari.
		//      Verificare che effettivamente $data sia di lunghezza pari.
		
		// Slave number (node)
		// 0x10
		// First Addr. High
		// First Addr. Low
		// 0x00
		// N. of word
		// N. of bytes
		// Data to write High
		// Data to write Low
		// ...
		// ...
		// CRC low
		// CRC high

		$frame  = '';
		$frame  = chr($node & 0xFF); 
		$frame .= chr(0x10);
		$frame .= chr($address >> 8 & 0xFF) . chr($address & 0xFF);
		$frame .= chr(0x00);
		$frame .= chr(strlen($data)/2) . chr(strlen($data));
		$frame .= $data;
		$checksum = $this->crc16($frame);
		$frame = $frame . chr($checksum & 0xFF) . chr($checksum >> 8 & 0xFF); 
		return $frame;
	}

	function jbusResponseFrameCheck($question_binary_frame, $answer_binary_frame) {
		//TODO: verificare checksum
		
		$question = bin2hex($question_binary_frame);
		$answer   = bin2hex($answer_binary_frame);

		$question = substr($question, 0, 12);
		$answer   = substr($answer  , 0, 12);

		if ($question == $answer)
			return 1; //Risposta corretta
		else
			return 0;  //Risposta sbagliata
	}

	function crc16($data) {
		$crc = 0xFFFF;
		for ($i = 0; $i < strlen($data); $i++) {
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
	 
	function communicationOpen($host, $port=1025) {
		//Create a TCP/IP socket
		/*http://php.net/manual/en/function.socket-create.php*/
		$this->socket = socket_create(AF_INET, SOCK_STREAM, 0);
		
		if ($this->socket === false) {
			$this->error = 'ERR - Impossibile creare una connessione socket';
			return false;
		}

		socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 1, 'usec' => 500));
		socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 500));

		$result = socket_set_nonblock($this->socket);
		if ($result === false) {
			$this->error = 'ERR - Impossibile impostare la connessione socket come non bloccante';
			return false;
		}
		
		// Attempting to connect
		// $result = @socket_connect($this->socket, $host, $port) or die("Could not connect socket (2)"); 
		$timeout = 2;
		$time = time();

		// loop until a connection is gained or timeout reached
		while (!@socket_connect($this->socket, $host, $port)) {
			$err = socket_last_error($this->socket);

			if($err === 56 || $err ===10056) {
				// Success!
				//TODO: controllare error codes e vedere che cosa vuol dire 56 e 10056
				break;
			}

			// if timeout reaches then call exit();
			if ((time() - $time) >= $timeout) {
				socket_close($this->socket);
				$this->error = 'ERR - Timeout nella creazione della comunicazione TCP';
				return false;
			}

			// sleep for a bit
			usleep(100000); //0.1 secondi
		}

		$result = socket_set_block($this->socket);
		if ($result === false) {
			$this->error = 'ERR - Impossibile impostare la connessione socket come bloccante';
			return false;
		}
		
		$this->ip = $host;
		
		return true;
	}

	function communicationSend($data) {
		$this->error = '';
		$lenght = strlen($data);
		
		//Sending request
        $sent = socket_write($this->socket, $data, $lenght);

		if($sent === false) {
			//Frame non spedito
			$this->error = 'ERR - Frame non spedito';
			return false;
		} elseif ($sent < $lenght) {
			//Frame troncato e non spedito completamente
			$this->error = 'ERR - Frame troncato e non interamente spedito';
			return false;
		}

		//Reading response
		//Riceverò una risposta Jbus che salverò dentro la variabile $answer
		$this->answer = socket_read($this->socket, 2048);

		if($this->answer === false) {
			//Qualcosa è andato storto nella ricezione
			$this->error = 'ERR - Errore durante la ricezione della risposta';
			return false;
		}
		
		return true;
	}

	function communicationClose() {
		//Close the socket

		$status = socket_shutdown($this->socket,2);
		
		if ($status === false) {
			$this->error = 'ERR - Impossibile disabilitare il socket';
			return false;
		}
		
		socket_close($this->socket);
		
		return true;
	}

}  //end class


//Example:

$simulator = new jbus();

$simulator->communicationOpen("172.17.9.173");

$indirizzo_JBUS = 0x1060;
$quantita_JBUS = 0x30;

$myframe = $simulator->frameGeneratorRead($indirizzo_JBUS, $quantita_JBUS, 1);

$outcome = $simulator->communicationSend($myframe);

if ($outcome == false) {
	echo "Fail durante la comunicazione.<br>";
	echo $simulator->error;
} else {
	echo "Richiesta eseguita correttamente.<br>";
	echo "Indirizzo: ". dechex($indirizzo_JBUS) ."<br>";
	echo "Quantità: ". dechex($quantita_JBUS) ."<br>";
	echo "TX: " . bin2hex($myframe)."<br>";
	echo "RX: " . bin2hex($simulator->answer)."<br>";
	echo "<br>";
}

for ($i = 1; $i<$quantita_JBUS ; $i++) {
	echo "ID vettore: " . $i . " - Valore: " . (  $simulator->extractWordFromFrame($simulator->answer, $i)) . "<br>";
    echo $simulator->error;
}


?>
