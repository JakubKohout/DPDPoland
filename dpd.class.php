<?php
/******************************************************************************\
*																			   *
* Version:  1.1 BETA                                                           *
* CreateDate:   2014-12-30                                                     *
* UpdateDate:   2015-08-25                                                     *
* Author:   OSKAR LEBUDA                                                       *
* License:  Freeware                                                           *
*                                                                              *
\******************************************************************************/

/******************************************************************************\
*																			   *
*		changelog:				                                               *
*		v 1.1:																   *
*																			   *
*			-Dodana metoda checkPackageStatus pozwalająca na 				   *
*			sprawdzenie statusu Paczki 										   *
*																			   *
*			-Dodana metoda Logs pozwalająca na generowanie					   *
*			loga z ostatniego zapytania SOAP 								   *
*                                                                              *
\******************************************************************************/



/**
	* klasa do obsługi webserwisu DPD - Objektowo
	* @author Oskar Lebuda (o.lebuda@gmail.com)
	* @version 1.1 BETA
*/

	

	class DPD{

		public $client;
		public $dClient;
		public $Logs = 'SOAP-Logs/';

		public function __construct(){
			$this->client = new SoapClient(__wsdl__,array('features' => SOAP_SINGLE_ELEMENT_ARRAYS));
			$this->dClient = new SoapClient('https://ucsweb.dpd.com.pl/UnisoftCustomerServices/Parcel.asmx?WSDL',array('features' => SOAP_SINGLE_ELEMENT_ARRAYS));
		}

		/**
			* Metoda ma za zadanie wyświetlić strukturę obiektu
		*/

		public function DebugCode($result){
			$aRes = @get_object_vars($result);
			echo '<h4>'.(isset($aRes['Method']) ? $aRes['Method'] : 'Result' ).'</h4>';
			echo '<hr />';
			echo '<xmp>';print_r($result);echo '</xmp>';
			echo '<hr />';
		}





		/**
			* Metoda ma za zadanie stworzyć ostatnio wysłany plik XML, który zapisuje w formie loga w przypadku błędnej walidacji zapytania
				* @param string -> string w formacie xml uzyskany podczas wywołania metody SOAP : __getLastRequest()
				* @return plik xml zapisany w folderze SOAP-Logs
		*/

		public function DegugLogs($string){
			$xml = simplexml_load_string($string);
			$dom = new DOMDocument('1.0'); 
			$dom->preserveWhiteSpace = false; 
			$dom->formatOutput = true; 
			$dom->loadXML($xml->asXML()); 
			$dom->save($this->Logs.date("Y-m-d H:i:s").'.xml');
		}



		/**
			* Metoda ma za zadanie przygotować nową paczkę
				* @param ParcelsArray -> Tablica paczek
				* @param Payer; -> String (Osoba, która będzie płaciła za usługę).
				* @param ReceiverArray -> Tablica z danymi odbiorcy.
				* @param Ref -> String(27) z dodatnowymi informacjami dla Kuriera
				* @param SenderArray -> Tablica z danymi nadawcy.
				* @param ServicesArray -> Tablica z dodatkowymi usługami.
				* @return object z danymi (Dane Nadawcy, PackageID, Tablica z danymi paczki/ek (ParcelID, Status, Numer Identyfikacyjny))
		*/

		public function generatePackagesNumbersV1($ParcelsArray, $Payer, $ReceiverArray, $SenderArray, $ServicesArray = '', $Ref = ''){
			if (!isset($ParcelsArray) || !isset($Payer) || !isset($ReceiverArray) || !isset($Ref) || !isset($SenderArray)) {
				throw new Exception("Brak wymaganych parametrow...", 1);
			}

			if(strlen($Ref) > 27){
				throw new Exception("Zawartosc pola REF nie moze przekraczac 27 znakow...", 1);
				
			}else{
				$Ref = str_split($Ref, 9);
			}

			$params=[
				'openUMLV1' => [
					'packages'=>[
						'parcels' => $ParcelsArray,
						'payerType' => $Payer,
						'receiver' => $ReceiverArray, 
						'ref1' => $Ref[0],
						'ref2' => isset($Ref[1]) ? $Ref[1] : '',
						'ref3' => isset($Ref[2]) ? $Ref[2] : '',
						'sender' => $SenderArray,
						'services' => $ServicesArray,
					],
				],
				'pkgNumsGenerationPolicyV1' => 'IGNORE_ERRORS',
				'authDataV1'=>[
					'login' => __user__,
					'masterFid' => __fid__,
					'password' => __password__,
				],
			];

			try{
				$result = $this->client->__soapCall('generatePackagesNumbersV1', array($params));

				$object = new stdClass;
				$object->Method = 'generatePackagesNumbersV1';
				$object->Sender = $SenderArray;
				$object->PackageID = $result->return->packages[0]->packageId;
				$object->Parcels = $result->return->packages[0]->parcels;

				return $object;

			}catch(SOAPFault $e){
				$this->DegugLogs($client->__getLastRequest());
				throw new Exception($e->getMessage(), 1);
			}

		}




		/**
			* Metoda ma za zadanie wygenerować etykietę w formacie PDF
				* @param SenderArray -> Tablica z danymi odbiorcy.
				* @param PackageID -> Int z Numerem przewozowym
				* @return objekt z Nazwą Metody Oraz BASE64 PDF
		*/

		public function generateSpedLabelsV1($SenderArray, $PackageID){
			if (!isset($SenderArray) || !isset($PackageID)) {
				throw new Exception("Brak wymaganych parametrow...", 1);
				
			}

			$params = [
				'dpdServicesParamsV1' => [
					'pickupAddress'=>$SenderArray,
					'policy'=>'STOP_ON_FIRST_ERROR',
					'session'=>[
						'packages'=>[
							'packageId'=>$PackageID,
						],
						'sessionType'=>'DOMESTIC',
					],
				],
				'outputDocFormatV1'=>'PDF',
				'outputDocPageFormatV1'=> 'A4',
				'authDataV1'=>[
					'login' => __user__,
					'masterFid' => __fid__,
					'password' => __password__,
				],
			];

			try{
				$result = $this->client->__soapCall('generateSpedLabelsV1', array($params));
				if ($result->return->session->statusInfo->status <> 'OK') {
					throw new Exception("Blad podczas tworzenia protokolu! \n Blad: ".$result->return->session->statusInfo->status, 1);
				}

				$object = new stdClass;
				$object->Method = 'generateSpedLabelsV1';
				$object->PDF = $result->return->documentData;

				return $object;

			}catch(SOAPFault $e){
				$this->DegugLogs($client->__getLastRequest());
				throw new Exception($e->getMessage(), 1);
			}
		}




		/**
			* Metoda ma za zadanie wygenerować Protokół przewoźnika
				* @param PackageIDArray -> Tablica kodów PackageID
				* @param FID -> FID oddziału, z którego zostaje generowany protokół
				* @return objekt z Nazwą Metody, NIE ZAKODOWANY PDF, DocumentID potrzebny do wezwania kuriera
		*/

		public function generateProtocolV1($PackageIDArray, $FID){
			if (!isset($PackageIDArray)) {
				throw new Exception("Brak wymaganych parametrow...", 1);
			}

			$aPackage = [];

			foreach ($PackageIDArray as $Package) {
				array_push($aPackage, array('packageId' => $Package));
			}
			$params = [
				'dpdServicesParamsV1'=>[
					'pickupAddress'=>[
						'fid'=>$FID,
					],
					'policy'=>'STOP_ON_FIRST_ERROR',
					'session'=>[
						'packages'=>$aPackage,
						'sessionType'=>'DOMESTIC',
					],
				],
				'outputDocFormatV1'=>'PDF',
				'outputDocPageFormatV1'=>'A4',
				'authDataV1'=>[
					'login' => __user__,
					'masterFid' => __fid__,
					'password' => __password__,
				],
			];

			try{
				$result = $this->client->__soapCall('generateProtocolV1', array($params));
				if ($result->return->session->statusInfo->status <> 'OK') {
					throw new Exception("Blad podczas tworzenia protokolu! \n Blad: ".$result->return->session->statusInfo->status, 1);
				}

				$object = new stdClass;
				$object->Method = 'generateProtocolV1';
				$object->PDF = $result->return->documentData;
				$object->DocumentID = $result->return->documentId;

				return $object;

			}catch(SOAPFault $e){
				$this->DegugLogs($client->__getLastRequest());
				throw new Exception($e->getMessage(), 1);
			}
		}




		/**
			* Metoda ma za zadanie dodać dodatkową parcele do paczki
				* @param PackageID -> kodów PackageID paczki do której ma zostać dodana parcela
				* @param Content -> zawartość paczki
				* @param Weight -> Waga paczki.
				* @param CustomerData -> String(27) z dodatkowymi informacjami o paczce.
				* @return objekt z Nazwą Metody oraz Numerem przewozowym Paczki
		*/

		public function appendParcelsToPackageV1($PackageID, $Content, $Weight, $CustomerData = ''){
			if (!isset($PackageID) || !isset($Content) || !isset($CustomerData) || !isset($Weight)) {
				throw new Exception("Brak wymaganych parametrow...", 1);
			}

			if(strlen($CustomerData) > 27){
				throw new Exception("Zawartosc pola CustomerData nie moze przekraczac 27 znakow...", 1);
				
			}else{
				$CustomerData = str_split($CustomerData, 9);
			}

			$params = [
				'parcelsAppend' =>[
					'packagesearchCriteria'=>[
						'packageId'=>$PackageID,
					],
					'parcels'=>[
						'content'=>$Content,
						'customerData1'=>$CustomerData[0],
						'customerData2'=>isset($CustomerData[1]) ? $CustomerData[1] : '',
						'customerData3'=>isset($CustomerData[2]) ? $CustomerData[2] : '',
						'weight' => $Weight,
					],
				],
				'authDataV1'=>[
					'login' => __user__,
					'masterFid' => __fid__,
					'password' => __password__,
				],
			];

			try{
				$result = $this->client->__soapCall('appendParcelsToPackageV1', array($params));
				if ($result->return->status <> 'OK') {
					throw new Exception("Blad podczas dodawania paczki.! \n Blad: ".$result->return->session->statusInfo->status, 1);
				}

				$object = new stdClass;
				$object->Method = 'appendParcelsToPackageV1';
				$object->wybill = $result->return->parcels[0]->waybill;

				return $object;

			}catch(SOAPFault $e){
				$this->DegugLogs($client->__getLastRequest());
				throw new Exception($e->getMessage(), 1);
			}

		}



		/**
			* Metoda ma za zadanie wezwać kuriera po odbiór paczek
				* @param FID -> FID oddziału, z którego zostaje generowany protokół
				* @param PickupDate -> Data odbioru przez kuriera w formacje RRRR-MM-DD
				* @param PickupTimeFrom -> Godzina, od której kurier może odbierać paczki w formacie 00:00
				* @param PickupTimeTo -> Godzina, do której kurier może odbierać paczki w formacie 00:00
				* @param DocumentID -> Numer dokumentu, na którym są umieszczone wszystkie paczki, które kurier ma zabrać (generowany podczas korzystania z metody generateProtocolV1)
				* @return objekt z Nazwą Metody, DocumentID oraz statusem zatwierdzenia.
		*/

		public function packagesPickupCallV1($FID, $PickupDate, $PickupTimeFrom, $PickupTimeTo, $DocumentID, $ContactArray = ''){
			if (!isset($FID) || !isset($PickupDate) || !isset($PickupTimeFrom) || !isset($PickupTimeTo) || !isset($DocumentID)) {
				throw new Exception("Brak wymaganych parametrow...", 1);
			}

			$date = DateTime::createFromFormat('Y-m-d', $PickupDate);
			if (!$date) {
				throw new Exception("Bledny format Daty...", 1);
			}

			$time1 = preg_match( '/^[1-2]{1}[0-9]{1}:[0-59]{2}$/', $PickupTimeFrom);
			$time2 = preg_match( '/^[1-2]{1}[0-9]{1}:[0-59]{2}$/', $PickupTimeTo);
			if (!$time1 || !$time2) {
				throw new Exception("Bledny format godziny...", 1);
			}

			$params = [
				'dpdPickupParamsV1' => [
					'contactInfo' => $ContactArray,
					'pickupAddress'=>[
						'fid' => $FID,
					],
					'pickupDate' => $PickupDate,
					'pickupTimeFrom'=> $PickupTimeFrom,
					'pickupTimeTo'=> $PickupTimeTo,
					'policy'=>'STOP_ON_FIRST_ERROR',
					'protocols'=>[
						'documentId'=>$DocumentID,
					],
				],
				'authDataV1'=>[
					'login' => __user__,
					'masterFid' => __fid__,
					'password' => __password__,
				],
			];
			try{
				$result = $this->client->__soapCall('packagesPickupCallV1', array($params));
				if ($result->return->prototocols->statusInfo->status <> 'OK') {
					throw new Exception("Blad podczas zamawiania kuriera! \n Blad: ".$result->return->session->statusInfo->status, 1);
				}

				$object = new stdClass;
				$object->Method = 'packagesPickupCallV1';
				$object->DocumentID = $result->return->prototocols->documentId;
				$object->Status = $result->return->prototocols->statusInfo->status;

				return $object;

			}catch(SOAPFault $e){
				$this->DegugLogs($client->__getLastRequest());
				throw new Exception($e->getMessage(), 1);
			}
		}





		/**
			* Metoda ma za zadanie sprawdzić poprawnośćk kody pocztowego
				* @param PostalCode - kod pocztowy do sprawdzenia
				* @return objekt z Nazwą Metody, Kodem pocztowym oraz Statusem, bądź błędem.
		*/

		public function findPostalCodeV1($PostalCode){
			if (!isset($PostalCode)) {
				throw new Exception("Brak wymaganych parametrow...", 1);
			}

			$check = preg_match( '/^[0-9]{2}-[0-9]{3}$/', $PostalCode);
			if (!$check) {
				throw new Exception("Bledny format kodu pocztowego. Wymagany format: 00-000...", 1);
			}

			$PostalCode = str_replace('-', '', $PostalCode);

			$params = [
				'postalCodeV1'=>[
					'countryCode'=>'PL',
					'zipCode'=>$PostalCode,
				],
				'authDataV1'=>[
					'login' => __user__,
					'masterFid' => __fid__,
					'password' => __password__,
				],
			];

			try{
				$result = $this->client->__soapCall('findPostalCodeV1', array($params));

				$object = new stdClass;
				$object->Method = 'findPostalCodeV1';
				$object->PostalCode = $PostalCode;
				$object->Status = $result->return->status;

				return $object;

			}catch(SOAPFault $e){
				$this->DegugLogs($client->__getLastRequest());
				throw new Exception($e->getMessage(), 1);
			}

		}




		/**
			* Metoda ma za zadanie sprawdzić status paczki 
				* @param pWybill - identyfikator paczki, wygenerowany za pomocą metody generatePackagesNumbersV1
				* @return objekt z Nazwą Metody oraz tablicą wszystkich statusów paczki
				* UWAGA do tej metody wymagany jest dodatkowy login i hasło użytkownika, wygenerowane ściśle przez serwis DPD.
				* w celu uzyskania dostępu do modułu checkDelivery, pozwalającena na korzystanie z tej metody należy skontaktować się z działem handlowym DPD
		*/

		public function checkPackageStatus($pWybill)
		{
			if (!trim($pWybill)) {
				throw new Exception("Brak wymaganych paranetrów lub błędny numer paczki...", 1);
			}

			$params = [
				'User' 	=> __userDelivery__, 
				'Password' => __passwordDelivery__,
				'ParcelNumber' => $pWybill,
			];
			try {
				$oResult = $this->dClient->__soapCall('ParcelInfo', array($params));

				$oXML = new SimpleXMLElement($oResult->ParcelInfoResult->any);

				$object = new stdClass();
				$object->Method = 'checkPackageStatus';
				$object->PackageNumber = $this->GetStatusAttribut($oXML, 'NumerListu');
				$object->Status = [];

				foreach ($oXML->Zdarzenie as $k => $v) {
					$object->Status[] = [
						'DataZdarzenia' => ($this->GetStatusAttribut($v, 'Czas')),
						'InformacjaZdarzenia' => ($this->GetStatusAttribut($v, 'Nazwisko')),
						'OpisZdarzenia' => ($this->GetStatusAttribut($v, 'Opis')),
					];
				}

				return $object;

			} catch (Exception $e) {
				$this->DegugLogs($client->__getLastRequest());
				throw new Exception($e->getMessage(), 1);
			}
			
		}

		/**
			* Metoda na potrzeby metody checkPackageStatus, pozwalająca wyciągnąć atrybut z oXML otrzymanego w działaniu SOAP
				* @param oXML - XML object, Attrib - wyciągany atrybut
				* @return string wyciągana wartość atrybutu
		*/

		private function GetStatusAttribut($oXML, $Attrib)
		{
			if(isset($oXML[$Attrib])){
				return (string) $oXML[$Attrib];
			}else{
				return (string)'Brak informacji.';
			}
		}
	}

?>