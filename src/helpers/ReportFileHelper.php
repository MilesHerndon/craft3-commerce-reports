<?php
/**
 * Commerce Reports plugin for Craft CMS 3.x
 *
 * Plugin to run specific Commerce reports
 *
 * @link      https://milesherndon.com
 * @copyright Copyright (c) 2019 MilesHerndon
 */

namespace milesherndon\commercereports\helpers;

use milesherndon\commercereports\CommerceReports;

use Craft;
use craft\helpers\FileHelper;

/**
 * @author    MilesHerndon
 * @package   CommerceReports
 * @since     1.0.5
 */
class ReportFileHelper extends FileHelper
{
    /**
     * Get temp storage directory path
     *
     * @param $directory
     * @return string
     */
    public static function getStoragePath($directory) {
        $path = Craft::$app->path->getTempPath() . '/' . $directory;

        if (!file_exists($path)) {
            FileHelper::createDirectory($path);
        }

        return $path;
    }
}
