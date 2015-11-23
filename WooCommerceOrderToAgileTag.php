<?php
//	Tyler Hunnefeld
//	11/20/2015

/*
	Creates tag holding item(s) and purchase date from Woocommerce, places in Agile
	Tag form => product name_purchase date
*/

/************************************************************************************
*
*	Data definitions and 
*
************************************************************************************/

//Agile Creds
define("AGILE_DOMAIN", 'Agile Domain');  
define("AGILE_USER_EMAIL",'Agile User Email');
define("AGILE_REST_API_KEY", 'Agile API Key');

//Woocommerce DB
define('consumer_key', 'API key');
define('consumer_secret', 'API secret');
define('site', 'site url');

/************************************************************************************
*
*	Set up woocommerce library 
*
************************************************************************************/

require_once( 'lib/woocommerce-api.php' );
$options = array(
	'debug'           => true,
	'return_as_array' => true,
	'validate_url'    => false,
	'timeout'         => 30,
	'ssl_verify'      => false,
);
try {
	$client = new WC_API_Client(site, consumer_key, consumer_secret, $options );
	$orders = $client->orders->get();	//$orders is associative array
	//Creates an associative array
	/*
	
	$dataArray =>
		[0]:
			"email" => $thisEmail
			"tags" => _$thisItem[0]_$thisItem[1].$thisDate
		[1]:
			...
			
	*/

	$dataArray = array();
	$orderArray = $orders["orders"];
	foreach ($orderArray as $orderData){
		$thisEmail = $orderData["customer"]["email"];
		$theseItems = "";
		foreach ($orderData["line_items"] as $thisItem){
			$thisItemName = $thisItem["name"];
			$theseItems = "{$theseItems}_{$thisItemName}";
		}
		$thisDate = $orderData["created_at"];

		//$dataArray["email"] = $thisEmail;
		//$dataArray["tag"] = "{$theseItems}.{$thisDate}";
		$dataArray[] = array(
							"email" => $thisEmail,
							"tags" => "{$theseItems}.{$thisDate}",
		);
	}

	//if using alternative method, don't encode until foreach
	$dataArray = json_encode($dataArray);
	curl_wrap("tags", $dataArray, "POST");


	/* 
	Alternatively,

	foreach ($dataArray as $thisOrderData){
		$thisOrderData = json_encode($thisOrderData);
		curl_wrap("tags", $thisOrderData, "POST");
	}
	*/

} catch ( WC_API_Client_Exception $e ) {
	echo $e->getMessage() . PHP_EOL;
	echo $e->getCode() . PHP_EOL;
	if ( $e instanceof WC_API_Client_HTTP_Exception ) {
		print_r( $e->get_request() );
		print_r( $e->get_response() );
	}
}



/************************************************************************************
*
*	Helper Functions
*
************************************************************************************/


function curl_wrap($entity, $data, $method)
{
    $agile_url     = "https://" . AGILE_DOMAIN . ".agilecrm.com/dev/api/" . $entity;
    $agile_php_url = "https://" . AGILE_DOMAIN . ".agilecrm.com/core/php/api/" . $entity . "?id=" . AGILE_REST_API_KEY;

	$ch = curl_init();
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_UNRESTRICTED_AUTH, true);
    switch ($method) {
        case "POST":
            $url = ($entity == "tags" ? $agile_php_url : $agile_url);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            break;
        case "GET":
            $url = ($entity == "tags" ? $agile_php_url . '&email=' . $data->{'email'} : $agile_url);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            break;
        case "PUT":
            $url = ($entity == "tags" ? $agile_php_url : $agile_url);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            break;
        case "DELETE":
            $url = ($entity == "tags" ? $agile_php_url : $agile_url);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            break;
        default:
            break;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-type : application/json;','Accept : application/json'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, AGILE_USER_EMAIL . ':' . AGILE_REST_API_KEY);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

?>