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
        $this->_log      = new Log('./messages.log');
        $this->_errorLog = new Log('./errors.log');
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
     * @return array [string $brand => Entity\DeviceConfig[] $devices]
     */
    public function getOfficialDevicesByBrand() {
        $officialDevices = $this->getOfficialDevicesList();
        $brands = $this->_getBrands($officialDevices);

        $result = [];
        foreach ($brands as $brand) {
            $devicesByBrand = $this->_filterOfficialDevicesByBrand($officialDevices, $brand);
            $result[$brand] = $devicesByBrand;
        }
        return $result;
    }

    /**
     * @return Entity\DeviceConfig[]
     */
    public function getOfficialDevicesList() {
        $officialDevicesConfigs = $this->_getFolderFilesList(self::OFFICIAL_DEVICE_REPO_PATH);

        $result = [];
        foreach ($officialDevicesConfigs as $deviceConfigFilename) {
            try {
                $deviceConfig = $this->_loadDeviceConfigFromFile($deviceConfigFilename);
            } catch (\Throwable $t) {
                $this->logThrowable($t);
                continue;
            } catch (\Exception $e) {
                $this->logException($e);
                continue;
            }
            $result[] = $deviceConfig;
        }
        return $result;
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
        return gettype($array[$key]) === 'string' ? trim($array[$key]) : $array[$key];
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
     * @return Entity\DeviceConfig
     * @throws \Exception
     */
    private function _loadDeviceConfigFromFile($filename) {
        $configFileContent = file_get_contents(self::OFFICIAL_DEVICE_REPO_PATH . $filename);
        $deviceJson = json_decode($configFileContent, true);
        $deviceJson['config_file_name'] = $filename;
        $device = new Entity\DeviceConfig(
            $this->_tryToGetAndFormatArrayItem($deviceJson, 'developer'),    $this->_tryToGetAndFormatArrayItem($deviceJson, 'developer_url'),
            $this->_tryToGetAndFormatArrayItem($deviceJson, 'website_url'),  $this->_tryToGetAndFormatArrayItem($deviceJson, 'news_url'),
            $this->_tryToGetAndFormatArrayItem($deviceJson, 'forum_url'),    $this->_tryToGetAndFormatArrayItem($deviceJson, 'donate_url'),
            $this->_tryToGetAndFormatArrayItem($deviceJson, 'filename'),     $this->_tryToGetAndFormatArrayItem($deviceJson, 'filesize'),
            $this->_tryToGetAndFormatArrayItem($deviceJson, 'md5'),          $this->_tryToGetAndFormatArrayItem($deviceJson, 'build_date'),
            $this->_tryToGetAndFormatArrayItem($deviceJson, 'url'),          $this->_tryToGetAndFormatArrayItem($deviceJson, 'changelog'),
            $this->_tryToGetAndFormatArrayItem($deviceJson, 'addons'),       $this->_tryToGetAndFormatArrayItem($deviceJson, 'device_brand'),
            $this->_tryToGetAndFormatArrayItem($deviceJson, 'device_model'), basename($filename, '.json')
        );
        return $device;
    }

    /**
     * @param Entity\DeviceConfig[] $officialDevices
     * @return string[]
     */
    private function _getBrands($officialDevices) {
        $result = [];
        array_walk($officialDevices, function ($value, $key) use (&$result) {
            /** @var DeviceConfig $value */
            if (!in_array($value->getDeviceBrand(), $result)) {
                $result[] = $value->getDeviceBrand();
            }
        });
        natsort($result);
        return $result;
    }

    /**
     * @param Entity\DeviceConfig[] $officialDevices
     * @param string $brand
     * @return Entity\DeviceConfig[]
     */
    private function _filterOfficialDevicesByBrand($officialDevices, $brand) {
        return array_filter($officialDevices, function ($officialDevice) use ($brand) {
            /** @var Entity\DeviceConfig $officialDevice */
            return $officialDevice->getDeviceBrand() === $brand;
        });
    }

    /**
     * @param string $folderPath
     * @return string[]
     */
    private function _getFolderFilesList($folderPath) {
        return array_filter(scandir($folderPath), function($filename) use ($folderPath) {
            return is_file($folderPath . $filename);
        });
    }
}