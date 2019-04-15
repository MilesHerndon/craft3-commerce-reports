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
use milesherndon\commercereports\helpers\ReportDateTimeHelper;
use milesherndon\commercereports\records\AdjustmentRecord;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;

/**
 * @author    MilesHerndon
 * @package   CommerceReports
 * @since     1.1.0
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
     * @return string
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

        $dates = ReportDateTimeHelper::formatTimes($request, 'c', true);

        $adjustmentRecords = AdjustmentRecord::find()
                                ->where(['>=', 'dateCreated', $dates['start']])
                                ->andWhere(['<', 'dateCreated', $dates['end']])
                                ->all();

        fputcsv($fp, array('SKU','Title','Price','Wholesale','Qty Added','Qty Removed','Date of Action','Stock Remaining'));

        foreach ($adjustmentRecords as $record) {
            $row = [
                'SKU' => $record->sku,
                'Title' => $record->title,
                'Price' => $record->price,
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
                    if (isset($existingVariant->stock)) {
                        $this->currentQuantities[$variant->sku] = $existingVariant->stock;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Save version product quantity adjustments
     *
     * @param $element
     * @param $elementDeleted
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
                $record->variantId = $element->id;
                $record->sku = $element->sku;
                $record->stockRemaining = $element->stock;
                $record->wholesale = $element->getProduct()->productWholesalePrice ?? 0;
                $record->price = $element->price;

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

    /**
     * Get inventory items for Sold and Total reports
     *
     * @param $request
     * @param $sold
     * @return $csv
     */
    public function getInventory($request, $sold = true)
    {
        $variants = Variant::find()
            ->orderBy('sku asc')
            ->all();

        if ($sold) {
            $orders = CommerceReports::$plugin->orderService->getOrdersByDate($request);
        }

        $tempPath = ReportFileHelper::getStoragePath('commerce-reports-inventory');
        $fileName = 'inventory_'.time().'.csv';

        $name = $tempPath . '/' . $fileName;
        $fp = fopen($name, 'w');

        if ($sold) {
            fputcsv($fp, array('SKU','Title','Price','Wholesale','On hand/stock','Qty sold'));
        } else {
            fputcsv($fp, array('SKU','Title','Price','Wholesale','Weight','On hand/stock'));
        }

        foreach ($variants as $variant) {

            try {
                $product = $variant->getProduct();
                $wholesale = $product->getType()->handle != 'uniqueImagesForEachVariant' ? $product->productWholesalePrice : $variant->productWholesalePrice;
                $wholesale = empty($wholesale) ? 0 : $wholesale;

                if ($sold) {
                    $quantitySold = 0;

                    foreach ($orders as $order) {
                        $lineItems = $order->getLineItems();

                        foreach ($lineItems as $lineItem) {
                            $lineItemVariant = $lineItem->purchasable;
                            if ($lineItem->getSku() == $variant->getSku()) {
                                $qty = $lineItem->qty;
                                $quantitySold += $qty;
                            }
                        }
                    }
                }

                $row = [
                    'sku' => $variant->getSku(),
                    'title' => $product->title,
                    'price' => number_format((float)$variant->getPrice(), 2, '.', ''),
                    'wholesale' => number_format((float)$wholesale, 2, '.', ''),
                    'quantity' => $variant->stock,
                ];

                // NOTE: add quantity sold from orders for sold
                if ($sold) {
                    $row['sold'] = $quantitySold;
                }
                // NOTE: add weight for total
                else{
                    $weight = array('weight'=>$variant->weight);
                    array_splice( $row, 4, 0, $weight );
                }

                fputcsv($fp, $row);
            } catch(\Exception $e) {

            }
        }
        fclose($fp);

        return $name;
    }

    /**
     * Get wholesale price of line item.
     *
     * @param $lineItems
     * @return float
     */
    public function totalProductWholesale($lineItems)
    {
        $totalWholesale = 0;
        foreach ($lineItems as $lineItem) {
            $qty = $lineItem->qty;
            $productId = $lineItem->snapshot['product']['id'];

            $product = Product::find()->id($productId)->status(null)->one();

            if (!is_numeric($product['productWholesalePrice'])) {
                $productId = $lineItem->snapshot['purchasableId'];
                $product = Variant::find()->id($productId)->one();
            }

            $totalWholesale += ($product['productWholesalePrice'] * $qty);
        }

        return $totalWholesale;
    }
}
