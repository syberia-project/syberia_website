<?php
/**
 * Created by Lane Shukhov.
 * Date: 27.08.2018
 * Time: 0:15
 */
include_once 'config.php';

define('SITE_URL', 'https://syberiaos.com/');

/** @var Base $f3 */
$f3->set('DEBUG',    0);
$f3->set('site_url', SITE_URL);
$f3->set('utils',    new Services\Utils($f3));

/**
 * @param Base $f3
 * @return \Services\Utils
 */
function utils(Base $f3) {
    return $f3->utils;
}

$indexAction     = function (Base $f3) {
    utils($f3)->renderPage('Syberia OS', 'pages/index.html');
};
$errorAction     = function (Base $f3) {
    utils($f3)->renderError();
};
$downloadsAction = function (Base $f3) {
    $officialDevices = utils($f3)->getOfficialDevicesByBrand();
    uasort ($officialDevices, function($a, $b) {
        return count($b) - count($a);
    });
    $isDownloadPortalEnabled = count($officialDevices) > 0;
    $f3->set('isDownloadPortalEnabled', $isDownloadPortalEnabled);
    !$isDownloadPortalEnabled ?: $f3->set('officialDevices', $officialDevices);
    utils($f3)->renderPage('Downloads - Syberia OS', 'pages/downloads.html');
};
$teamAction      = function (Base $f3) {
    utils($f3)->renderPage('Team - Syberia OS', 'pages/team.html');
};
$linksAction     = function (Base $f3) {
    utils($f3)->renderPage('Links - Syberia OS', 'pages/links.html');
};

$f3->set  ('ONERROR',        $errorAction);
$f3->route('GET /',          $indexAction);
$f3->route('GET /downloads', $downloadsAction);
$f3->route('GET /team',      $teamAction);
$f3->route('GET /links',     $linksAction);

$f3->run();

