<?php
date_default_timezone_set('Europe/London');
ini_set('max_execution_time', 0);
GetData();

function getData() {
  $token = getToken();
  $iteminfo = [];
  $finalArr = [0 => ['NAME','INVOICED SOLD','SALESORDERS','PRICE','WAREHOUSE SOLD FROM','GIATALIA STOCK','GIATALIA COMMITTED STOCK','ON SHIP (0-4 WEEKS) STOCK', 'ON SHIP COMMITTED STOCK','IN PRODUCTION STOCK', 'IN PRODUCTION COMMITTED STOCK']];
  $counter = 0;
  $counter1 = 0;
  $startTime = strtotime(date("Y-m-d H:i:s"));
  $convertedTime = date('Y-m-d H:i:s', strtotime('+58 minutes', $startTime));
  $days = 91;

  if($itemsArray = getInvoicedOrders($token)) {
    sleep(60);
  };
 
  foreach($itemsArray as $item) { //filter by sku here
    foreach($item as $indi){
      $startTime = strtotime(date("Y-m-d H:i:s"));

      if($startTime >= strtotime($convertedTime)) {
        $convertedTime = date('Y-m-d H:i:s', strtotime('+58 minutes', $startTime));
        $token = getToken();
      }


      $iteminfofromfunc = getInvoiceInfo($indi['invoice_id'], $token, $days);

      if($iteminfofromfunc !== false) {
        array_push($iteminfo, $iteminfofromfunc);
        $counter++;
      } else {
        sleep(60);
        $counter = 0;
        break;
      }
  
      if($counter >= 80) {
        sleep(60);
        $counter = 0;
      }
    }
  }

  if($salesOrders = getSalesOrders($token)){
    sleep(60);
  };

  foreach($salesOrders as $items) {
    foreach($items as $item) {

      $startTime = strtotime(date("Y-m-d H:i:s"));

      if($startTime >= strtotime($convertedTime)) {
        $convertedTime = date('Y-m-d H:i:s', strtotime('+58 minutes', $startTime));
        $token = getToken();
      }

      $sofromfunc = getSalesOrderInfo($item['salesorder_id'], $token, $days);

      if($sofromfunc !== false) {
        array_push($iteminfo, $sofromfunc);
        $counter++;
      } else {
        sleep(60);
        $counter = 0;
        break;
      }
  
      if($counter >= 80) {
        sleep(60);
        $counter = 0;
      }
    }
  }

  foreach($iteminfo as $item) {
    foreach($item as $key => $line) {

      //return([$price,$wh1,$wh2,$wh3,$whc1,$whc2,$whc3]);

      if(array_key_exists($line[$key][4], $finalArr) === false){
        $singleItemInfo = getItemInfo($line[$key][4],$line[$key][5], $token);
        if(isset($line[0][6])) {
          $finalArr[$line[0][4]] = [$line[0][1],0,$line[0][2],$singleItemInfo[0],$line[0][3],$singleItemInfo[1],$singleItemInfo[4],$singleItemInfo[2],$singleItemInfo[5],$singleItemInfo[3],$singleItemInfo[6]];  
        } else {                  //NAME  INVOICED SALES ORDER     PRICE           WAREHOUSE       GIATALIA        G COMMITTED         ON SHIP            OS COMMITTED       PRODUCTION       PRODUCTION COMMITED
          $finalArr[$line[0][4]] = [$line[0][1],$line[0][2],0,$singleItemInfo[0],$line[0][3],$singleItemInfo[1],$singleItemInfo[4],$singleItemInfo[2],$singleItemInfo[5],$singleItemInfo[3],$singleItemInfo[6]];  
        }
        $counter++;
      } else {
        if(isset($line[0][6])) {
          $finalArr[$line[0][4]][2] = $finalArr[$line[0][4]][2] + $line[0][2]; //add qty values
        } else {
          $finalArr[$line[0][4]][1] = $finalArr[$line[0][4]][1] + $line[0][2]; //add qty values
        }
      }

      if($counter >= 80) {
        sleep(60);
        $counter = 0;
      }

    }
  }

  $CSVOutput = fopen('StockFile.csv', 'w');
  foreach ($finalArr as $line) {
    fputcsv($CSVOutput, $line); //save output to csv file
  }

};


