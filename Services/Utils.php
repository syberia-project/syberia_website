<?php
/**
 * Created by Lane Shukhov.
 * Date: 06.10.2018
 * Time: 17:58
 */

namespace Services;

use Base;
use Log;
use Services\Entity\DeviceConfig;
use Template;

class Utils {
    const OFFICIAL_DEVICE_REPO_PATH = './OTA/';
    const A_ONLY_FOLDER             = self::OFFICIAL_DEVICE_REPO_PATH.'a-only/';
    const AB_FOLDER                 = self::OFFICIAL_DEVICE_REPO_PATH.'ab/';

    const DEVELOPER_STUB = 'unknown';

    private $_f3;
    private $_template;
    private $_log;
    private $_errorLog;

    /**
     * @param Base $_f3
     */
    public function __construct(Base $_f3) {
        $this->_f3       = $_f3;
        $this->_template = Template::instance();
        $this->_log      = new Log('messages.log');
        $this->_errorLog = new Log('errors.log');
    }

    /**
     * @param string $title
     * @param string $pageFilename
     */
    public function renderPage($title, $pageFilename) {
        // TODO: dynamic navigation generation
        // TODO: localisation support
        $this->_f3->set('page_title', $title);
        $this->_f3->set('page_content', $pageFilename);
        echo $this->_template->render('layout.html');
    }

    public function renderError() {
        // TODO: redesign error page
        echo $this->_template->render('error.html');
    }

    /**
     * @param string $message
     */
    public function log($message) {
        $this->_log->write($message);
    }

    /**
     * @param \Exception $error
     */
    public function logException($error) {
        $errorClass = get_class($error);
        $this->_errorLog->write(
            "Exception {$errorClass}, code {$error->getCode()}. Message: {$error->getMessage()}. Trace:\n{$error->getTraceAsString()}"
        );
    }

    /**
     * @param \Throwable $error
     */
    public function logThrowable($error) {
        $errorClass = get_class($error);
        $this->_errorLog->write(
            "Exception {$errorClass}, code {$error->getCode()}. Message: {$error->getMessage()}. Trace:\n{$error->getTraceAsString()}"
        );
    }

    /**
     * @param string $filename
     * @return string
     */
    public function getOtaFileString($filename) {
        $device = str_replace('.json', '', $filename);
        $device = str_replace('.', '', $device);
        $filename = "{$device}.json";
        if (file_exists(self::AB_FOLDER.$filename)) {
            return file_get_contents(self::AB_FOLDER.$filename);
        } elseif (file_exists(self::A_ONLY_FOLDER.$filename)) {
            return file_get_contents(self::A_ONLY_FOLDER.$filename);
        }
        return '{"Error": "No OTAs for requested device"}';
    }

    public function getOtaChangelog(string $device, string $androidVersion): string {
        $deviceConfig = $this->_getDeviceConfig($device, $androidVersion);
        $result = [];

        if ($deviceConfig === null) {
            $result['status'] = 'error';
            $result['error'] = 'Unknown device';
            $result['data'] = null;
        } else if ($deviceConfig->changelog === null) {
            $result['status'] = 'error';
            $result['error'] = 'No changelog available for device';
            $result['data'] = null;
        } else {
            $result['status'] = 'success';
            $result['data'] = $deviceConfig->changelog;
            $result['error'] = null;
        }

        return json_encode($result);
    }

