<?php

require_once 'simple_html_dom.php';
require_once 'StopGameParser.php';
require_once 'Http.php';

$http = new Http();
$parser = new StopGameParser($http,'https://stopgame.ru');
$resul = $parser->parse(1000);




print_r($resul);