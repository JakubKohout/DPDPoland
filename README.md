# DPD-CLASS-PHP

Klasa stworzona na potrzeby integracji sklepu internetowego z serwisem kurierskim DPD.
Klasa posiada następujące metody: 

  - generatePackagesNumbersV1 -> Generuje numer listu przewozowego
  - generateSpedLabelsV1 -> Generuje etykietę na podstawie numeru listu przewozowego
  - generateProtocolV1 -> Generuje protokół przekazania na podstawie numerów identyfikacyjnych zamówienia
  - appendParcelsToPackageV1 -> Pozwala na dodanie paczki do zamówienia
  - packagesPickupCallV1 -> Pozwala na wezwanei kuriera, aby odebrał paczki z określonego protokołu
  - findPostalCodeV1 -> Pozwala na sprawdzenie poprawności kodu pocztowego



### Version
1.0.0

## Opis Metod
<b>generatePackagesNumbersV1</b>

Przyjmuje parametry:

  - ParcelsArray -> Tablica paczek
  - Payer -> osoba, która zostanie pokryta kosztami za usługę
  - ReceiverArray -> Tablica z danymi Odbiorcy
  - SenderArray -> Tablica z danymi Nadawcy
  - ServicesArray -> Tablica z dodatkowymi usługami.
  - Ref -> Dodatkowe informacje dla kuriera

Parametry wyjściowe:
- object z danymi (Dane Nadawcy, PackageID, Tablica z danymi paczki/ek (ParcelID, Status, Numer Identyfikacyjny))
Przykładowe wywołanie:

```sh
	include 'dpd.class.php';

	//Definiowanie danych autoryzacyjnych//
	//konto testowe//

	define('__user__', 'test');
	define('__fid__', '1495');
	define('__password__', 'KqvsoFLT2M');
	define('__wsdl__', 'https://dpdservicesdemo.dpd.com.pl/DPDPackageObjServicesService/DPDPackageObjServices?WSDL');

	//Parametry metody//

	$ParcelsArray = [
		0=>[
			'content'=>'Zawartość paczki 1',
			'customerData1'=>'Proszę uważać',
			'weight'=>'5',
                        'sizeX' => '1',
                        'sizeY' => '1',
                        'sizeZ' => '1'
                        
		],
		1=>[
			'content'=>'Zawartość paczki 2',
			'customerData1'=>'Szkło!',
			'weight'=>'18',
                        'sizeX' => '1',
                        'sizeY' => '1',
                        'sizeZ' => '1'
		],
	];

	$ReceiverArray = [
		'address' => 'RAddress',
		'city' => 'RCity',
		'company' => 'Rcompany',
		'countryCode' => 'PL',
		'email'=> 'REmail@company.pl',
		'name' => 'RName',
		'phone' => 'RPhone',
		'postalCode' => '41100',
	];

	$SenderArray = [
		'address' => 'SAddress',
		'city' => 'SCity',
		'company' => 'Scompany',
		'countryCode' => 'PL',
		'email'=> 'SEmail@company.pl',
		'name' => 'SName',
		'fid' => '1495',
		'phone' => 'SPhone',
		'postalCode' => '41100',
	];


	$DPD = new DPD();
    $Result = $DPD->generatePackagesNumbersV1($ParcelsArray, 'SENDER', $ReceiverArray, $SenderArray);
```
RESPONSE
```sh
stdClass Object
(
    [Method] => generatePackagesNumbersV1
    [Sender] => Array
        (
            [address] => SAddress
            [city] => SCity
            [company] => Scompany
            [countryCode] => PL
            [email] => SEmail@company.pl
            [name] => SName
            [fid] => 1495
            [phone] => SPhone
            [postalCode] => 41100
        )

    [PackageID] => 00000
    [Parcels] => Array
        (
            [0] => stdClass Object
                (
                    [parcelId] => 000000
                    [status] => OK
                    [waybill] => 0000000000000S
                )

            [1] => stdClass Object
               (
                    [parcelId] => 000000
                    [status] => OK
                    [waybill] => 0000000000000S
                )

        )

)
```
<b>generateSpedLabelsV1</b>

Przyjmuje parametry:
  - SenderArray -> Tablica z danymi odbiorcy
  - PackageID -> Int z Numerem przewozowym

Parametry wyjściowe:
  - objekt z Nazwą Metody Oraz BASE64 PDF

Przykładowe wywołanie:
```sh
	include 'dpd.class.php';

	//Definiowanie danych autoryzacyjnych//
	//konto testowe//

	define('__user__', 'test');
	define('__fid__', '1495');
	define('__password__', 'KqvsoFLT2M');
	define('__wsdl__', 'https://dpdservicesdemo.dpd.com.pl/DPDPackageObjServicesService/DPDPackageObjServices?WSDL');

	//Parametry metody//
	$SenderArray = [
		'address' => 'SAddress',
		'city' => 'SCity',
		'company' => 'Scompany',
		'countryCode' => 'PL',
		'email'=> 'SEmail@company.pl',
		'name' => 'SName',
		'fid' => '1495',
		'phone' => 'SPhone',
		'postalCode' => '41100',
	];
	
	$PackageID = '00000';
	
	$DPD = new DPD();
	$Result = $DPD->generateSpedLabelsV1($SenderArray, $PackageID);
```
RESPONSE

```sh
stdClass Object
(
    [Method] => generateSpedLabelsV1
    [PDF] => (PDF ZAKODOWANY BASE64)
)
```
<b>generateProtocolV1</b>