    private function _getDeviceConfig(string $device, string $androidVersion): ?Entity\DeviceConfig {
        $deviceConfigsByBrand = $this->getOfficialDevicesByBrand();
        foreach ($deviceConfigsByBrand as $brand => $deviceConfigsByModel) {
            foreach ($deviceConfigsByModel as $model => $deviceConfigs) {
                /* @var Entity\DeviceConfig[] $deviceConfigs */
                if (trim($deviceConfigs[0]->getDeviceCodename()) === trim($device)) {
                    foreach ($deviceConfigs as $deviceConfig) {
                        if (trim($deviceConfig->getAndroidVersion()) === trim($androidVersion)) {
                            return $deviceConfig;
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * @return array [string $brand => [string $model => Entity\DeviceConfig[][]]]
     */
    public function getOfficialDevicesByBrand() {
        $officialDeviceConfigsByModelName = $this->_sortOfficialDeviceConfigsByBuildDelta($this->getOfficialDevicesListByModel());
        $brands = $this->_getBrands($officialDeviceConfigsByModelName);

        $result = [];
        foreach ($brands as $brand) {
            $devicesByBrand = $this->_filterOfficialDevicesByBrand($officialDeviceConfigsByModelName, $brand);
            uasort($devicesByBrand, function($a, $b) {
                /** @var Entity\DeviceConfig[] $a */
                /** @var Entity\DeviceConfig[] $b */
                return $a[0]->getLastBuildDelta() > $b[0]->getLastBuildDelta();
            });
            $result[$brand] = $devicesByBrand;
        }
        return $result;
    }

    public function getLastUpdatedDeviceConfig(array $devicesConfigsByBrand): ?DeviceConfig {
        $lastUpdatedDeviceConfig = null;

        foreach ($devicesConfigsByBrand as $brand => $devicesConfigsByModel) {
            foreach ($devicesConfigsByModel as $model => $deviceConfigs) {
                foreach ($deviceConfigs as $deviceConfig) {
                    /** @var DeviceConfig $deviceConfig */
                    if ($lastUpdatedDeviceConfig === null) {
                        $lastUpdatedDeviceConfig = $deviceConfig;
                        continue;
                    }

                    if ($lastUpdatedDeviceConfig->getLastBuildDelta() > $deviceConfig->getLastBuildDelta()) {
                        $lastUpdatedDeviceConfig = $deviceConfig;
                    }
                }
            }
        }

        if ($lastUpdatedDeviceConfig->getLastBuildDelta() > 1) {
            return null;
        }

        return $lastUpdatedDeviceConfig;
    }

    private function _sortOfficialDeviceConfigsByBuildDelta($officialDeviceConfigsByModelName) {
        $result = [];
        foreach ($officialDeviceConfigsByModelName as $modelName => $officialDeviceConfigs) {
            usort($officialDeviceConfigs, function($a, $b) {
                /** @var Entity\DeviceConfig $a */
                /** @var Entity\DeviceConfig $b */
                return $a->getLastBuildDelta() > $b->getLastBuildDelta();
            });
            $result[$modelName] = $officialDeviceConfigs;
        }
        return $result;
    }

    /**
     * @return Entity\DeviceConfig[][]
     */
    public function getOfficialDevicesListByModel() {
        $officialAOnlyDevicesConfigs = $this->_getFolderFilesList(self::A_ONLY_FOLDER, '.json');
        $officialABDevicesConfigs    = $this->_getFolderFilesList(self::AB_FOLDER, '.json');
        $aonlyConfigs = $this->_processDeviceConfigFiles($officialAOnlyDevicesConfigs, false);
        $abConfigs = $this->_processDeviceConfigFiles($officialABDevicesConfigs, true);
        $android10Configs = $this->_processDeviceConfigFiles($officialABDevicesConfigs, true, DeviceConfig::ANDROID_VERSION_10);
        $android11Configs = $this->_processDeviceConfigFiles($officialABDevicesConfigs, true, DeviceConfig::ANDROID_VERSION_11);
        return array_merge_recursive($aonlyConfigs, $abConfigs, $android10Configs, $android11Configs);
    }

    /**
     * @param string[] $filenames
     * @param bool $isAb
     * @param string $androidVersion
     * @return Entity\DeviceConfig[]
     */
    private function _processDeviceConfigFiles($filenames, $isAb, $androidVersion = Entity\DeviceConfig::ANDROID_VERSION_9) {
        $result = [];
        foreach ($filenames as $deviceConfigFilename) {
            try {
                if (!$this->_isAndroidVersionMatch($deviceConfigFilename, $androidVersion)) {
                    continue;
                }

                $deviceConfig = $this->_loadDeviceConfigFromFile($deviceConfigFilename, $isAb, $androidVersion);
            } catch (\Throwable $t) {
                $this->logThrowable($t);
                continue;
            } catch (\Exception $e) {
                $this->logException($e);
                continue;
            }
            $result[$deviceConfig->getDeviceModel()][] = $deviceConfig;
        }
        return $result;
    }

    /**
     * @param string $filename
     * @param string $targetAndroidVersion
     * @return bool
     * @throws \Exception
     */
    private function _isAndroidVersionMatch($filename, $targetAndroidVersion) {
        $android10Postfix = '-10.json';
        $android11Postfix = '-11.json';
        $android12Postfix = '-12.json';
        switch ($targetAndroidVersion) {
            case Entity\DeviceConfig::ANDROID_VERSION_9:
                return !$this->_hasPostfix($filename, $android10Postfix) && !$this->_hasPostfix($filename, $android11Postfix) && !$this->_hasPostfix($filename, $android12Postfix);;
            case Entity\DeviceConfig::ANDROID_VERSION_10:
                return $this->_hasPostfix($filename, $android10Postfix);
            case Entity\DeviceConfig::ANDROID_VERSION_11:
                return $this->_hasPostfix($filename, $android11Postfix);
            case Entity\DeviceConfig::ANDROID_VERSION_12:
                return $this->_hasPostfix($filename, $android12Postfix);
            default:
                throw new \Exception("Unknown android version: {$targetAndroidVersion}");
        }
    }

    /**
     * @param string $string
     * @param string $postfix
     * @return bool
     */
    private function _hasPostfix($string, $postfix) {
        $stringLength = strlen($string);
        $postfixLength = strlen($postfix);
        if ($postfixLength > $stringLength) {
            return false;
        }

        return substr($string, $stringLength - $postfixLength, $postfixLength) === $postfix;
    }

    /**
     * @param array $array
     * @param string $key
     * @return mixed
     * @throws \Exception
     */
    private function _tryToGetAndFormatArrayItem($array, $key) {
        if ($array[$key] === null) {
            throw new \Exception("No {$key} field in {$array['config_file_name']}");
        }
        $result = gettype($array[$key]) === 'string' ? trim($array[$key]) : $array[$key];
        if (gettype($result) === 'string') {
            return mb_strlen($result) > 0 ? $result: null;
        } else {
            return $result;
        }
    }

    /**
     * @param DeviceConfig $device
     * @return DeviceConfig
     */
    private function _fixNoBreakSpace(Entity\DeviceConfig $device) {
        $device->device_model = str_replace(' ' , "\xc2\xa0", $device->device_model);
        return $device;
    }

    /**
     * @param string $filename
     * @param bool $isAb
     * @param string $androidVersion
     * @return Entity\DeviceConfig
     * @throws \Exception
     */
    private function _loadDeviceConfigFromFile($filename, $isAb, $androidVersion) {
        $folder = $isAb ? self::AB_FOLDER : self::A_ONLY_FOLDER;
        $configFileContent = file_get_contents("{$folder}{$filename}");
        $deviceJson = json_decode($configFileContent, true);
        $deviceJson = $isAb ? $deviceJson['response'][0] : $deviceJson;
        $deviceJson['config_file_name'] = $filename;
        $deviceConfig = $isAb ? $this->_loadABDeviceConfigFromData($deviceJson, $androidVersion) : $this->_loadAOnlyDeviceConfigFromData($deviceJson, $androidVersion);
        $autoBuildChangelog = $this->_loadAutobuildChangelog($filename, $folder);
        if ($autoBuildChangelog !== null) {
            $deviceConfig->changelog = $autoBuildChangelog;
        }
        return $deviceConfig;
    }

    /**
     * @param string $deviceConfigFilename
     * @param string $folder
     * @return mixed
     */
    private function _loadAutobuildChangelog($deviceConfigFilename, $folder) {
        $changelogFileName = str_replace('.json', '.changelog', $deviceConfigFilename);
        $changelogFilePath = "{$folder}{$changelogFileName}";
        if (!file_exists($changelogFilePath)) {
            $changelogFileName = strtolower($changelogFileName);
            $changelogFilePath = "{$folder}{$changelogFileName}";
            if (!file_exists($changelogFilePath)) {
                return null;
            }
        }
        $content = file_get_contents($changelogFilePath);
        return mb_strlen($content) > 0 ? str_replace("\x1B", '', $content) : null;
    }

    /**
     * @param mixed $data
     * @param string $androidVersion
     * @return Entity\DeviceConfig
     * @throws \Exception
     */
    private function _loadAOnlyDeviceConfigFromData($data, $androidVersion) {
        $device = new Entity\DeviceConfig(
            $this->_tryToGetAndFormatArrayItem($data, 'developer'),    $this->_tryToGetAndFormatArrayItem($data, 'developer_url'),
            $this->_tryToGetAndFormatArrayItem($data, 'website_url'),  $this->_tryToGetAndFormatArrayItem($data, 'news_url'),
            $this->_tryToGetAndFormatArrayItem($data, 'forum_url'),    $this->_tryToGetAndFormatArrayItem($data, 'donate_url'),
            $this->_tryToGetAndFormatArrayItem($data, 'filename'),     $this->_tryToGetAndFormatArrayItem($data, 'filesize'),
            $this->_tryToGetAndFormatArrayItem($data, 'md5'),          @\DateTime::createFromFormat('Ymd', $this->_tryToGetAndFormatArrayItem($data, 'build_date'))->format('Y-m-d'),
            $this->_tryToGetAndFormatArrayItem($data, 'url'),          $this->_tryToGetAndFormatArrayItem($data, 'changelog'),
            $this->_tryToGetAndFormatArrayItem($data, 'addons'),       $this->_tryToGetAndFormatArrayItem($data, 'device_brand'),
            $this->_tryToGetAndFormatArrayItem($data, 'device_model'), trim(basename($data['config_file_name'], '.json')),
            false, null, $androidVersion
        );
        return $device;
    }

    /**
     * @param mixed $androidVersion
     * @param string $data
     * @return Entity\DeviceConfig
     * @throws \Exception
     */
    private function _loadABDeviceConfigFromData($data, $androidVersion) {
        $developer = $androidVersion === Entity\DeviceConfig::ANDROID_VERSION_10 || $androidVersion === Entity\DeviceConfig::ANDROID_VERSION_11
            ? self::DEVELOPER_STUB
            : $this->_tryToGetAndFormatArrayItem($data, 'developer');

        $device = new Entity\DeviceConfig(
            $developer, null,
            null, null,
            null, null,
            null, $this->_tryToGetAndFormatArrayItem($data, 'size'),
            null, @date('Y-m-d', $this->_tryToGetAndFormatArrayItem($data, 'datetime')),
            $this->_tryToGetAndFormatArrayItem($data, 'url'), null,
            null, $this->_tryToGetAndFormatArrayItem($data, 'device_brand'),
            $this->_tryToGetAndFormatArrayItem($data, 'device_model'), trim($data['device_codename']),
            true, $this->_tryToGetAndFormatArrayItem($data, 'version'), $androidVersion
        );
        return $device;
    }

    /**
     * @param Entity\DeviceConfig[][] $officialDeviceConfigsByModelName
     * @return string[]
     */
    private function _getBrands($officialDeviceConfigsByModelName) {
        $result = [];
        array_walk($officialDeviceConfigsByModelName, function ($deviceConfigs) use (&$result) {
            /** @var DeviceConfig[] $deviceConfigs */
            $result[] = $deviceConfigs[0]->getDeviceBrand();
        });
        $result = array_unique($result);
        natsort($result);
        return $result;
    }

    /**
     * @param Entity\DeviceConfig[][] $officialDevices
     * @param string $brand
     * @return Entity\DeviceConfig[][]
     */
    private function _filterOfficialDevicesByBrand($officialDevices, $brand) {
        return array_filter($officialDevices, function ($officialDeviceConfigs) use ($brand) {
            /** @var Entity\DeviceConfig[] $officialDeviceConfigs */
            return $officialDeviceConfigs[0]->getDeviceBrand() === $brand;
        });
    }

    /**
     * @param string $folderPath
     * @param string $extensionFilter like '.json'
     * @return string[]
     */
    private function _getFolderFilesList($folderPath, $extensionFilter = null) {
        try {
            if (!is_dir($folderPath)) {
                return [];
            }
            return array_filter(scandir($folderPath), function ($filename) use ($folderPath, $extensionFilter) {
                if ($extensionFilter === null) {
                    return is_file($folderPath . $filename);
                } else {
                    return is_file($folderPath . $filename) && substr($filename, -mb_strlen($extensionFilter)) === $extensionFilter;
                }
            });
        } catch (\Throwable $t) {
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @return string
     */
    public function getCurrentLanguage() {
        $language = $this->_f3->get('T.languageName');
        switch ($language) {
            case 'Русский':
                return 'ru';
                break;
            case 'English':
                return 'en';
                break;
            default:
                return '';
                break;
        }
    }

    /**
     * @param int $dayCount
     * @return string
     */
    public function getTranslatedLastBuildDateText($dayCount) {
        $language = $this->getCurrentLanguage();
        switch ($language) {
            case 'ru':
                return "{$dayCount} {$this->_pluralRus($dayCount, ['день', 'дня', 'дней'])} назад";
                break;
            default:
                return "{$dayCount} {$this->_f3->get('T.daysAgo')}";
                break;
        }
    }

    /**
     * @param int $number
     * @param string[] $after
     * @return string
     */
    private function _pluralRus($number, $after) {
        $cases = array (2, 0, 1, 1, 1, 2);
        return $after[ ($number%100>4 && $number%100<20)? 2: $cases[min($number%10, 5)] ];
    }
}
