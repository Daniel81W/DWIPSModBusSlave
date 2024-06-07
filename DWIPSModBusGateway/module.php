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

        private function ReceiveDataUDP($udpdata)
        {
            if ($udpdata["DataID"] != "{9082C662-7864-D5CA-863F-53999200D897}") {
                return;
            }
            $clientIP = $udpdata['ClientIP'];
            $clientPort = $udpdata['ClientPort'];
            $broadcast = boolval($udpdata['Broadcast']);
            $buffer = bin2hex($udpdata['Buffer']);
            $this->SendDebug("Received UDP [" . $clientIP . ":" . $clientPort . "(BC:" . $broadcast . ")]", $buffer, 0);

            $header = [
                'TransID' => hexdec(substr($buffer, 0, 4)),
                'ProtoID' => hexdec(substr($buffer, 4, 4)),
                'Length' => hexdec(intval(substr($buffer, 8, 4))),
                'DevID' => hexdec(substr($buffer, 12, 2))
            ];
            $body = [
                'FC' => hexdec(substr($buffer, 14, 2)),
                'Data' => substr($buffer, 16, $header['Length'] * 2 - 4)
            ];

            if ($header['ProtoID'] == 0 && $header['DevID'] == $this->ReadPropertyInteger("DeviceID")) {
                $intTransID = $this->CheckForTransIDIP($clientIP, $clientPort, $header['TransID']);
                $data2send = [
                    'DataID' => '{CF28C131-AE67-4DE9-7749-D95E8DC7FCAB}',
                    'IntTransID' => $intTransID,
                    'Buffer' => $body
                ];
                $d2sStr = json_encode($data2send);
                $this->SendDebug('Test', $d2sStr, 0);
                $this->SendDataToChildren($d2sStr);
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
            $fdata = json_decode($JSONString, true);

            $intTransIDs_str = $this->ReadAttributeString("TransIDsIP");
            $this->SendDebug("1", $intTransIDs_str, 0);
            if ($intTransIDs_str == "") {
                $intTransIDs = [];
            } else {
                $intTransIDs = json_decode($intTransIDs_str, true);
            }
            $this->SendDebug("2", print_r($intTransIDs, true), 0);
            $trans = $intTransIDs_str[$fdata['IntTransID']];
            $this->SendDebug("3", $trans, 0);
            $d2s = [
                'DataID' => '{8E4D9B23-E0F2-1E05-41D8-C21EA53B8706}',
                'Buffer' => '',
                'ClientIP' => $trans['IP'],
                'ClientPort' => $trans['Port'],
                'Broadcast' => false
            ];
            $this->SendDebug('4', dechex($fdata['FC']), 0);
            //$this->SendDataToParent(json_encode());
        }
		public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {


		}

        private function CheckForTransIDIP($ip, $port, $transid)
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