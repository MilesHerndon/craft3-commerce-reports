<?php
/**
 * Commerce Reports plugin for Craft CMS 3.x
 *
 * Plugin to run specific Commerce reports
 *
 * @link      https://milesherndon.com
 * @copyright Copyright (c) 2019 MilesHerndon
 */

namespace milesherndon\commercereports\widgets;

use milesherndon\commercereports\CommerceReports;
use milesherndon\commercereports\assetbundles\commercereportswidgetwidget\CommerceReportsWidgetWidgetAsset;

use Craft;
use craft\base\Widget;
use craft\elements\db\ElementQuery;
use craft\commerce\elements\Order;

/**
 * Commerce Reports Widget
 *
 * @author    MilesHerndon
 * @package   CommerceReports
 * @since     1.0.0
 */
class CommerceReportsWidget extends Widget
{

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $reportType;
    public $customer;
    public $startDate;
    public $endDate;


    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('commerce-reports', 'Commerce Reports');
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias("@milesherndon/commercereports/assetbundles/commercereportswidgetwidget/dist/img/CommerceReportsWidget-icon.svg");
    }

    /**
     * @inheritdoc
     */
    public static function maxColspan()
    {
        return null;
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules = array_merge(
            $rules,
            [
                ['reportType', 'string'],
                ['reportType', 'default', 'value' => 'full-inventory']
            ]
        );
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        $date = new \DateTime;

        return Craft::$app->getView()->renderTemplate(
            'commerce-reports/_components/widgets/CommerceReportsWidget_settings',
            [
                'widget' => $this,
                'reportTypes' => $this->_getReportTypes(),
                'customers' => $this->_getCustomers(),
                'settings' => $this->settings,
                'currentDate' => $date
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml()
    {
        Craft::$app->getView()->registerAssetBundle(CommerceReportsWidgetWidgetAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'commerce-reports/_components/widgets/CommerceReportsWidget_body',
            [
                'widget' => $this,
                'settings' => $this->settings,
            ]
        );
    }

    private function _getReportTypes()
    {
        $types = [
            [
                'value' => 'batch-transactions',
                'label' => 'Batch Transactions',
            ],
            [
                'value' => 'customer-order-history',
                'label' => 'Customer Order History',
            ],
            [
                'value' => 'indiana-sales-tax',
                'label' => 'Indiana Sales Tax',
            ],
            [
                'value' => 'inventory-quantity-adjustments',
                'label' => 'Inventory Quantity Adjustments',
            ],
            [
                'value' => 'inventory-sold',
                'label' => 'Inventory Sold',
            ],
            [
                'value' => 'full-inventory',
                'label' => 'Full Inventory',
            ],
        ];

        return $types;
    }

    private function _getCustomers()
    {
        $orders = new Order;
        $orders->isCompleted = true;
        $allOrders = $orders->find()->orderBy('dateOrdered asc')->limit(100)->all();

        $customersArray = [];

        foreach ($allOrders as $order) {
            if (!in_array($order->email, $customersArray)) {
                array_push($customersArray, $order->email);
            }
        }
        sort($customersArray, SORT_STRING | SORT_FLAG_CASE);

        $customers = [];
        foreach ($customersArray as $email) {
            $formattedEmail = str_replace('@', '\@', $email);
            array_push(
                $customers,
                [
                    'value' => $formattedEmail,
                    'label' => $email,
                ]
            );
        }

        return $customers;
    }
}
