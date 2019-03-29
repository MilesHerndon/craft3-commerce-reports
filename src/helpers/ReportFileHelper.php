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
use milesherndon\commercereports\helpers\ReportDateTimeHelper;

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
            self::createDirectory($path);
        }

        return $path;
    }

    /**
     * Create zip file with provided filepaths
     *
     * @param $filepaths
     * @param $request
     * @return string
     */
    public static function generateZip($filepaths, $request)
    {
        $zip = new \ZipArchive;

        $endDateString = ReportDateTimeHelper::formatTimes($request, 'Ymd')['end'];
        $rawStartDate = $request[0]['value'] ?? $request['startDate'];
        $startDate = new \DateTime($rawStartDate);
        $startDateString = $startDate -> format('Ymd');

        $zipNameTemplate = $startDateString.'_'.$endDateString;

        $zipPath = self::getStoragePath('commerce-reports-batch');

        $zipFileName = 'batch-transactions_'.$zipNameTemplate.'.zip';
        $zipName = $zipPath . '/' . $zipFileName;

        if (file_exists($zipName)) {
            self::unlink($zipName);
        }

        if ($zip->open($zipName, \ZipArchive::CREATE) !== TRUE) {
            return false;
        }

        foreach ($filepaths as $path) {
            $pathExplode = explode('/',$path);
            $fileName = end($pathExplode);

            $zip->addFile($path, $fileName);
        }

        $zip->close();

        return $zipName;
    }

    /**
     * Create CSV
     *
     * @param $combinedOrders
     * @return string
     */
    public static function generateCSVs($combinedOrders)
    {
        $filePaths = array();
        foreach ($combinedOrders as $dateKey => $valueArray) {

            $fileName = self::getStoragePath('commerce-reports-orders') . '/' . $dateKey.'.csv';

            $fp = fopen($fileName, 'w');

            fputcsv($fp, [
                'date',
                'orderNumber(s)',
                'productTotal',
                'taxTotal',
                'shippingTotal',
                'wholesaleTotal',
                'paidTotal'
            ]);

            fputcsv($fp, $valueArray);

            fclose($fp);

            array_push($filePaths, $fileName);
        }

        return $filePaths;
    }
}
