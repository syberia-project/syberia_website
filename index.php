<?php
/**
 * Created by Lane Shukhov.
 * Date: 27.08.2018
 * Time: 0:15
 */
include_once 'config.php';

/** @var Base $f3 */
$f3->set('DEBUG',      0);
$f3->set('STATIC_VER', 5);
$f3->set('site_url',   SITE_URL);
$f3->set('utils',      new Services\Utils($f3));
$f3->set('FALLBACK',   'en');  // English as default fallback language

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

$indexAction     = function (Base $f3) {
    utils($f3)->renderPage('Syberia OS', 'pages/index.html');
};
$errorAction     = function (Base $f3) {
    $exception = $f3->get('EXCEPTION');
    if ($exception instanceof \Throwable) {
        \Sentry\captureException($exception);
    }

    // recursively clear existing output buffers:
    while (ob_get_level())
        ob_end_clean();

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
    $f3->set('lastUpdatedDevice', utils($f3)->getLastUpdatedDeviceConfig($officialDevices));
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
$getOtaChangelogAction = function (Base $f3, $params) {
    echo utils($f3)->getOtaChangelog($params['device'], $f3->get('GET.android') ?? Services\Entity\DeviceConfig::ANDROID_VERSION_11);
};

$f3->set  ('ONERROR',        $errorAction);
$f3->route('GET /',          $indexAction);
$f3->route('GET /downloads', $downloadsAction);
$f3->route('GET /team',      $teamAction);
$f3->route('GET /links',     $linksAction);
$f3->route('GET /OTA/@device', $getOtaAction);
$f3->route('GET /OTA/@device/changelog', $getOtaChangelogAction);

$f3->run();

