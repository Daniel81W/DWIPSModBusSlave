<?php
    /** @noinspection PhpExpressionResultUnusedInspection */
    /** @noinspection PhpUnused */
    /** @noinspection PhpRedundantClosingTagInspection */

    class DWIPSModBusSlave extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();
            $this->ForceParent("{8ADEF4EE-6E27-6035-46C6-32221029A20D}");

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

            if ($mbdata["DataID"] != "{CF28C131-AE67-4DE9-7749-D95E8DC7FCAB}") {
                return;
            }
            $intTransID = $mbdata['IntTransID'];
            $fc = $mbdata['Buffer']['FC'];
            $data = $mbdata['Buffer']['Data'];

            $retDat = [
                "DataID" => '{A590DFA2-E37C-CEA6-12C5-457C47323E4C}',
                'IntTransID' => $intTransID,
                'Buffer' => ['FC' => $fc, 'Data' => '02001a']
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