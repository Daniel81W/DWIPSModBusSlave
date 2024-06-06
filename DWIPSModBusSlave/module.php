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
            $this->SendDebug("in", "Test", 0);
            $this->SendDebug("in", $JSONString, 0);
		}

		public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {


		}
		
    }
?>