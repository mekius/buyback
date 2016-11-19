<?php
/**
 * Created by PhpStorm.
 * User: nhughart
 * Date: 11/19/16
 * Time: 1:01 AM
 */

namespace App\Models;

use Pheal\Core\Config as PhealConfig;
use Pheal\Pheal;
use Illuminate\Support\Facades\Storage;

class EveApi
{
    private static $pheal = null;

    /**
     * Get Pheal XML API Instance
     *
     * @return null|Pheal
     */
    public static function getXmlApiInstance()
    {
        if (self::$pheal === null) {
            Storage::makeDirectory('cache/pheal');
            PhealConfig::getInstance()->cache = new \Pheal\Cache\FileStorage(self::getLocalStoragePath('cache/pheal/'));
            PhealConfig::getInstance()->additional_request_parameters = ['accessToken' =>  \ESI\Configuration::getDefaultConfiguration()->getAccessToken()];

            // TOOD: Can enable this one access tokens are working
            //Config::getInstance()->access = new \Pheal\Access\StaticCheck();

            self::$pheal = new Pheal(null, null, 'char');
            self::$pheal->setAccess('Character');
        }

        return self::$pheal;
    }

    /**
     * Return base path used by Storage class for local disk methods
     *
     * @param string $path Relative path to generate.
     * @return string
     */
    private static function getLocalStoragePath($path)
    {
        return config('filesystems.disks.local.root') . '/' . $path;
    }
}