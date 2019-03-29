<?php
/**
 * Commerce Reports plugin for Craft CMS 3.x
 *
 * Plugin to run specific Commerce reports
 *
 * @link      https://milesherndon.com
 * @copyright Copyright (c) 2019 MilesHerndon
 */

namespace milesherndon\commercereports\helpers;

use milesherndon\commercereports\CommerceReports;

use Craft;
use craft\helpers\DateTimeHelper;

/**
 * @author    MilesHerndon
 * @package   CommerceReports
 * @since     1.0.5
 */
class ReportDateTimeHelper extends DateTimeHelper
{
    /**
     * Adjust time format to match reporting expectations
     *
     * @param $request
     * @param $format
     * @param $timezoneAdjusted
     * @return array
     */
    public static function formatTimes($request, $format, $timezoneAdjusted = false)
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
}
