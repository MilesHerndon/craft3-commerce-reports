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
class SalesTaxWidget extends Widget
{

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $reportType;
    public $startDate;
    public $endDate;


    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('commerce-reports', 'Commerce Reports - Sales Tax');
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
                ['reportType', 'default', 'value' => 'indiana-sales-tax']
            ]
        );
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate(
            'commerce-reports/_components/widgets/CommerceReportsWidget_settings',
            [
                'widget' => $this,
                'settings' => $this->settings,
                'reportType' => 'indiana-sales-tax'
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
}
