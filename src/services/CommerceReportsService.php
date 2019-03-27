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

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;

/**
 * @author    MilesHerndon
 * @package   CommerceReports
 * @since     1.0.0
 */
class CommerceReportsService extends Component
{
    // Private Properties
    // =========================================================================

    private $accountCodes = [
        'pay' => [
            'desc'=>'PAY',
            'acct#'=>'01-00-00-1007-000'
        ],
        'ar/pp' => [
            'desc'=>'AR, PP',
            'acct#'=>'01-00-00-1054-000'
        ],
        'inventory' => [
            'desc'=>'INV',
            'acct#'=>'01-00-00-1073-000'
        ],
        'shipping' => [
            'desc'=>'Freight_#, Handling_#',
            'acct#'=>'01-02-00-8220-000'
        ],
        'product' => [
            'desc'=>'TR',
            'acct#'=>'01-02-00-4135-000'
        ],
        'tax' => [
            'desc'=>'TAX/IN',
            'acct#'=>'01-02-00-8026-000'
        ],
        'cogs' => [
            'desc'=>'COGS',
            'acct#'=>'01-02-00-8240-000'
        ],
    ];

    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin/module file, call it like this:
     *
     *     CommerceReports::$instance->commerceReportsModuleService->batchTransactions()
     *
     * @return json
     */
    public function batchTransactions($request)
    {
        $datesInSeconds = $this->_formatTimes($request, 'U', true);

        $startDateInSeconds = (int)$datesInSeconds['start'];
        $endDateInSeconds = (int)$datesInSeconds['end'];
        $incrementer = 86400;
        $dateCounter = $startDateInSeconds;

        $files = [];
        $tempPath = ReportFileHelper::getStoragePath('commerce-reports-batch');

        $orderSpreadsheet = $this->_getOrdersWithDetails($request, $tempPath);
        array_push($files, $orderSpreadsheet);

        for ($dateCounter = $startDateInSeconds; $dateCounter < $endDateInSeconds; $dateCounter += $incrementer) {

            $params = [
                0 => [
                    'value' => $dateCounter
                ],
                1 => [
                    'value' => $dateCounter + $incrementer
                ]
            ];

            $dates = $this->_formatTimes($params, 'Y-m-d H:i');
            $datesName = $this->_formatTimes($params, 'Y-m-d');
            $datesReport = $this->_formatTimes($params, 'mdY');
            // CommerceAddonsPlugin::log(print_r($dates, true));

            $fileName = $tempPath . '/' . date('Y-m-d', strtotime("+1 day", strtotime($datesName['start'])));
            array_push($files, $fileName.'.txt');

            $orders = Order::find()
                ->isCompleted(true)
                ->orderBy('dateOrdered asc')
                ->dateOrdered(["and", ">= ".$dates['start'], "< ".$dates['end']])
                ->all();

            // NOTE: CSV
            $csvFileName = $fileName.'.csv';

            $fp = fopen($csvFileName, 'w');

            fputcsv($fp, [
                'Batch Number',
                'Account Number',
                'Post Date',
                'Type',
                'Journal',
                'Journal Reference',
                'Amount'
            ]);

            // $keys = array('shipping', 'ar/pp', 'inventory', 'product', 'cogs', 'pay', 'tax');
            $keys = array('shipping', 'inventory', 'product', 'cogs', 'pay', 'tax');
            $initialTemplateArray = array_fill_keys($keys, 0);

            foreach ($orders as $order) {
                $initialTemplateArray['shipping'] += floatval($order->getAdjustmentsTotalByType("shipping")) * -1;
                // $initialTemplateArray['ar/pp'] += floatval($order->totalPaid);
                $initialTemplateArray['inventory'] += floatval($this->_totalProductWholesale($order->getLineItems())) * -1;
                $initialTemplateArray['product'] += floatval($order->itemTotal - $order->getAdjustmentsTotalByType("tax")) * -1;
                $initialTemplateArray['cogs'] += floatval($this->_totalProductWholesale($order->getLineItems()));
                $initialTemplateArray['pay'] += floatval($order->itemTotal + $order->getAdjustmentsTotalByType("shipping"));
                $initialTemplateArray['tax'] += floatval($order->getAdjustmentsTotalByType("tax")) * -1;
            }

            $initialTemplateArray = array_map(
                function($value){
                    return number_format((float)$value, 2, '.', '');
                }, $initialTemplateArray);

            $fileContentsString = '';
            $fileContentsString .= 'DIV=04 SEP=|\r\n';
            $fileContentsString .= '1|CRAFT|'.$datesReport['end'].'|CRAFT|CRAFT IMPORT'."|\r\n";

            foreach ($initialTemplateArray as $key => $value) {
                $fileContentsString .= '4|'.$this->accountCodes[$key]['acct#'].'|'.$this->accountCodes[$key]['desc'].'||CRAFT||'.(string)$value."|\r\n";

                fputcsv($fp, [
                    '',
                    $this->accountCodes[$key]['acct#'],
                    $datesName['start'], $value > 0 ? 'D' : 'C',
                    'Craft Journal',
                    $this->accountCodes[$key]['desc'],
                    (string)abs($value)
                ]);

            }

            $file = file_put_contents($fileName.".txt", $fileContentsString.PHP_EOL , LOCK_EX);

            fclose($fp);
            array_push($files, $csvFileName);

        }

        $zip = $this->_generateZip($files, $request);

        return $zip;
    }

