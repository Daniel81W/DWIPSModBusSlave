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

    class DWIPSModBusSlave extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();
            $this->ForceParent(Module_GUID::DWIPS_ModBus_Gateway);

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
            $this->SendDebug("in", $JSONString, 0);

            $mbdata = json_decode($JSONString, true);

            if ($mbdata["DataID"] != IO_Datatype::DWIPS_MODBUS_RX) {
                return;
            }
            $intTransID = $mbdata['IntTransID'];
            $devID = $mbdata['Buffer']['DevID'];
            $fc = $mbdata['Buffer']['FC'];
            $data = $mbdata['Buffer']['Data'];

            $retDat = [
                "DataID" => IO_Datatype::DWIPS_MODBUS_TX,
                'IntTransID' => $intTransID,
                'Buffer' => ['DevID' => $devID, 'FC' => $fc, 'Data' => '02001a']
            ];
            switch ($fc) {
                case 3:
                    $this->SendDataToParent(json_encode($retDat));
                    break;
                default:
                    break;
            }
        }

		public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {


		}
		
    }
?>