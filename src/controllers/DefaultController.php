<?php
/**
 * Commerce Reports plugin for Craft CMS 3.x
 *
 * Plugin to run specific Commerce reports
 *
 * @link      https://milesherndon.com
 * @copyright Copyright (c) 2019 MilesHerndon
 */

namespace milesherndon\commercereports\controllers;

use milesherndon\commercereports\CommerceReports;
use milesherndon\commercereports\services\CommerceReportsService;

use Craft;
use craft\web\Controller;
use craft\web\Request;

/**
 * @author    MilesHerndon
 * @package   CommerceReports
 * @since     1.0.0
 */
class DefaultController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = [
        'inventory-sold',
        'full-inventory',
        'customer-order-history',
        'batch-transactions',
        'indiana-sales-tax',
    ];

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our module's index action URL,
     * e.g.: actions/commerce-reports/default
     *
     * @return mixed
     */

    public function actionInventorySold()
    {
        $request = Craft::$app->getRequest()->get();

        $products = CommerceReports::getInstance()->commerceReportsService->getInventory($request, true);

        return Craft::$app->getResponse()->sendFile($products);
    }

    public function actionFullInventory()
    {
        $request = Craft::$app->getRequest()->get();

        $products = CommerceReports::getInstance()->commerceReportsService->getInventory($request, false);

        return Craft::$app->getResponse()->sendFile($products);
    }

    public function actionCustomerOrderHistory()
    {
        $request = Craft::$app->getRequest()->get();

        $orders = CommerceReports::getInstance()->commerceReportsService->getOrdersByCustomer($request);

        return Craft::$app->getResponse()->sendFile($orders);
    }

    public function actionBatchTransactions()
    {
        // get data from dashboard request
        $request = Craft::$app->getRequest()->get();

        $batch = CommerceReports::getInstance()->commerceReportsService->batchTransactions($request);

        // CommerceAddonsPlugin::log(print_r('actionBatchTransactions', true));

        return Craft::$app->getResponse()->sendFile($batch);
    }

    public function actionIndianaSalesTax()
    {
        $request = Craft::$app->getRequest()->get();

        $orders = CommerceReports::getInstance()->commerceReportsService->getIndianaSalesTax($request);

        return Craft::$app->getResponse()->sendFile($orders);
    }
}
