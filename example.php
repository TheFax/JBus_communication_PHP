<?php

require_once("jbus.php");
require_once("jsocket.php");

$esito = "OK - comando eseguito";

$simulator_socket=jbusSocketOpen($_GET["ip"], 1025);

$unlock_packet =  frameGeneratorDataWrite(0x2692, hex2bin("386E8A0B"));
$response = jbusSocketCommunication($simulator_socket, $unlock_packet);
if (jbusResponseCheck($unlock_packet, $response) == 0) $esito = "STOP! Non eseguito";


$unlock_packet =  frameGeneratorDataWrite(0x1F12, hex2bin("386E8A0B"));
$response = jbusSocketCommunication($simulator_socket, $unlock_packet);
if (jbusResponseCheck($unlock_packet, $response) == 0) $esito = "STOP! Non eseguito";


if ($_GET["command"] == "poweroff") {
  //Spengo simulatore
  $packet = frameGeneratorCommandWrite(0x25D9, 0x0080);
}

if ($_GET["command"] == "poweron") {
  //Accendo simulatore
  $packet = frameGeneratorCommandWrite(0x25D8, 0x0002);
}

if ($_GET["command"] == "set") {
//Imposto simulatore con tensione e corrente date via $_GET
//Costruisco il pacchetto:
  $voltage = $_GET["voltage"];
  $current = $_GET["current"];

  if ( !is_numeric($voltage) ) die("Parametro tensione non numerico.");
  if ( !is_numeric($current) ) die("Parametro corrente non numerico.");
  if ( strlen($voltage)>3 ) die("Parametro tensione troppo lungo.");
  if ( strlen($current)>3 ) die("Parametro corrente troppo lungo.");
  if ( $voltage < 100 ) die("Parametro tensione troppo piccolo.");
  if ( $voltage > 700 ) die("Parametro tensione troppo alto.");
  if ( $current < 8  ) die("Parametro corrente troppo basso.");
  if ( $current > 50  ) die("Parametro corrente troppo alto.");

  $voltage = $voltage * 10;
  $current = $current * 10;

  $data_frame = "";
  $data_frame .= hex2bin("0000"); //CmdAte
  $data_frame .= hex2bin("0001"); //PV_Logic
  $data_frame .= hex2bin("09C4"); //V_DC
  $data_frame .= chr($current >> 8 & 0xFF) . chr($current & 0xFF); //I_ShortCircuit
  $data_frame .= chr($voltage >> 8 & 0xFF) . chr($voltage & 0xFF); //Voc_Max_x_pannel
  $data_frame .= hex2bin("0001"); //Num_of_pannels_in_string
  $data_frame .= hex2bin("0001"); //uiDwell_time
  $data_frame .= hex2bin("03e8"); //Irradiance
	
  $packet = frameGeneratorDataWrite(0x2770, $data_frame);
  $esito = "Settato per: \n " . $_GET["voltage"] . " Volt\n " . $_GET["current"] . " Ampere";
}

$response = jbusSocketCommunication($simulator_socket, $packet);
if (jbusResponseCheck($packet, $response) == 0) $esito = "STOP! Non eseguito";

jbusSocketClose($simulator_socket);

echo $esito;




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
