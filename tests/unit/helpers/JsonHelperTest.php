<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit\helpers;


use craft\helpers\Json;

/**
 * Unit tests for the Json Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class JsonHelperTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    /**
     * @dataProvider jsonDecodabledData
     */
    public function testDecodeIfJson($input, $output)
    {
        $this->assertSame($output, Json::decodeIfJson($input));
    }

    public function jsonDecodabledData()
    {
        $basicArray = [
            'WHAT DO WE WANT' => 'JSON',
            'WHEN DO WE WANT IT' => 'NOW',
        ];
        return [
            ['{"test":"test"', '{"test":"test"'],
            [ json_encode($basicArray), $basicArray],
            ['', null]
        ];
    }
}