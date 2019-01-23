<?php
/**
 * Commerce Reports plugin for Craft CMS 3.x
 *
 * Plugin to run specific Commerce reports
 *
 * @link      https://milesherndon.com
 * @copyright Copyright (c) 2019 MilesHerndon
 */

namespace milesherndon\commercereports;

use milesherndon\commercereports\services\CommerceReportsService as CommerceReportsServiceService;
// use milesherndon\commercereports\widgets\CommerceReportsWidget;
use milesherndon\commercereports\widgets\BatchTransactionsWidget;
use milesherndon\commercereports\widgets\CustomerOrderHistoryWidget;
use milesherndon\commercereports\widgets\FullInventoryWidget;
use milesherndon\commercereports\widgets\InventorySoldWidget;
use milesherndon\commercereports\widgets\SalesTaxWidget;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\services\Dashboard;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;

use yii\base\Event;

/**
 * Class CommerceReports
 *
 * @author    MilesHerndon
 * @package   CommerceReports
 * @since     1.0.0
 *
 * @property  CommerceReportsServiceService $commerceReportsService
 */
class CommerceReports extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var CommerceReports
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'commerce-reports/default';
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['inventorySold'] = 'commerce-reports/default/inventory-sold';
                $event->rules['fullInventory'] = 'commerce-reports/default/full-inventory';
                $event->rules['customerOrderHistory'] = 'commerce-reports/default/customer-order-history';
                $event->rules['batchTransactions'] = 'commerce-reports/default/batch-transactions';
                $event->rules['salesTax'] = 'commerce-reports/default/indiana-sales-tax';
            }
        );

        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            function (RegisterComponentTypesEvent $event) {
                // $event->types[] = CommerceReportsWidget::class;
                $event->types[] = BatchTransactionsWidget::class;
                $event->types[] = CustomerOrderHistoryWidget::class;
                $event->types[] = FullInventoryWidget::class;
                $event->types[] = InventorySoldWidget::class;
                $event->types[] = SalesTaxWidget::class;
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Craft::info(
            Craft::t(
                'commerce-reports',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

}
