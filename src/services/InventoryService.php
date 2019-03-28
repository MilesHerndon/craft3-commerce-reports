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
use milesherndon\commercereports\records\AdjustmentRecord;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Variant;
use craft\commerce\elements\Product;

/**
 * @author    MilesHerndon
 * @package   CommerceReports
 * @since     1.0.5
 */
class InventoryService extends Component
{
    // Private Properties
    // =========================================================================

    /**
     * @var array
     */
    private $currentQuantities = [];

    // Public Methods
    // =========================================================================

    /**
     * Returns a list of changed inventory quantities
     *
     * @param $request
     * @return $csv
     */
    public function getInventoryQuantityAdjustments($request)
    {
        $tempPath = ReportFileHelper::getStoragePath('commerce-reports-inventory-quantities');
        $fileName = 'inventory_quantity_adjustments_' . time() . '.csv';

        $csv = $tempPath . '/' . $fileName;
        $fp = fopen($csv, 'w');

        if (empty($request)) {
            return false;
        }

        $dates = CommerceReports::$plugin->commerceReportsService->_formatTimes($request, 'c', true);

        $adjustmentRecords = AdjustmentRecord::find()
                                ->where(['>=', 'dateCreated', $dates['start']])
                                ->andWhere(['<', 'dateCreated', $dates['end']])
                                ->all();

        fputcsv($fp, array('SKU','Title','Price','Wholesale','Qty Added','Qty Removed','Date of Action','Stock Remaining'));

        foreach ($adjustmentRecords as $record) {
            $row = [
                'SKU' => $record->sku,
                'Title' => $record->title,
                'Price' => 0,
                'Wholesale' => $record->wholesale,
                'Qty Added' => $record->qtyAdded,
                'Qty Removed' => $record->qtyDeleted,
                'Date of Action' => $record->dateCreated,
                'Stock Remaining' => $record->stockRemaining,
            ];

            fputcsv($fp, $row);
        }

        fclose($fp);

        return $csv;
    }

    /**
     * Set current quantities array
     *
     * @param $element
     * @return boolean
     */
    public function setCurrentQuantities($element)
    {
        if (is_a($element, Product::class)) {
            foreach ($element->getVariants() as $variant) {
                if ($variant->id) {
                    $existingVariant = Variant::findOne($variant->id);
                    $this->currentQuantities[$variant->sku] = $existingVariant->stock;
                }
            }
        }

        return true;
    }

    /**
     * Save version product quantity adjustments
     *
     * @param $element
     * @return boolean
     */
    public function saveQuantityAdjustments($element, $elementDeleted = false)
    {
        if (is_a($element, Variant::class)) {
            $record = new AdjustmentRecord;
            $currentQuantity = (isset($this->currentQuantities[$element->sku]) ? $this->currentQuantities[$element->sku] : 0);

            if ($currentQuantity !== $element->stock) {
                $record->title = (!is_null($element->title) ? $element->title : $element->getProduct()->title);
                $record->productId = $element->productId;
                $record->sku = $element->sku;
                $record->stockRemaining = $element->stock;
                $record->wholesale = $element->getProduct()->productWholesalePrice ?? 0;

                if ($currentQuantity < $element->stock) {
                    $record->qtyAdded = $element->stock - $currentQuantity;
                } elseif ($currentQuantity > $element->stock) {
                    $record->qtyDeleted = $currentQuantity - $element->stock;
                }

                if ($elementDeleted) {
                    $currentQuantity = 0;
                    $record->stockRemaining = 0;
                    $record->qtyAdded = 0;
                    $record->qtyDeleted = $element->stock;
                    $record->productDeleted = true;
                }

                $record->validate();
                if ($record->hasErrors()) {
                    return false;
                }

                $transaction = Craft::$app->db->beginTransaction();

                try {
                    if ($record->save(false)) {
                        $transaction->commit();
                    }
                } catch (\Exception $e) {
                    $transaction->rollback();
                    throw $e;
                }

                return true;
            }
        }
    }
}
