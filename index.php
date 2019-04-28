<?php
/**
 * Created by Lane Shukhov.
 * Date: 27.08.2018
 * Time: 0:15
 */
include_once 'config.php';

/** @var Base $f3 */
$f3->set('DEBUG',    0);
$f3->set('site_url', SITE_URL);
$f3->set('utils',    new Services\Utils($f3));
$f3->set('FALLBACK','en');  // English as default fallback language

if ($f3->exists('COOKIE.userLang')) {
    $supportedLanguages = ['en', 'ru'];
    $userLang = $f3->get('COOKIE.userLang');
    if (in_array($userLang, $supportedLanguages)) {
        $f3->set('LANGUAGE', $userLang);
    }
}

/**
 * @param Base $f3
 * @return \Services\Utils
 */
function utils(Base $f3) {
    return $f3->utils;
}

$f3->set('currentLanguage', utils($f3)->getCurrentLanguage());

utils($f3)->log($f3->get('PATH') . ' Cookie: ' . json_encode($f3->get('COOKIE')));
utils($f3)->log($f3->get('PATH') . ' currentLanguage: ' . json_encode($f3->get('currentLanguage')));

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
$getOtaAction = function (Base $f3, $params) {
    echo utils($f3)->getOtaFileString($params['device']);
};

$f3->set  ('ONERROR',        $errorAction);
$f3->route('GET /',          $indexAction);
$f3->route('GET /downloads', $downloadsAction);
$f3->route('GET /team',      $teamAction);
$f3->route('GET /links',     $linksAction);
$f3->route('GET /OTA/@device', $getOtaAction);

$f3->run();

