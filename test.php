<?php
$array = array(
    'test_en',
    'test_to',
    'test_tre'
);

echo "<pre>";
print_r($array);
echo "</pre>";

echo "<br>Og resultatet er:<br>";
$seck = array_splice($array,2,count($array));

echo "<pre>";
print_r($seck);
print_r($array);
echo "</pre>";