Przyjmuje parametry:
  - PackageIDArray -> Tablica kodów PackageID
  - FID -> FID oddziału, z którego zostaje generowany protokół

Parametry wyjściowe:
  - objekt z Nazwą Metody, NIE ZAKODOWANY PDF, DocumentID potrzebny do wezwania kuriera

Przykładowe wywołanie:
```sh
	include 'dpd.class.php';

	//Definiowanie danych autoryzacyjnych//
	//konto testowe//

	define('__user__', 'test');
	define('__fid__', '1495');
	define('__password__', 'KqvsoFLT2M');
	define('__wsdl__', 'https://dpdservicesdemo.dpd.com.pl/DPDPackageObjServicesService/DPDPackageObjServices?WSDL');

	//Parametry metody//
	$PackageIDArray = [
		0=>'00000',
		1=>'00000',
	];
	
	$FID = '1495';
	
	$DPD = new DPD();
	$Result = $DPD->generateProtocolV1($PackageIDArray, '1495');
```

RESPONSE
```sh
stdClass Object
(
    [Method] => generateProtocolV1
    [PDF] => PDF
    [DocumentID] => 000000
)
```
<b>appendParcelsToPackageV1</b>

Przyjmuje parametry:
  - PackageID -> kodów PackageID paczki do której ma zostać dodana parcela
  - Content -> zawartość paczki
  - Weight -> Waga paczki.
  - CustomerData -> String(27) z dodatkowymi informacjami o paczce.


Parametry wyjściowe:
  - objekt z Nazwą Metody oraz Numerem przewozowym Paczki

Przykładowe wywołanie:
```sh
include 'dpd.class.php';

	//Definiowanie danych autoryzacyjnych//
	//konto testowe//

	define('__user__', 'test');
	define('__fid__', '1495');
	define('__password__', 'KqvsoFLT2M');
	define('__wsdl__', 'https://dpdservicesdemo.dpd.com.pl/DPDPackageObjServicesService/DPDPackageObjServices?WSDL');

	//Parametry metody//
	$PackageID = '000000';
	$Content = 'Zawartość';
	$Weight = '7';
	$CustomerData = 'Dodatkowe informacje';
	
	$DPD = new DPD();
	$Result = $DPD->appendParcelsToPackageV1($PackageID, $Content, $Weight, $CustomerData);
	
```
RESPONSE
```sh
stdClass Object
(
    [Method] => appendParcelsToPackageV1
    [wybill] => 0000000000000S
)
```
<b>packagesPickupCallV1</b>

Przyjmuje parametry:
  - FID -> FID oddziału, z którego zostaje generowany protokół
  - PickupDate -> Data odbioru przez kuriera w formacje RRRR-MM-DD
  - PickupTimeFrom -> Godzina, od której kurier może odbierać paczki w formacie 00:00
  - PickupTimeTo -> Godzina, do której kurier może odbierać paczki w formacie 00:00
  - DocumentID -> Numer dokumentu, na którym są umieszczone wszystkie paczki, które kurier ma zabrać (generowany podczas korzystania z metody generateProtocolV1)
  - ContactArray -> Tablica z danymi kontaktowymi


Parametry wyjściowe:
  - objekt z Nazwą Metody, DocumentID oraz statusem zatwierdzenia.

Przykładowe wywołanie:
```sh
include 'dpd.class.php';

	//Definiowanie danych autoryzacyjnych//
	//konto testowe//

	define('__user__', 'test');
	define('__fid__', '1495');
	define('__password__', 'KqvsoFLT2M');
	define('__wsdl__', 'https://dpdservicesdemo.dpd.com.pl/DPDPackageObjServicesService/DPDPackageObjServices?WSDL');

	//Parametry metody//
	$FID = '1495';
	$PickupDate = '2015-01-02';
	$PickupTimeFrom = '12:00';
	$PickupTimeTo = '18:00';
	$DocumentID = '000000';
	
	$DPD = new DPD();
	$Result = $DPD->packagesPickupCallV1($FID, $PickupDate, $PickupTimeFrom, $PickupTimeTo, $DocumentID);
```
RESPONSE
```sh
stdClass Object
(
    [Method] => packagesPickupCallV1
    [DocumentID] => 000000
    [Status] => OK
)
```
<b>findPostalCodeV1</b>

Przyjmuje parametry:
  - PostalCode - kod pocztowy do sprawdzenia

Parametry wyjściowe:
  - objekt z Nazwą Metody, Kodem pocztowym oraz Statusem, bądź błędem.

Przykładowe wywołanie:

```sh
    include 'dpd.class.php';

	//Definiowanie danych autoryzacyjnych//
	//konto testowe//

	define('__user__', 'test');
	define('__fid__', '1495');
	define('__password__', 'KqvsoFLT2M');
	define('__wsdl__', 'https://dpdservicesdemo.dpd.com.pl/DPDPackageObjServicesService/DPDPackageObjServices?WSDL');

	//Parametry metody//
	$PostalCode = '41100';
	
	$DPD = new DPD();
	$Result= $DPD->findPostalCodeV1($PostalCode);
```
RESPONSE
```sh
stdClass Object
(
    [Method] => findPostalCodeV1
    [PostalCode] => 41100
    [Status] => OK
)
```
<b>code</b><br>
metoda pozwala na podgląd uzyskanego obiektu z dowolnej metody
Przykładowe wywołanie:

```sh
    $DPD->code($Result);
```
License
----

Freeware

