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
use milesherndon\commercereports\widgets\BatchTransactionsWidget;
use milesherndon\commercereports\widgets\CustomerOrderHistoryWidget;
use milesherndon\commercereports\widgets\FullInventoryWidget;
use milesherndon\commercereports\widgets\InventoryQuantityAdjustmentsWidget;
use milesherndon\commercereports\widgets\InventorySoldWidget;
use milesherndon\commercereports\widgets\SalesTaxWidget;

use Craft;
use craft\base\Plugin;
use craft\commerce\elements\Variant;
use craft\commerce\elements\Product;
use craft\commerce\events\CustomizeProductSnapshotFieldsEvent;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Dashboard;
use craft\services\Plugins;
use craft\services\Elements;
use craft\web\UrlManager;

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
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['batchTransactions'] = 'commerce-reports/default/batch-transactions';
                $event->rules['customerOrderHistory'] = 'commerce-reports/default/customer-order-history';
                $event->rules['fullInventory'] = 'commerce-reports/default/full-inventory';
                $event->rules['inventoryQuantityAdjustments'] = 'commerce-reports/default/inventory-quantity-adjustments';
                $event->rules['inventorySold'] = 'commerce-reports/default/inventory-sold';
                $event->rules['salesTax'] = 'commerce-reports/default/indiana-sales-tax';
            }
        );

        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types = $this->_getWidgetTypes($event->types);
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

        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_SAVE_ELEMENT,
            function(Event $event) {
                $this->inventoryService->setCurrentQuantities($event->element);
            }
        );

        Event::on(
            Elements::class,
            Elements::EVENT_AFTER_SAVE_ELEMENT,
            function(Event $event) {
                $this->inventoryService->saveQuantityAdjustments($event->element);
            }
        );

        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_DELETE_ELEMENT,
            function(Event $event) {
                $this->inventoryService->saveQuantityAdjustments($event->element, true);
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

    private function _getWidgetTypes($eventTypes) {
        return array_merge($eventTypes, [
            BatchTransactionsWidget::class,
            CustomerOrderHistoryWidget::class,
            FullInventoryWidget::class,
            InventoryQuantityAdjustmentsWidget::class,
            InventorySoldWidget::class,
            SalesTaxWidget::class,
        ]);
    }
}
