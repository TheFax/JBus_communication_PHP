<?php

//TX | 01 03 05 00 00 2A C4 D9 

//echo dechex(crc16( "\x01\x03\x05\x00\x00\x2A" ) & 0xFF).' '.dechex(crc16( "\x01\x03\x05\x00\x00\x2A" )>>8 & 0xFF) . '<br>'; 
//echo bin2hex(frameGeneratorRead(1280,42,1));

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
 
?>
