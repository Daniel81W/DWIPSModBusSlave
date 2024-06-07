<?php
    /** @noinspection PhpExpressionResultUnusedInspection */
    /** @noinspection PhpUnused */
    /** @noinspection PhpRedundantClosingTagInspection */

    class DWIPSModBusGateway extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();

            $this->RegisterPropertyInteger("ModbusType", ModBusType::ModBus_TCP);
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
                case ModBusType::ModBus_TCP:
                case ModBusType::ModBus_RTU_TCP:
                case ModBusType::ModBus_ASCII_TCP:
                    $this->ForceParent("{8062CF2B-600E-41D6-AD4B-1BA66C32D6ED}");
                    break;
                case ModBusType::ModBus_UDP:
                case ModBusType::ModBus_RTU_UDP:
                case ModBusType::ModBus_ASCII_UDP:
                    $this->ForceParent("{82347F20-F541-41E1-AC5B-A636FD3AE2D8}");
                    break;
                case ModBusType::ModBus_RTU:
                case ModBusType::ModBus_ASCII:
                    $this->ForceParent("{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}");
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
                case ModBusType::ModBus_TCP:
                    $this->ReceiveDataTCP($data);
                    break;
                case ModBusType::ModBus_UDP:
                    $this->ReceiveDataUDP($data);
                    break;
                case ModBusType::ModBus_RTU:
                    $this->ReceiveDataRTU($data);
                    break;
                case ModBusType::ModBus_RTU_TCP:
                    $this->ReceiveDataRTUTCP($data);
                    break;
                case ModBusType::ModBus_RTU_UDP:
                    $this->ReceiveDataRTUUDP($data);
                    break;
                case ModBusType::ModBus_ASCII:
                    $this->ReceiveDataASCII($data);
                    break;
                case ModBusType::ModBus_ASCII_TCP:
                    $this->ReceiveDataASCIITCP($data);
                    break;
                case ModBusType::ModBus_ASCII_UDP:
                    $this->ReceiveDataASCIIUDP($data);
                    break;
                default:
                    break;
            }

		}

        private function ReceiveDataTCP($tcpdata)
        {
        }

        private function ReceiveDataUDP(array $udpdata)
        {
            // Auf richtigen Datentyp prüfen, sonst abbrechen
            if ($udpdata["DataID"] != "{9082C662-7864-D5CA-863F-53999200D897}") {
                $this->LogMessage("Empfangener Datentyp passt nicht zum Modbustypen", KL_ERROR);
                return;
            }
            //UDP-spezifische Daten auslesen
            $clientIP = $udpdata['ClientIP'];
            $clientPort = $udpdata['ClientPort'];
            $broadcast = boolval($udpdata['Broadcast']);
            //Buffer lesen und in hex wandeln
            $buffer = bin2hex($udpdata['Buffer']);
            //Daten im Debug ausgeben
            $this->SendDebug("Received UDP [" . $clientIP . ":" . $clientPort . "(BC:" . $broadcast . ")]", $buffer, 0);

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
                    'DataID' => '{CF28C131-AE67-4DE9-7749-D95E8DC7FCAB}',
                    'IntTransID' => $intTransID,
                    'Buffer' => $body
                ];
                //Daten JsonCodieren und an Device senden
                $this->SendDataToChildren(json_encode($data2send));
            }
        }

        private function ReceiveDataRTU($rtudata)
        {
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
                case ModBusType::ModBus_TCP:
                    $this->ForwardDataTCP($data);
                    break;
                case ModBusType::ModBus_UDP:
                    $this->ForwardDataUDP($data);
                    break;
                case ModBusType::ModBus_RTU:
                    $this->ForwardDataRTU($data);
                    break;
                case ModBusType::ModBus_RTU_TCP:
                    $this->ForwardDataRTUTCP($data);
                    break;
                case ModBusType::ModBus_RTU_UDP:
                    $this->ForwardDataRTUUDP($data);
                    break;
                case ModBusType::ModBus_ASCII:
                    $this->ForwardDataASCII($data);
                    break;
                case ModBusType::ModBus_ASCII_TCP:
                    $this->ForwardDataASCIITCP($data);
                    break;
                case ModBusType::ModBus_ASCII_UDP:
                    $this->ForwardDataASCIIUDP($data);
                    break;
                default:
                    break;
            }

        }

        public function ForwardDataTCP($data)
        {

        }

        public function ForwardDataUDP(array $data)
        {

            $intTransIDs_str = $this->ReadAttributeString("TransIDsIP");
            if ($intTransIDs_str == "") {
                $intTransIDs = [];
            } else {
                $intTransIDs = json_decode($intTransIDs_str, true);
            }
            $trans = $intTransIDs[$data['IntTransID']];
            $buf =
                sprintf('%04X', $trans['TransID']) .
                sprintf('%04X', 0) .
                sprintf('%04X', strlen($data['Buffer']['Data']) / 2 + 2) .
                sprintf('%02X', $this->ReadPropertyInteger("DeviceID")) .
                sprintf('%02X', $data['Buffer']['FC']) .
                $data['Buffer']['Data'];
            $data2send = [
                'DataID' => '{8E4D9B23-E0F2-1E05-41D8-C21EA53B8706}',
                'Buffer' => hex2bin($buf),
                'ClientIP' => $trans['IP'],
                'ClientPort' => $trans['Port'],
                'Broadcast' => false
            ];
            $this->SendDebug('3', $trans['TransID'], 0);
            $this->SendDebug('4', dechex($trans['TransID']), 0);
            $this->SendDebug('5', $buf, 0);
            $this->SendDataToParent(json_encode($data2send));
        }

        public function ForwardDataRTU($data)
        {

        }

        public function ForwardDataRTUTCP($data)
        {

        }

        public function ForwardDataRTUUDP($data)
        {

        }

        public function ForwardDataASCII($data)
        {

        }

        public function ForwardDataASCIITCP($data)
        {

        }

        public function ForwardDataASCIIUDP($data)
        {

        }

		public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {


		}

        private function CheckForTransIDIP(string $ip, string $port, int $transid)
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
    }

class ModBusType
{
    const ModBus_TCP = 0;
    const ModBus_UDP = 1;
    const ModBus_RTU = 2;
    const ModBus_RTU_TCP = 3;
    const ModBus_RTU_UDP = 4;
    const ModBus_ASCII = 5;
    const ModBus_ASCII_TCP = 6;
    const ModBus_ASCII_UDP = 7;

}
?>