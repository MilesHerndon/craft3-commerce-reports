<?php
/**
 * Commerce Reports plugin for Craft CMS 3.x
 *
 * Plugin to run specific Commerce reports
 *
 * @link      https://milesherndon.com
 * @copyright Copyright (c) 2019 MilesHerndon
 */

namespace milesherndon\commercereports\records;

use milesherndon\commercereports\CommerceReports;

use Craft;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * @author    MilesHerndon
 * @package   CommerceReports
 * @since     1.1.0
 */
class AdjustmentRecord extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%commercereports_quantityversions}}';
    }
}
