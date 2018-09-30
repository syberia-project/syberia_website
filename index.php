<?php
/**
 * Created by Lane Shukhov.
 * Date: 27.08.2018
 * Time: 0:15
 */
include_once 'config.php';

define('SITE_URL', 'https://syberiaos.com/');

$f3->set('DEBUG', 0);
$f3->set('site_url', SITE_URL);

function renderPage($f3, $title, $page) {
    // TODO: dynamic navigation generation
    // TODO: localisation support
    $f3->set('page_title', $title);
    $f3->set('page_content', $page);
    echo \Template::instance()->render('layout.html');
}

$indexAction = function ($f3) {
    renderPage($f3, 'Syberia OS', 'pages/index.html');
};

$errorAction = function ($f3) {
    // TODO: redesign error page
    echo \Template::instance()->render('error.html');
};

$downloadsAction = function ($f3) {
    // TODO: parse OTAs configs for get download links
    renderPage($f3, 'Downloads - Syberia OS', 'pages/downloads.html');
};

$teamAction = function ($f3) {
    renderPage($f3, 'Team - Syberia OS', 'pages/team.html');
};

$linksAction = function ($f3) {
    renderPage($f3, 'Links - Syberia OS', 'pages/links.html');
};

$f3->set('ONERROR', $errorAction);

$f3->route('GET /',          $indexAction);
$f3->route('GET /downloads', $downloadsAction);
$f3->route('GET /team',      $teamAction);
$f3->route('GET /links',     $linksAction);

$f3->run();

