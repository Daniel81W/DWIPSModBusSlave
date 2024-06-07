<?php
    /** @noinspection PhpExpressionResultUnusedInspection */
    /** @noinspection PhpUnused */
    /** @noinspection PhpRedundantClosingTagInspection */
include_once(__DIR__ . "/../libs/ModBus_Type.php");
include_once(__DIR__ . "/../libs/IO_Datatype.php");
include_once(__DIR__ . "/../libs/Module_GUID.php");


use DWIPS\ModBus\libs\ModBus_Type;
use DWIPS\libs\IO_Datatype;
use DWIPS\libs\Module_GUID;


    class DWIPSModBusGateway extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();

            $this->RegisterPropertyInteger("ModbusType", ModBus_Type::ModBus_TCP);
            $this->RegisterPropertyInteger("DeviceID", 1);

            $this->RegisterAttributeString("TransIDsIP", json_encode(array()));
            $this->RegisterAttributeInteger("InternalTransIDCounter", 1);
		}

		/**
        * @return void
        */
        public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();

		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();

            switch ($this->ReadPropertyInteger("ModbusType")) {
                case ModBus_Type::ModBus_TCP:
                case ModBus_Type::ModBus_RTU_TCP:
                case ModBus_Type::ModBus_ASCII_TCP:
                $this->ForceParent(Module_GUID::Server_Socket);
                    break;
                case ModBus_Type::ModBus_UDP:
                case ModBus_Type::ModBus_RTU_UDP:
                case ModBus_Type::ModBus_ASCII_UDP:
                $this->ForceParent(Module_GUID::UDP_Socket);
                    break;
                case ModBus_Type::ModBus_RTU:
                case ModBus_Type::ModBus_ASCII:
                $this->ForceParent(Module_GUID::Serial_Port);
                    break;
                default:
                    break;
            }
		}


        public function GetConfigurationForm():string{
            return "";
        }

		/**
        * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
        * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wie folgt zur Verfügung gestellt:
        *
        * DWIPSShutter_UpdateSunrise($id);
        *
        */

		public function ReceiveData($JSONString) {

            $data = json_decode($JSONString, true);

            switch ($this->ReadPropertyInteger("ModbusType")) {
                case ModBus_Type::ModBus_TCP:
                    // Auf richtigen Datentyp prüfen, sonst abbrechen
                    if ($data["DataID"] != IO_Datatype::EXT_Socket_RX) {
                        $this->LogMessage("Empfangener Datentyp passt nicht zum Modbustypen", KL_ERROR);
                        return;
                    }
                    $this->ReceiveDataTCP($data, ModBus_Type::ModBus_TCP);
                    break;
                case ModBus_Type::ModBus_UDP:
                    // Auf richtigen Datentyp prüfen, sonst abbrechen
                    if ($data["DataID"] != IO_Datatype::EXT_UDP_RX) {
                        $this->LogMessage("Empfangener Datentyp passt nicht zum Modbustypen", KL_ERROR);
                        return;
                    }
                    $this->ReceiveDataTCP($data, ModBus_Type::ModBus_UDP);
                    break;
                case ModBus_Type::ModBus_RTU:
                    // Auf richtigen Datentyp prüfen, sonst abbrechen
                    if ($data["DataID"] != IO_Datatype::Simple_RX) {
                        $this->LogMessage("Empfangener Datentyp passt nicht zum Modbustypen", KL_ERROR);
                        return;
                    }
                    $this->ReceiveDataRTU($data, ModBus_Type::ModBus_RTU);
                    break;
                case ModBus_Type::ModBus_RTU_TCP:
                    $this->ReceiveDataRTUTCP($data);
                    break;
                case ModBus_Type::ModBus_RTU_UDP:
                    $this->ReceiveDataRTUUDP($data);
                    break;
                case ModBus_Type::ModBus_ASCII:
                    $this->ReceiveDataASCII($data);
                    break;
                case ModBus_Type::ModBus_ASCII_TCP:
                    $this->ReceiveDataASCIITCP($data);
                    break;
                case ModBus_Type::ModBus_ASCII_UDP:
                    $this->ReceiveDataASCIIUDP($data);
                    break;
                default:
                    break;
            }

		}

        private function ReceiveDataTCP(array $data, int $mbtype)
        {
            //IP-spezifische Daten auslesen
            $clientIP = $data['ClientIP'];
            $clientPort = $data['ClientPort'];
            //Buffer lesen und in hex wandeln
            $buffer = bin2hex(utf8_decode($data['Buffer']));
            //Bei Übertragung über TCP
            if ($mbtype == ModBus_Type::ModBus_TCP) {
                $tcptype = $data['Type'];
                //Daten im Debug ausgeben
                $this->SendDebug("Received TCP [" . $clientIP . ":" . $clientPort . "(Type:" . $tcptype . ")]", implode(' ', str_split($buffer, 2)), 0);
            } //Bei Übertragung über UDP
            elseif ($mbtype == ModBus_Type::ModBus_UDP) {
                $broadcast = boolval($data['Broadcast']);
                //Daten im Debug ausgeben
                $this->SendDebug("Received UDP [" . $clientIP . ":" . $clientPort . "(BC:" . $broadcast . ")]", implode(' ', str_split($buffer, 2)), 0);
            }

            //Aus Buffer den ModBusHeader auslesen
            $header = [
                'TransID' => hexdec(substr($buffer, 0, 4)), //ModBus-TransaktionsID - ersten 2 Byte
                'ProtoID' => hexdec(substr($buffer, 4, 4)), // ModBus-ProtokollID - Byte 3+4, immer 0x0000
                'Length' => hexdec(intval(substr($buffer, 8, 4))), //Länger der folgenden Daten (DeviceID, Functionscode und Daten) - Byte 5+6
                'DevID' => hexdec(substr($buffer, 12, 2)) //ID des abgefragten Gerätes - Byte 7
            ];
            //Body des Modbusframes
            $body = [
                'FC' => hexdec(substr($buffer, 14, 2)), //Funktionscode - 1 Byte
                'Data' => substr($buffer, 16, $header['Length'] * 2 - 4) //Eigentliche Daten - Länge: Length - 2 Byte
            ];

            //Prüfen ob Protokoll = 0x0000 und ob abgefragte DeviceID gleich der dieser Instanz
            if ($header['ProtoID'] == 0 && $header['DevID'] == $this->ReadPropertyInteger("DeviceID")) {
                //Prüfen ob es vom Absender schon eine ANfrage mit gleicher TransaktionsID gibt.
                $intTransID = $this->CheckForTransIDIP($clientIP, $clientPort, $header['TransID']);
                //Daten für ModbusDevice
                $data2send = [
                    'DataID' => IO_Datatype::DWIPS_MODBUS_RX,
                    'IntTransID' => $intTransID,
                    'Buffer' => $body
                ];
                //Daten JsonCodieren und an Device senden
                $this->SendDataToChildren(json_encode($data2send));
            }
        }



        private function ReceiveDataRTU($rtudata)
        {
            //Buffer lesen und in hex wandeln
            $buffer = bin2hex(utf8_decode($rtudata['Buffer']));

            //Aus Buffer den ModBusHeader auslesen

            $DevID = hexdec(substr($buffer, 0, 2)); //ID des abgefragten Gerätes - Byte 0

            //Body des Modbusframes
            $body = [
                'FC' => hexdec(substr($buffer, 2, 2)), //Funktionscode - 1 Byte
                'Data' => substr($buffer, 4, strlen($buffer) - 8) //Eigentliche Daten - Länge: -4 Byte
            ];
            if ($this->CheckCRC(substr($buffer, 0, strlen($buffer) - 4), substr($buffer, strlen($buffer) - 4, 4))) {
                //Daten für ModbusDevice
                $data2send = [
                    'DataID' => IO_Datatype::DWIPS_MODBUS_RX,
                    'IntTransID' => null,
                    'Buffer' => $body
                ];
                //Daten JsonCodieren und an Device senden
                $this->SendDataToChildren(json_encode($data2send));
            }
        }

        private function ReceiveDataRTUTCP($rtudata)
        {
        }

        private function ReceiveDataRTUUDP($rtudata)
        {
        }

        private function ReceiveDataASCII($asciidata)
        {
        }

        private function ReceiveDataASCIITCP($asciidata)
        {
        }

        private function ReceiveDataASCIIUDP($asciidata)
        {
        }

        public function ForwardData($JSONString)
        {
            $this->SendDebug("Slave", $JSONString, 0);
            $data = json_decode($JSONString, true);

            switch ($this->ReadPropertyInteger("ModbusType")) {
                case ModBus_Type::ModBus_TCP:
                    $this->ForwardDataTCP($data, ModBus_Type::ModBus_TCP);
                    break;
                case ModBus_Type::ModBus_UDP:
                    $this->ForwardDataTCP($data, ModBus_Type::ModBus_UDP);
                    break;
                case ModBus_Type::ModBus_RTU:
                    $this->ForwardDataRTU($data);
                    break;
                case ModBus_Type::ModBus_RTU_TCP:
                    $this->ForwardDataRTUTCP($data);
                    break;
                case ModBus_Type::ModBus_RTU_UDP:
                    $this->ForwardDataRTUUDP($data);
                    break;
                case ModBus_Type::ModBus_ASCII:
                    $this->ForwardDataASCII($data);
                    break;
                case ModBus_Type::ModBus_ASCII_TCP:
                    $this->ForwardDataASCIITCP($data);
                    break;
                case ModBus_Type::ModBus_ASCII_UDP:
                    $this->ForwardDataASCIIUDP($data);
                    break;
                default:
                    break;
            }

        }

        public function ForwardDataTCP(array $data, int $mbtype)
        {
            $intTransIDs_str = $this->ReadAttributeString("TransIDsIP");
            if ($intTransIDs_str == "") {
                $intTransIDs = [];
            } else {
                $intTransIDs = json_decode($intTransIDs_str, true);
            }
            $trans = $intTransIDs[$data['IntTransID']];
            $buf =
                sprintf('%04x', $trans['TransID']) .
                sprintf('%04x', 0) .
                sprintf('%04x', strlen($data['Buffer']['Data']) / 2 + 2) .
                sprintf('%02x', $this->ReadPropertyInteger("DeviceID")) .
                sprintf('%02x', $data['Buffer']['FC']) .
                $data['Buffer']['Data'];
            $data2send = [
                'Buffer' => utf8_encode(hex2bin($buf)),
                'ClientIP' => $trans['IP'],
                'ClientPort' => $trans['Port']
            ];
            if ($mbtype == ModBus_Type::ModBus_TCP) {
                $data2send['DataID'] = IO_Datatype::EXT_Socket_TX;
                $data2send['Type'] = 0;
                $this->SendDebug("Transmit TCP [" . $data2send['ClientIP'] . ":" . $data2send['ClientPort'] . "(Type:" . $data2send['Type'] . ")]", implode(' ', str_split($buf, 2)), 0);
            } elseif ($mbtype == ModBus_Type::ModBus_UDP) {
                $data2send['DataID'] = IO_Datatype::EXT_UDP_TX;
                $data2send['Broadcast'] = false;
                $this->SendDebug("Transmit UDP [" . $data2send['ClientIP'] . ":" . $data2send['ClientPort'] . "(BC:" . $data2send['Broadcast'] . ")]", implode(' ', str_split($buf, 2)), 0);
            }
            $this->SendDataToParent(json_encode($data2send));
        }

        /* public function ForwardDataUDP(array $data)
         {

             $intTransIDs_str = $this->ReadAttributeString("TransIDsIP");
             if ($intTransIDs_str == "") {
                 $intTransIDs = [];
             } else {
                 $intTransIDs = json_decode($intTransIDs_str, true);
             }
             $trans = $intTransIDs[$data['IntTransID']];
             $buf =
                 sprintf('%04x', $trans['TransID']) .
                 sprintf('%04x', 0) .
                 sprintf('%04x', strlen($data['Buffer']['Data']) / 2 + 2) .
                 sprintf('%02x', $this->ReadPropertyInteger("DeviceID")) .
                 sprintf('%02x', $data['Buffer']['FC']) .
                 $data['Buffer']['Data'];
             $data2send = [
                 'DataID' => IO_Datatype::EXT_UDP_TX,
                 'Buffer' => utf8_encode(hex2bin($buf)),
                 'ClientIP' => $trans['IP'],
                 'ClientPort' => $trans['Port'],
                 'Broadcast' => false
             ];
             $this->SendDebug("Transmit UDP [" . $data2send['ClientIP'] . ":" . $data2send['ClientPort'] . "(BC:" . $data2send['Broadcast'] . ")]", implode(' ', str_split($buf, 2)), 0);

             $this->SendDataToParent(json_encode($data2send));
         }*/

        public function ForwardDataRTU(array $data)
        {
            $buf =
                sprintf('%02x', $this->ReadPropertyInteger("DeviceID")) .
                sprintf('%02x', $data['Buffer']['FC']) .
                $data['Buffer']['Data'];
            $buf .= $this->GenerateCRC($buf);

            $data2send = [
                'DataID' => IO_Datatype::Simple_TX,
                'Buffer' => utf8_encode(hex2bin($buf))
            ];
            $this->SendDebug("Transmit RTU", implode(' ', str_split($buf, 2)), 0);

            $this->SendDataToParent(json_encode($data2send));
        }

        public function ForwardDataRTUTCP(array $data)
        {

        }

        public function ForwardDataRTUUDP(array $data)
        {

        }

        public function ForwardDataASCII(array $data)
        {

        }

        public function ForwardDataASCIITCP(array $data)
        {

        }

        public function ForwardDataASCIIUDP(array $data)
        {

        }

		public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {


		}

        private function CheckForTransIDIP(string $ip, int $port, int $transid)
        {
            $intTransIDs_str = $this->ReadAttributeString("TransIDsIP");
            if ($intTransIDs_str == "") {
                $intTransIDs = [];
            } else {
                $intTransIDs = json_decode($intTransIDs_str, true);
            }
            $intTransID = array_search(['IP' => $ip, 'Port' => $port, 'TransID' => $transid], $intTransIDs, true);
            if (!$intTransID) {
                $intTransID = $this->getNextIntTransID();
                $intTransIDs[$intTransID] = ['IP' => $ip, 'Port' => $port, 'TransID' => $transid];
                $this->WriteAttributeString("TransIDsIP", json_encode($intTransIDs));
            }
            return $intTransID;
        }

        private function getNextIntTransID(): int
        {
            $next = $this->ReadAttributeInteger("InternalTransIDCounter") + 1;
            $this->WriteAttributeInteger("InternalTransIDCounter", $next);
            return $next;
        }

        private function GenerateCRC(string $hexdata): string
        {
            $crc_reg = 0xffff;

            for ($i = 0; $i < strlen($hexdata); $i += 2) {
                $crc_reg = $crc_reg ^ hexdec("00" . substr($hexdata, $i, 2));

                for ($j = 0; $j < 8; $j++) {
                    if (($crc_reg & 0x0001) == 1) {
                        $crc_reg = $crc_reg >> 1;
                        $crc_reg = $crc_reg ^ 0xA001;
                    } else {
                        $crc_reg = $crc_reg >> 1;
                    }
                }
            }
            $crc = dechex($crc_reg);
            return substr($crc, 2, 2) . substr($crc, 0, 2);
        }

        private function CheckCRC(string $hexdata, string $crc): bool
        {
            return $this->GenerateCRC($hexdata) == $crc;
        }
    }
?>