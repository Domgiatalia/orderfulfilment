<?php
date_default_timezone_set('Europe/London');
ini_set('max_execution_time', 0);
test3();

function test3() {
  $mins = 50;

  $startTime = strtotime(date("Y-m-d H:i:s"));
  $convertedTime = date('Y-m-d H:i:s', strtotime('+1 minutes', $startTime));

  for($i = 0; $i <= 10000; $i++){
    $startTime = strtotime(date("Y-m-d H:i:s"));

    if($startTime >= strtotime($convertedTime)) {
      echo ("over");
      $convertedTime = date('Y-m-d H:i:s', strtotime('+1 minutes', $startTime));
    }
    echo($i);

  }

  $test = 0 ;
}