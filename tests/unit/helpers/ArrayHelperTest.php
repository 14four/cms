<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit\helpers;

use \craft\helpers\ArrayHelper;

/**
 * Unit tests for the Array Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class ArrayHelperTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @dataProvider toArrayData
     */
    public function testToArray($result, $input)
    {
        $toArray = ArrayHelper::toArray($input);
        $this->assertSame($result, $toArray);
    }

    /**
     * TODO: Example with a \stdClass?
     * @return array
     */
    public function toArrayData()
    {
        return[
            [[], null], [[1,2,3], [1,2,3]]
        ];
    }

    /**
     * @dataProvider prependOrAppendData
     */
    public function testPrependOrAppend($result, $inputArray, $appendable, $preOrAppend)
    {
        ArrayHelper::prependOrAppend($inputArray, $appendable, $preOrAppend);
        $this->assertSame($result, $inputArray);
    }

    public function prependOrAppendData()
    {
        return [
            [[1, 2, 3, 4],  [1, 2, 3], 4, false],
            [[4, 1, 2, 3],  [1, 2, 3], 4, true],
            [[1, 2, 3, ['22']],  [1, 2, 3], ['22'], false],
            [[1, 2, 3, null],  [1, 2, 3], null, false],
        ];
    }

    public function testFilterEmptyStringsFromArray()
    {
        $this->assertSame([0 => 1, 1 => 2, 4 => null, 5 => 5], ArrayHelper::filterEmptyStringsFromArray([0 => 1, 1 => 2, 3 => '', 4 => null, 5=> 5]));
    }

    public function testFirstKey()
    {
        $this->assertNull(ArrayHelper::firstKey([]));
        $this->assertEquals(0, ArrayHelper::firstKey([1]));
        $this->assertEquals(5, ArrayHelper::firstKey([5 => 'value']));
        $this->assertEquals('firstKey', ArrayHelper::firstKey(['firstKey' => 'firstValue', 'secondKey' => 'secondValue']));
    }

    /**
     * @dataProvider renameDataProvider
     *
     * @param      $result
     * @param      $inputArray
     * @param      $oldKey
     * @param      $newKey
     * @param null $default
     */
    public function testArrayRename($result, $inputArray, $oldKey, $newKey, $default = null)
    {
        ArrayHelper::rename($inputArray, $oldKey, $newKey, $default);
        $this->assertSame($result, $inputArray);
    }

    public function renameDataProvider()
    {
        return [
            [['fizz' => 'plop', 'foo2' => 'bar'], ['foo' => 'bar', 'fizz' => 'plop'], 'foo', 'foo2'],
            [['foo' => 'bar', 'fizz' => 'plop', 'fooY' => null], ['foo' => 'bar', 'fizz' => 'plop'], 'fooX', 'fooY'],
            [['foo' => 'bar', 'fizz' => 'plop'], ['foo' => 'bar', 'fizz' => 'plop'], 'fooX', 'foo'],
            [['foo' => 'bar', 'fizz' => 'plop', 'fooY' => 'test'], ['foo' => 'bar', 'fizz' => 'plop'], 'fooX', 'fooY', 'test'],
        ];
    }



    public function testFilterbyValue()
    {
        $array = [
            [
                'name' => 'array 1',
                'description' => 'the first array',
            ],
            [
                'name' => 'array 2',
                'description' => 'the second array'
            ]
        ];

        $filtered = ArrayHelper::filterByValue($array, 'name', 'array 1');
        $this->assertCount(1, $filtered);
        $this->assertSame( 'the first array', $filtered[0]['description']);

        // Set the name to empty and see if we can filter by keys with an empty value
        $array[0]['name'] = '';
        $filtered = ArrayHelper::filterByValue($array, 'name', '');
        $this->assertCount(1, $filtered);
        $this->assertSame('the first array', $filtered[0]['description']);

        // Add a new key to the array that it empty and with an empty value. Make sure that when filtering empty by empty  it returns everything.
        $array[0][''] = '';
        $filtered = ArrayHelper::filterByValue($array, '', '');
        $this->assertCount(count($array), $filtered);
        $this->assertSame($array, $filtered);

        // Filter by emojis?
        $array[0]['😀'] = '😘';
        $filtered = ArrayHelper::filterByValue($array, '😀', '😘');
        $this->assertCount(1, $filtered);
        $this->assertSame('the first array', $filtered[0]['description']);

        // See if we can filter by an array as a value.
        $this->assertSame([['name' => ['testname' => true]]],
            ArrayHelper::filterByValue(
            [
                ['name' => ['testname' => true]],
                ['name' => '22'],
            ],
            'name',
            ['testname' => true]
        ));

        // Strict will only return 1. Non strict will typecast integer to string and thus find 2.
        $this->assertCount(2,
            ArrayHelper::filterByValue(
                [
                    ['name' => 22],
                    ['name' => '22'],
                ],
                'name',
                22,
                false
            )
        );
        $this->assertCount(1,
            ArrayHelper::filterByValue(
                [
                    ['name' => 22],
                    ['name' => '22'],
                ],
                'name',
                22,
                true
            )
        );

        $this->assertSame(
            [['name' => 'john']],
            ArrayHelper::filterByValue(
            [
                ['name' => 'john'],
                ['name' => 'michael'],
            ],
            ['name'],
            'john',
            true
        ));

        $this->assertSame(
            [['name' => 'john']],
            ArrayHelper::filterByValue(
                [
                    ['name' => 'john'],
                    ['name' => 'michael'],
                ],
                function ($array, $default){
                    return $array['name'];
                },
                'john',
                true
            ));
        // Make sure that filter by value hasnt made any changes to the array content e.t.c.
        $mockedUp = [
            [
                'name' => '',
                'description' => 'the first array',
                '' => '',
                '😀' =>'😘'

            ],
            [
                'name' => 'array 2',
                'description' => 'the second array'
            ]
        ];

        $this->assertSame($array, $mockedUp);
    }
}
