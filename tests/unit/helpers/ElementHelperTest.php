<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */


namespace craftunit\helpers;


use Codeception\Test\Unit;
use craft\errors\OperationAbortedException;
use craft\helpers\ElementHelper;
use craft\test\mockclasses\elements\ExampleElement;

/**
 * Class ElementHelperTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class ElementHelperTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @dataProvider createSlugData
     *
     * @param $result
     * @param $input
     */
    public function testCreateSlug($result, $input)
    {
        $this->assertSame($result, ElementHelper::createSlug($input));
    }

    public function createSlugData()
    {
        $glue = \Craft::$app->getConfig()->getGeneral()->slugWordSeparator;

        return [
            ['word'.$glue.'Word', 'wordWord'],
            ['word'.$glue.'word', 'word word'],
            ['word', 'word'],
            ['123456789', '123456789'],
            ['abc...dfg', 'abc...dfg'],
            ['abc...dfg', 'abc...(dfg)'],
        ];
    }

    public function testLowerRemoveFromCreateSlug()
    {
        $general =  \Craft::$app->getConfig()->getGeneral();
        $general->allowUppercaseInSlug = false;

        $this->assertSame('word'.$general->slugWordSeparator.'word', ElementHelper::createSlug('word WORD'));
    }

    /**
     * @dataProvider doesuriHaveSlugTagData
     * @param $result
     * @param $input
     */
    public function testDoesUriFormatHaveSlugTag($result, $input)
    {
        $doesIt = ElementHelper::doesUriFormatHaveSlugTag($input);
        $this->assertSame($result, $doesIt);
        $this->assertInternalType('boolean', $doesIt);
    }
    public function doesuriHaveSlugTagData()
    {

        return [
            [true, 'entry/slug'],
            [true, 'entry/{slug}'],
            [false, 'entry/{notASlug}'],
            [false, 'entry/{SLUG}'],
            [false, 'entry/data'],
        ];
    }

    /**
     * @dataProvider setUniqueUriData
     * @param $result
     * @param $config
     */
    public function testSetUniqueUri($result, $config)
    {
        $example = new ExampleElement($config);
        $uri = ElementHelper::setUniqueUri($example);

        $this->assertNull($uri);
        foreach ($result as $key => $res) {
            $this->assertSame($res, $example->$key);
        }
    }
    public function setUniqueUriData()
    {
        return [
            [['uri' => null], ['uriFormat' => null]],
            [['uri' => ''], ['uriFormat' => '']],
            [['uri' => 'craft'], ['uriFormat' => '{slug}', 'slug' => 'craft']],
            [['uri' => 'test'], ['uriFormat' => 'test/{slug}']],
            [['uri' => 'test/test'], ['uriFormat' => 'test/{slug}', 'slug' => 'test']],
            [['uri' => 'test/tes.!@#$%^&*()_t'], ['uriFormat' => 'test/{slug}', 'slug' => 'tes.!@#$%^&*()_t']],

            // 254 chars.
            [['uri' => 'test/asdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadsssssssssssssssssssssssssssssssssssssssssssss'], ['uriFormat' => 'test/{slug}', 'slug' => 'asdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadsssssssssssssssssssssssssssssssssssssssssssss']],


            // TODO: Test _isUniqueUri and setup fixtures that add data to elements_sites
        ];
    }
    public function testMaxSlugIncrementExceptions()
    {
        \Craft::$app->getConfig()->getGeneral()->maxSlugIncrement = 0;
        $this->tester->expectException(OperationAbortedException::class, function () {
            $el = new ExampleElement(['uriFormat' => 'test/{slug}']);
            ElementHelper::setUniqueUri($el);
        });
    }
    public function testMaxLength()
    {
        // 256 length slug. Oh no we dont.
        $this->tester->expectException(OperationAbortedException::class, function () {
            $el = new ExampleElement([
                'uriFormat' => 'test/{slug}',
                'slug' => 'asdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadsssssssssssssssssssssssssssssssssssssssss22ssss'
            ]);
            ElementHelper::setUniqueUri($el);
        });
    }



}