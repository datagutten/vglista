<?php
require 'vendor/autoload.php';
use datagutten\vglista;

$year = date('Y');
try {
    $list = new vglista\TopList($year, 25);
    echo $list."\n";

}
catch (Exception $e) {
    echo $e->getMessage() . "\n";
}