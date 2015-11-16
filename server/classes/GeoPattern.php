<?php
require '../lib/vendor/autoload.php';

$geopattern = new \RedeyeVentures\GeoPattern\GeoPattern();
//$geopattern->setString('Mastering Markdown');
$geopattern->setBaseColor('#ffcc00');
$geopattern->setColor('#ffcc00');
$geopattern->setGenerator('sine_waves');
$svg = $geopattern->toSVG();

echo $svg;