    public function getIndianaSalestax($request)
    {
        // $dates = $this->_formatTimes($request, 'Y-m-d H:i');

        $orders = $this->_getOrdersByDate($request);

        $ordersWithTax = [];
        foreach ($orders as $order) {
            if ($order->getAdjustmentsTotalByType("tax") > 0) {
                array_push($ordersWithTax, $order);
            }
        }

        $endDateString = $this->_formatTimes($request, 'Ymd')['end'];
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

    public function getOrdersByCustomer($request)
    {
        $customerEmailPreformatted = $request['customer'];
        $customerEmail = str_replace('\@', '@', $customerEmailPreformatted);

        $orders = $this->_getOrdersByDate($request);

        $customerOrders = [];

        foreach ($orders as $order) {
            if ($order->email == $customerEmail) {
                array_push($customerOrders, $order);
            }
        }

        $nameTemplate = $customerEmail;
        $filePath = ReportFileHelper::getStoragePath('commerce-reports-customer');
        $csvFileName = 'customer_'.$nameTemplate.'.csv';
        $fileName = $filePath . '/' . $csvFileName;

        $fp = fopen($fileName, 'w');

        fputcsv($fp, [
            'Order #',
            'Date Ordered',
            'Customer',
            'Purchase Total',
            'Status'
        ]);

        foreach ($customerOrders as $order) {
            $address = $order->shippingAddress;
            $row = [
                'Order #' => (string)$order->shortNumber,
                'Date Ordered' => $order->dateOrdered->format('Y-m-d'),
                'Customer' => $address->firstName . ' ' . $address->lastName,
                'Purchase Total' => $order->itemSubtotal,
                'Status' => $order->status,
            ];

            fputcsv($fp, $row);
        }
        fclose($fp);

        return $fileName;
    }


    // NOTE: For inventorySold and totalInventory reports
    public function getInventory($request, $sold = true)
    {
        $variants = Variant::find()
            ->orderBy('sku asc')
            ->all();

        if ($sold) {
            $orders = $this->_getOrdersByDate($request);
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

    private function _formatTimes($request, $format, $timezoneAdjusted = false)
    {
      $rawStartDate = $request[0]['value'] ?? $request['startDate'];
      $rawEndDate = $request[1]['value'] ?? $request['endDate'];

      // $timeZone = new \DateTimeZone('America/Indiana/Indianapolis');

      if (is_int($rawStartDate) && strlen($rawStartDate) == 10) {
        $startDate = new \DateTime();
        $startDate->setTimestamp($rawStartDate);
        $endDate = new \DateTime();
        $endDate->setTimestamp($rawEndDate);

      }
      else{
        $startDate = new \DateTime($rawStartDate);
        // $startDate -> setTimezone($timeZone);
        // This modification is -2 hours to get to 10pm the previous day, then +4 hours to make up for timezone, because timezone is not working somehow...
        // $startDate -> modify('-2 hours');
        if ($timezoneAdjusted) {
          $startDate->modify('+2 hours');
        }

        $endDate = new \DateTime($rawEndDate);
        // $endDate -> setTimezone($timeZone);
        // This modification is +22 hours to get to 10pm the same day, then +4 hours to make up for timezone, because timezone is not working somehow...
        // $endDate -> modify('+22 hours');
        if ($timezoneAdjusted) {
          $endDate->modify('+26 hours');
        }
      }

      $startDateString = $startDate->format($format);
      $endDateString = $endDate->format($format);

      return array(
        'start' => $startDateString,
        'end' => $endDateString,
      );
    }

    private function _generateCSVs($combinedOrders)
    {
        $filePaths = array();
        foreach ($combinedOrders as $dateKey => $valueArray) {

            $fileName = ReportFileHelper::getStoragePath('commerce-reports-orders') . '/' . $dateKey.'.csv';

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

    private function _generateZip($filepaths, $request)
    {
        $zip = new \ZipArchive;

        $endDateString = $this->_formatTimes($request, 'Ymd')['end'];
        $rawStartDate = $request[0]['value'] ?? $request['startDate'];
        $startDate = new \DateTime($rawStartDate);
        $startDateString = $startDate -> format('Ymd');

        $zipNameTemplate = $startDateString.'_'.$endDateString;

        $zipPath = ReportFileHelper::getStoragePath('commerce-reports-batch');

        $zipFileName = 'batch-transactions_'.$zipNameTemplate.'.zip';
        $zipName = $zipPath . '/' . $zipFileName;

        if (file_exists($zipName)) {
            FileHelper::unlink($zipName);
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

    private function _totalProductWholesale($lineItems)
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

    private function _getOrdersByDate($request)
    {
        if (empty($request)) {
            return false;
        }

        $dates = $this->_formatTimes($request, 'c', true);

        return Order::find()
            ->isCompleted(true)
            ->orderBy('dateOrdered asc')
            ->dateOrdered(["and", ">=".$dates['start'], "<".$dates['end']])
            ->all();
    }

    // // NOTE: Part of batchTransations
    private function _getOrdersWithDetails($request, $filepath)
    {
        if (empty($request)) {
            return false;
        }

        $orders = $this->_getOrdersByDate($request);

        $fileName = $filepath . '/' . uniqid() . '.csv';

        $fp = fopen($fileName, 'w');

        fputcsv($fp, [
            'Order number',
            'Status',
            'Product total',
            'Tax total',
            'Shipping total',
            'Wholesale total',
            'total paid',
            'date ordered',
            'date paid'
        ]);

        foreach ($orders as $order) {
            $row = [
                'Order number' => (string)$order->getShortNumber(),
                'Status' => $order->getOrderStatus(),
                'Product total' => number_format(floatval($order->getItemTotal() - $order->getAdjustmentsTotalByType("tax")), 2, '.', ''),
                'Tax total' => number_format((float)$order->getAdjustmentsTotalByType("tax"), 2, '.', ''),
                'Shipping total' => number_format((float)$order->getAdjustmentsTotalByType("shipping"), 2, '.', ''),
                'Wholesale total' => floatval($this->_totalProductWholesale($order->getLineItems())),
                'total paid' => number_format(floatval($order->getItemTotal() + $order->getAdjustmentsTotalByType("shipping")), 2, '.', ''),
                'date ordered' => $order->dateOrdered->format('n/d/Y'),
                'date paid' => $order->datePaid->format('n/d/Y'),
            ];

            fputcsv($fp, $row);
        }
        fclose($fp);

        return $fileName;
    }
}
