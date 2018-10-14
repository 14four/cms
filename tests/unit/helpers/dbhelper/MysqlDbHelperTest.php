<?php

namespace craftunit\helpers;


use Codeception\Test\Unit;
use craft\db\mysql\Schema;
use craft\helpers\Db;

/**
 * Unit tests for the DB Helper class where its output may need to be mysql specific. Will be skipped if db isnt mysql.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class MysqlDbHelperTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        if (!\Craft::$app->getDb()->getIsMysql()) {
            $this->markTestSkipped();
        }
    }

    /**
     * @dataProvider sqlTypesData
     */
    public function testTypeSupport($type, $supported)
    {
        $isSupported = Db::isTypeSupported($type);
        $this->assertSame($supported, Db::isTypeSupported($type));
        $this->assertInternalType('boolean', $isSupported);
    }

    public function sqlTypesData()
    {
        // TODO: This is the best way to test it but is it worth 20mb and 3 seconds of time?
        $mysqlSchema = new \craft\db\mysql\Schema();
        $pgsqlSchema = new \craft\db\pgsql\Schema();
        $returnArray = [];

        foreach ($mysqlSchema->typeMap as $key => $value) {
            $returnArray[] = [$key, true];
        }
        foreach ($pgsqlSchema->typeMap as $key => $value) {
            if (!isset($mysqlSchema->typeMap[$key])) {
                $returnArray[] = [$key, false];
            }
        }

        return  $returnArray;
    }

    /**
     * @dataProvider textualStorageData
     * @param $result
     * @param $input\
     */
    public function testGetTextualColumnStorageCapacity($result, $input)
    {
        $capacity = Db::getTextualColumnStorageCapacity($input);
        $this->assertSame($result, $capacity);
    }
    public function textualStorageData()
    {
        return [
            [null, Schema::TYPE_ENUM],
            [false, 'enum()']
        ];
    }

    /**
     * @dataProvider parseParamData
     */
    public function testParseParamGeneral($result, array $inputArray)
    {
        $collumn = isset($inputArray[0]) ? $inputArray[0] : null;
        $value =  isset($inputArray[1]) ? $inputArray[1] : null;
        $defaultOperator =  isset($inputArray[2]) ? $inputArray[2] : '=';
        $caseInsensitive =  isset($inputArray[3]) ? $inputArray[3] : null;

        $this->assertSame($result, Db::parseParam($collumn, $value, $defaultOperator, $caseInsensitive));
    }

    public function parseParamData()
    {
        return [
            'multi-:empty:-param' => [
                [ 'or', [ 'not', ['or',['content_table' => null, ], ['content_table' => '',]]], ['!=', 'content_table', 'field_2']],
                ['content_table', ':empty:, field_2', '!=']
            ],
        ];
    }
}