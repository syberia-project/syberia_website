<?php
/**
 * Created by Lane Shukhov.
 * Date: 28.10.2018
 * Time: 20:07
 */

namespace Services\Entity;

class DeviceConfig {

    const ANDROID_VERSION_9  = '9 Pie';
    const ANDROID_VERSION_10 = '10';
    const ANDROID_VERSION_11 = '11';
    const ANDROID_VERSION_12 = '12';

    /**
     * @var string
     */
    public $developer;
    /**
     * @var string
     */
    public $developer_url;
    /**
     * @var string
     */
    public $website_url;
    /**
     * @var string
     */
    public $news_url;
    /**
     * @var string
     */
    public $forum_url;
    /**
     * @var string
     */
    public $donate_url;

    /**
     * @var string
     */
    public $filename;
    /**
     * @var string
     */
    public $filesize;
    /**
     * @var string
     */
    public $md5;
    /**
     * @var string
     */
    public $build_date;
    /**
     * @var string
     */
    private $url;
    /**
     * @var string
     */
    public $changelog;
    /**
     * @var array
     */
    public $addons;

    /**
     * @var string
     */
    public $device_brand;
    /**
     * @var string
     */
    public $device_model;

    /**
     * @var string
     */
    public $device_codename;

    /**
     * @var bool
     */
    public $is_ab;

    /**
     * @var string
     */
    public $version;

    /**
     * @var string
     */
    public $androidVersion;

    /**
     * DeviceConfig constructor.
     * @param string $developer
     * @param string $developer_url
     * @param string $website_url
     * @param string $news_url
     * @param string $forum_url
     * @param string $donate_url
     * @param string $filename
     * @param string $filesize
     * @param string $md5
     * @param string $build_date
     * @param string $url
     * @param string $changelog
     * @param array  $addons
     * @param string $device_brand
     * @param string $device_model
     * @param string $device_codename
     * @param bool $is_ab
     * @param string $version
     * @param string $androidVersion
     */
    public function __construct(
        $developer,
        $developer_url,
        $website_url,
        $news_url,
        $forum_url,
        $donate_url,
        $filename,
        $filesize,
        $md5,
        $build_date,
        $url,
        $changelog,
        $addons,
        $device_brand,
        $device_model,
        $device_codename,
        $is_ab,
        $version,
        $androidVersion
    ) {
        $this->developer = $developer;
        $this->developer_url = $developer_url;
        $this->website_url = $website_url;
        $this->news_url = $news_url;
        $this->forum_url = $forum_url;
        $this->donate_url = $donate_url;
        $this->filename = $filename;
        $this->filesize = $filesize;
        $this->md5 = $md5;
        $this->build_date = $build_date;
        $this->url = $url;
        $this->changelog = $changelog;
        $this->addons = $addons;
        $this->device_brand = $device_brand;
        $this->device_model = $device_model;
        $this->device_codename = $device_codename;
        $this->is_ab = $is_ab;
        $this->version = $version;
        $this->androidVersion = $androidVersion;
    }

    /**
     * @return string
     */
    public function getDeveloper() {
        return $this->developer;
    }

    /**
     * @return string
     */
    public function getDeveloperUrl() {
        return $this->developer_url;
    }

    /**
     * @return string
     */
    public function getWebsiteUrl() {
        return $this->website_url;
    }

    /**
     * @return string
     */
    public function getNewsUrl() {
        return $this->news_url;
    }

    /**
     * @return string
     */
    public function getForumUrl() {
        return $this->forum_url;
    }

    /**
     * @return string
     */
    public function getDonateUrl() {
        return $this->donate_url;
    }

    /**
     * @return string
     */
    public function getFilename() {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getFilesize() {
        return $this->filesize;
    }

    /**
     * @return string
     */
    public function getMd5() {
        return $this->md5;
    }

    /**
     * @return string
     */
    public function getBuildDate() {
        return $this->build_date;
    }

    /**
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getChangelog() {
        return $this->changelog;
    }

    /**
     * @return array
     */
    public function getAddons() {
        return $this->addons;
    }

    /**
     * @return string
     */
    public function getDeviceBrand() {
        return $this->device_brand;
    }

    /**
     * @return string
     */
    public function getDeviceModel() {
        return $this->device_model;
    }

    /**
     * @return string
     */
    public function getDeviceCodename() {
        return $this->device_codename;
    }

    /**
     * @return bool
     */
    public function isAb() {
        return $this->is_ab;
    }

    /**
     * @return string
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getAndroidVersion() {
        return $this->androidVersion;
    }

    /**
     * @return bool
     */
    public function isActual() {
        $buildDate = date_create($this->getBuildDate());
        if ($buildDate === false) {
            return false;
        }
        return date_diff($buildDate, date_create('now'))->days <= 14;
    }

    /**
     * @return int
     */
    public function getLastBuildDelta() {
        $buildDate = date_create($this->getBuildDate());
        if ($buildDate === false) {
            return false;
        }
        if ($buildDate > date_create('now')) {
            return -1;
        }
        return (int)date_diff($buildDate, date_create('now'))->days;
    }

    /**
     * @return bool
     */
    public function isFinal() {
        return $this->getAndroidVersion() === self::ANDROID_VERSION_9 || $this->getAndroidVersion() === self::ANDROID_VERSION_10 || $this->getAndroidVersion() === self::ANDROID_VERSION_11;
    }
}