function getSalesOrders($token) {
  $page = 14;
  $warehouses = ['102402000000039007','102402000000039013','102402000000039017'];
  $curl = curl_init();
  $whCounter = 0;

  $startTime = strtotime(date("Y-m-d H:i:s"));
  $convertedTime = strtotime(date('Y-m-d H:i:s', strtotime('+50 minutes', strtotime($startTime))));

  foreach($warehouses as $warehouse){
    for($i = 1; $i <= $page; $i++) {
    // API CALL HERE FOR SALES ORDERS



      if(!isset($response)) {
      $response = curl_exec($curl);
      $jsonitemarray[$whCounter] = json_decode($response,true)['salesorders'];
      } else {
      $jsonSecondArray = json_decode(curl_exec($curl),true)['salesorders'];
      $jsonitemarray[$whCounter] = array_merge($jsonitemarray[$whCounter], $jsonSecondArray);
      }
    }
    $response = null;
    $whCounter++;
  }
  curl_close($curl);
  return $jsonitemarray;
}

function getSalesOrderInfo($itemid, $requestToken, $days) {
  $curl = curl_init();

// API CALL
  
  $response = curl_exec($curl);
  $iteminfonew = json_decode($response, true)['salesorder'];
  $arrayofitems = [];

  curl_close($curl);

  $date = $iteminfonew['date'];
  $items = $iteminfonew['line_items'];

  $itemdate = strtotime($date);
  $todaysdate = strtotime(date('Y-m-d'));
  $datediff = $todaysdate - $itemdate;

  if($datediff / (60 * 60 * 24) >= $days) { //how many days
    return(false);
  }

  foreach($items as $item) {
    if(isset( $item['warehouse_name'])) {
      array_push($arrayofitems, [$item['sku'], $item['name'], $item['quantity'], $item['warehouse_name'], $item['item_id'],$item['warehouse_id'], 'SO']);
    }
  }

  return [$arrayofitems];

}

function getItemInfo($itemNo,$warehouse,$token) {
  $curl = curl_init();

  //API CALL

  $response = curl_exec($curl);
  $iteminfonew = json_decode($response, true)['item'];
  curl_close($curl);


  $price = $iteminfonew['sales_rate'];

  $wh1 = $iteminfonew['warehouses'][0]['warehouse_available_for_sale_stock'];
  $wh2 = $iteminfonew['warehouses'][2]['warehouse_available_for_sale_stock'];
  $wh3 = $iteminfonew['warehouses'][3]['warehouse_available_for_sale_stock'];
  $whc1 = $iteminfonew['warehouses'][0]['warehouse_actual_committed_stock'];
  $whc2 = $iteminfonew['warehouses'][2]['warehouse_actual_committed_stock'];
  $whc3 = $iteminfonew['warehouses'][3]['warehouse_actual_committed_stock'];

  // foreach($iteminfonew['warehouses'] as $item) {
  //   if($item['warehouse_id'] == $warehouse) {
  //     $qty = $item['warehouse_available_for_sale_stock'];
  //     $total_commited = $item['warehouse_actual_committed_stock'];
  //   }
  // }

  return([$price,$wh1,$wh2,$wh3,$whc1,$whc2,$whc3]);

}

function getInvoiceInfo($itemid, $requestToken, $days) {
  $curl = curl_init();

    //API CALL

  $response = curl_exec($curl);
  $iteminfonew = json_decode($response, true)['invoice'];
  $arrayofitems = [];

  curl_close($curl);

  $date = $iteminfonew['date'];
  $items = $iteminfonew['line_items'];

  $itemdate = strtotime($date);
  $todaysdate = strtotime(date('Y-m-d'));
  $datediff = $todaysdate - $itemdate;

  if($datediff / (60 * 60 * 24) >= $days) { //how many days
    return(false);
  }

  foreach($items as $item) {
    if(isset( $item['warehouse_name'])) {
      array_push($arrayofitems, [$item['sku'], $item['name'], $item['quantity'], $item['warehouse_name'], $item['item_id'],$item['warehouse_id']]);
    }
  }

  return [$arrayofitems];

}

function getInvoicedOrders($requestToken) : array{
    $page = 13;
    $warehouses = ['102402000000039007','102402000000039013','102402000000039017'];
    $curl = curl_init();
    $whCounter = 0;
    foreach($warehouses as $warehouse){
      for($i = 1; $i <= $page; $i++) {
    // API CALL

        if(!isset($response)) {
        $response = curl_exec($curl);
        $jsonitemarray[$whCounter] = json_decode($response,true)['invoices'];
        } else {
        $jsonSecondArray = json_decode(curl_exec($curl),true)['invoices'];
        $jsonitemarray[$whCounter] = array_merge($jsonitemarray[$whCounter], $jsonSecondArray);
      }
    }
    $response = null;
    $whCounter++;
  }
    curl_close($curl);
    return $jsonitemarray;
};

function getToken() {
    $refreshToken = "REFRESH TOKEN";
  
    $curl = curl_init();
    
    //API CALL
    
    $response = json_decode(curl_exec($curl));
    curl_close($curl);
  
    $accessToken = $response->access_token;
  
    return($accessToken);
};
