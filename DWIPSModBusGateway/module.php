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

            switch ($data["DataID"]) {
                case "{9082C662-7864-D5CA-863F-53999200D897}":
                    $this->ReceiveDataUDP($data);
                    break;
                default:
                    break;
            }
		}

        private function ReceiveDataUDP($udpdata)
        {
            $clientIP = $udpdata['ClientIP'];
            $clientPort = $udpdata['ClientPort'];
            $broadcast = boolval($udpdata['Broadcast']);
            $buffer = bin2hex($udpdata['Buffer']);
            $this->SendDebug("Received UDP [" . $clientIP . ":" . $clientPort . "(BC:" . $broadcast . ")]", $buffer, 0);

            $d = [
                'TransID' => hexdec(substr($buffer, 0, 4)),
                'ProtoID' => substr($buffer, 4, 4),
                'Length' => hexdec(intval(substr($buffer, 8, 4)))
            ];
            $this->SendDebug("TransID", $d['TransID'], 0);
        }

		public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {


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