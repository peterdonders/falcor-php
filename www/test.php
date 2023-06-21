<?php

include("./src/head.php");



\Peter\head();

$x = 1;

do {
  if ($x == 4) {
    echo "done";
    break;
  }
  
    echo "The number is: $x <br>";
  $x++;


} while ($x <= 5);