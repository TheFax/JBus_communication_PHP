<?php

// http://sandbox.onlinephpfunctions.com/

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
   return $crc; //34 12
 }

function extractWord($jbus_frame, $word_number /*, $offset=0*/) {
  //Dato un vettore di dati ricevuto via JBUS, restituisco la word richiesta, considerando che i primi
  //tre byte di qualsiasi pacchetto JBUS sono dati "non interessanti" per noi.
  
  //TODO: controllare se il jbus_frame in analisi contiene una risposta negativa dell'host.
  
  $word_number=$word_number*2; //perchè si ragiona a 16 bit, non a 8 bit
  $word_number=$word_number+3; //perchè i primi tre byte fanno parte del protocollo JBUS
  return (ord($jbus_frame[$word_number])*255) + (ord($jbus_frame[$word_number+1]));
}


 $in = chr(0x01) . chr(0x03) .chr(0x14) .chr(0x50) .chr(0x00) .chr(0x30) .chr(0x40) .chr(0x3F); 
 
 $out = frameGeneratorRead(0x1450, 0x30);
echo "Stringa manuale: " . bin2hex($in);
echo "\n";
echo "Stringa calcolata: " . bin2hex($out);
echo "\n\n";

$splittata_rectifier = str_split($out);
print_r($splittata_rectifier);

if ($in===$out) {
    echo " e sono uguali.\n\n";
}

for ($i = 0; $i <= 1; $i++) { 
    //All'interno di questo ciclo for, assegno all'array tutte le misure del raddrizzatore
    $data['MA' . ($i) ]=extractWord($splittata_rectifier,$i);
  }
print_r($data);
