<?php
/**
 * Created by Lane Shukhov.
 * Date: 27.08.2018
 * Time: 0:15
 */
include_once "config.php";

define("SITE_URL", 'https://syberiaos.com/');

$f3->set('DEBUG',0);
$f3->set('site_url', SITE_URL);

$f3->route('GET /', function($f3) {
    echo \Template::instance()->render('stub.html');
});

$f3->run();