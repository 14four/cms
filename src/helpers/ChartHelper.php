<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\helpers;

use Craft;
use craft\app\db\Connection;
use craft\app\db\Query;
use craft\app\dates\DateTime;
use craft\app\services\Config;
use yii\base\Exception;


/**
 * Class ChartHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ChartHelper
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the data for a run chart, based on a given DB query, start/end dates, and the desired time interval unit.
     *
     * The query’s SELECT clause should already be set to a column aliased as `value`.
     *
     * The $options array can override the following defaults:
     *
     *  - `intervalUnit`  - The time interval unit to use ('hour', 'day', 'month', or 'year').
     *                     By default, a unit will be decided automatically based on the start/end date duration.
     *  - `categoryLabel` - The label to use for the chart categories (times). Defaults to "Date".
     *  - `valueLabel`    - The label to use for the chart values. Defaults to "Value".
     *  - `valueType`     - The type of values that are being plotted ('number', 'currency', 'percent', 'time'). Defaults to 'number'.
     *
     * @param Query      $query      The DB query that should be used
     * @param DateTime   $startDate  The start of the time duration to select (inclusive)
     * @param DateTime   $endDate    The end of the time duration to select (exclusive)
     * @param string     $dateColumn The column that represents the date
     * @param array|null $options    Any customizations that should be made over the default options
     *
     * @return array
     * @throws Exception
     */
    public static function getRunChartDataFromQuery(Query $query, DateTime $startDate, DateTime $endDate, $dateColumn, $options = [])
    {
        // Setup
        $options = array_merge([
            'intervalUnit' => null,
            'categoryLabel' => Craft::t('app', 'Date'),
            'valueLabel' => Craft::t('app', 'Value'),
            'valueType' => 'number',
        ], $options);

        $databaseType = Craft::$app->getConfig()->get('driver', Config::CATEGORY_DB);

        $craftTimezone = new \DateTimeZone(Craft::$app->getTimeZone());

        if ($options['intervalUnit'] && in_array($options['intervalUnit'], ['year', 'month', 'day', 'hour'])) {
            $intervalUnit = $options['intervalUnit'];
        } else {
            $intervalUnit = self::getRunChartIntervalUnit($startDate, $endDate);
        }

        switch ($databaseType) {
            case Connection::DRIVER_MYSQL:
                $yearSql = "YEAR([[{$dateColumn}]])";
                $monthSql = "MONTH([[{$dateColumn}]])";
                $daySql = "DAY([[{$dateColumn}]])";
                $hourSql = "HOUR([[{$dateColumn}]])";
                break;
            case Connection::DRIVER_PGSQL:
                $yearSql = "EXTRACT(YEAR FROM [[{$dateColumn}]])";
                $monthSql = "EXTRACT(MONTH FROM [[{$dateColumn}]])";
                $daySql = "EXTRACT(DAY FROM [[{$dateColumn}]])";
                $hourSql = "EXTRACT(HOUR FROM [[{$dateColumn}]])";
                break;
            default:
                throw new Exception('Unsupported connection type: '.$databaseType);
        }

        switch ($intervalUnit) {
            case 'year':
                switch ($databaseType) {
                    case Connection::DRIVER_MYSQL:
                        $sqlDateFormat = '%Y-01-01';
                        break;
                    case Connection::DRIVER_PGSQL:
                        $sqlDateFormat = 'YYYY-01-01';
                        break;
                    default:
                        throw new Exception('Unsupported connection type: '.$databaseType);
                }
                $phpDateFormat = 'Y-01-01';
                $sqlGroup = [$yearSql];
                $cursorDate = new DateTime($startDate->format('Y-01-01'), $craftTimezone);
                break;
            case 'month':
                switch ($databaseType) {
                    case Connection::DRIVER_MYSQL:
                        $sqlDateFormat = '%Y-%m-01';
                        break;
                    case Connection::DRIVER_PGSQL:
                        $sqlDateFormat = 'YYYY-MM-01';
                        break;
                    default:
                        throw new Exception('Unsupported connection type: '.$databaseType);
                }
                $phpDateFormat = 'Y-m-01';
                $sqlGroup = [$yearSql, $monthSql];
                $cursorDate = new DateTime($startDate->format('Y-m-01'), $craftTimezone);
                break;
            case 'day':
                switch ($databaseType) {
                    case Connection::DRIVER_MYSQL:
                        $sqlDateFormat = '%Y-%m-%d';
                        break;
                    case Connection::DRIVER_PGSQL:
                        $sqlDateFormat = 'YYYY-MM-DD';
                        break;
                    default:
                        throw new Exception('Unsupported connection type: '.$databaseType);
                }
                $phpDateFormat = 'Y-m-d';
                $sqlGroup = [$yearSql, $monthSql, $daySql];
                $cursorDate = new DateTime($startDate->format('Y-m-d'), $craftTimezone);
                break;
            case 'hour':
                switch ($databaseType) {
                    case Connection::DRIVER_MYSQL:
                        $sqlDateFormat = '%Y-%m-%d %H:00:00';
                        break;
                    case Connection::DRIVER_PGSQL:
                        $sqlDateFormat = 'YYYY-MM-DD HH24:00:00';
                        break;
                    default:
                        throw new Exception('Unsupported connection type: '.$databaseType);
                }
                $phpDateFormat = 'Y-m-d H:00:00';
                $sqlGroup = [$yearSql, $monthSql, $daySql, $hourSql];
                $cursorDate = new DateTime($startDate->format('Y-m-d'), $craftTimezone);
                break;
            default:
                throw new Exception('Invalid interval unit: '.$intervalUnit);
        }

        switch ($databaseType) {
            case Connection::DRIVER_MYSQL:
                $select = "DATE_FORMAT([[{$dateColumn}]], '{$sqlDateFormat}') AS [[date]]";
                break;
            case Connection::DRIVER_PGSQL:
                $select = "to_char([[{$dateColumn}]], '{$sqlDateFormat}') AS [[date]]";
                break;
            default:
                throw new Exception('Unsupported connection type: '.$databaseType);
        }

        $sqlGroup[] = '[[date]]';

        // Execute the query
        $results = $query
            ->addSelect([$select])
            ->andWhere([
                'and',
                ['>=', $dateColumn, $startDate->format('Y-m-d H:i:s')],
                ['<', $dateColumn, $endDate->format('Y-m-d H:i:s')]
            ])
            ->groupBy($sqlGroup)
            ->orderBy(['[[date]]' => SORT_ASC])
            ->all();

        // Assemble the data
        $rows = [];

        $endTimestamp = $endDate->getTimestamp();

        while ($cursorDate->getTimestamp() < $endTimestamp) {
            // Do we have a record for this date?
            // $formattedCursorDate = $cursorDate->format($phpDateFormat, $craftTimezone);
            $formattedCursorDate = $cursorDate->format($phpDateFormat);

            if (isset($results[0]) && $results[0]['date'] == $formattedCursorDate) {
                $value = (float)$results[0]['value'];
                array_shift($results);
            } else {
                $value = 0;
            }

            $rows[] = [$formattedCursorDate, $value];
            $cursorDate->modify('+1 '.$intervalUnit);
        }

        return [
            'columns' => [
                [
                    'type' => ($intervalUnit == 'hour' ? 'datetime' : 'date'),
                    'label' => $options['categoryLabel']
                ],
                [
                    'type' => $options['valueType'],
                    'label' => $options['valueLabel']
                ]
            ],
            'rows' => $rows,
        ];
    }

    /**
     * Returns the interval unit that should be used in a run chart, based on the given start and end dates.
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     *
     * @return string The unit that the chart should use ('hour', 'day', 'month', or 'year')
     */
    public static function getRunChartIntervalUnit(DateTime $startDate, DateTime $endDate)
    {
        // Get the total number of days between the two dates
        $days = floor(($endDate->getTimestamp() - $startDate->getTimestamp()) / 86400);

        if ($days >= 730) {
            return 'year';
        }

        if ($days >= 60) {
            return 'month';
        }

        if ($days >= 2) {
            return 'day';
        }

        return 'hour';
    }

    /**
     * Returns the short date, decimal, percent and currency D3 formats based on Craft's locale settings
     *
     * @return array
     */
    public static function getFormats()
    {
        return [
            'shortDateFormats' => self::getShortDateFormats(),
            'decimalFormat' => self::getDecimalFormat(),
            'percentFormat' => self::getPercentFormat(),
            'currencyFormat' => self::getCurrencyFormat(),
        ];
    }

    /**
     * Returns the D3 short date formats based on Yii's short date format
     *
     * @return array
     */
    public static function getShortDateFormats()
    {
        $format = Craft::$app->getLocale()->getDateFormat('short');

        // Some of these are RTL versions
        $removals = [
            'day' => ['y'],
            'month' => ['d', 'd‏'],
            'year' => ['d', 'd‏', 'm', 'M‏'],
        ];

        $shortDateFormats = [];

        foreach ($removals as $unit => $chars) {
            $shortDateFormats[$unit] = $format;

            foreach ($chars as $char) {
                $shortDateFormats[$unit] = preg_replace("/(^[{$char}]+\W+|\W+[{$char}]+)/iu", '', $shortDateFormats[$unit]);
            }
        }


        // yii formats to d3 formats

        $yiiToD3Formats = [
            'day' => ['dd' => '%-d', 'd' => '%-d'],
            'month' => ['MM' => '%-m', 'M' => '%-m'],
            'year' => ['yyyy' => '%Y', 'yy' => '%y', 'y' => '%y']
        ];

        foreach ($shortDateFormats as $unit => $format) {
            foreach ($yiiToD3Formats as $_unit => $_formats) {
                foreach ($_formats as $yiiFormat => $d3Format) {
                    $pattern = "/({$yiiFormat})/i";

                    preg_match($pattern, $shortDateFormats[$unit], $matches);

                    if (count($matches) > 0) {
                        $shortDateFormats[$unit] = preg_replace($pattern, $d3Format, $shortDateFormats[$unit]);

                        break;
                    }
                }
            }
        }

        return $shortDateFormats;
    }

    /**
     * Returns the decimal format for D3
     *
     * @return string
     */
    public static function getDecimalFormat()
    {
        return ',.3f';
    }

    /**
     * Returns the percent format for D3
     *
     * @return string
     */
    public static function getPercentFormat()
    {
        return ',.2%';
    }

    /**
     * Returns the currency format for D3
     *
     * @return string
     */
    public static function getCurrencyFormat()
    {
        return '$,.2f';
    }

    /**
     * Returns the predefined date ranges with their label, start date and end date.
     *
     * @return array
     */
    public static function getDateRanges()
    {
        $dateRanges = [
            'd7' => ['label' => Craft::t('app', 'Last 7 days'), 'startDate' => '-7 days', 'endDate' => null],
            'd30' => ['label' => Craft::t('app', 'Last 30 days'), 'startDate' => '-30 days', 'endDate' => null],
            'lastweek' => ['label' => Craft::t('app', 'Last Week'), 'startDate' => '-2 weeks', 'endDate' => '-1 week'],
            'lastmonth' => ['label' => Craft::t('app', 'Last Month'), 'startDate' => '-2 months', 'endDate' => '-1 month'],
        ];

        return $dateRanges;
    }
}
