<?php
/**
 * Commerce Reports plugin for Craft CMS 3.x
 *
 * Plugin to run specific Commerce reports
 *
 * @link      https://milesherndon.com
 * @copyright Copyright (c) 2019 MilesHerndon
 */

namespace milesherndon\commercereports\assetbundles\commercereportswidgetwidget;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    MilesHerndon
 * @package   CommerceReports
 * @since     1.0.0
 */
class CommerceReportsWidgetWidgetAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@milesherndon/commercereports/assetbundles/commercereportswidgetwidget/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/CommerceReportsWidget.js',
        ];

        $this->css = [
            'css/CommerceReportsWidget.css',
        ];

        parent::init();
    }
}
