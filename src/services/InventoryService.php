<?php
/**
 * Commerce Reports plugin for Craft CMS 3.x
 *
 * Plugin to run specific Commerce reports
 *
 * @link      https://milesherndon.com
 * @copyright Copyright (c) 2019 MilesHerndon
 */

namespace milesherndon\commercereports\services;

use milesherndon\commercereports\CommerceReports;
use milesherndon\commercereports\helpers\ReportFileHelper;

use Craft;
use craft\base\Component;

/**
 * @author    MilesHerndon
 * @package   CommerceReports
 * @since     1.0.0
 */
class InventoryService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Returns a list of changed inventory quantities
     *
     * @param $request
     * @return json
     */
    public function getInventoryQuantityModifications($request)
    {

        $tempPath = ReportFileHelper::getStoragePath('commerce-reports-inventory-quantities');
        $fileName = 'inventory_quantity_modifications_' . time() . '.csv';

        $csv = $tempPath . '/' . $fileName;
        $fp = fopen($csv, 'w');

        fputcsv($fp, array('SKU','Title','Price','Wholesale','Qty Added','Qty Removed','Date of Action','Total Remaining in Stock'));

        fclose($fp);

        return $csv;
    }
}
