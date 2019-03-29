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
use milesherndon\commercereports\helpers\ReportDateTimeHelper;
use milesherndon\commercereports\helpers\ReportFileHelper;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Variant;
use craft\commerce\elements\Product;

/**
 * @author    MilesHerndon
 * @package   CommerceReports
 * @since     1.0.5
 */
class TaxService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Gets indiana sales tax items
     *
     * @param $request
     * @return string
     */
    public function getIndianaSalestax($request)
    {
        // $dates = ReportDateTimeHelper::formatTimes($request, 'Y-m-d H:i');

        $orders = CommerceReports::$plugin->orderService->getOrdersByDate($request);

        $ordersWithTax = [];
        foreach ($orders as $order) {
            if ($order->getAdjustmentsTotalByType("tax") > 0) {
                array_push($ordersWithTax, $order);
            }
        }

        $endDateString = ReportDateTimeHelper::formatTimes($request, 'Ymd')['end'];
        $rawStartDate = $request['startDate'];
        $startDate = new \DateTime($rawStartDate);
        $startDateString = $startDate->format('Ymd');

        $nameTemplate = $startDateString . '_' . $endDateString;
        $filePath = ReportFileHelper::getStoragePath('commerce-reports-sales-tax');
        $csvFileName = 'sales-tax_' . $nameTemplate.'.csv';
        $fileName = $filePath . '/' . $csvFileName;

        $fp = fopen($fileName, 'w');

        fputcsv($fp, [
            'Order #',
            'Customer',
            'Date Ordered',
            'Taxable Amount'
        ]);

        foreach ($ordersWithTax as $order) {
            $address = $order->shippingAddress;
            $row = [
                'Order #' => (string)$order->shortNumber,
                'Customer' => $address->firstName . ' ' . $address->lastName,
                'Date Ordered' => $order->dateOrdered->format('Y-m-d'),
                'Taxable Amount' => $order->itemSubtotal,
            ];

            fputcsv($fp, $row);
        }

        fclose($fp);

        return $fileName;
    }
}
