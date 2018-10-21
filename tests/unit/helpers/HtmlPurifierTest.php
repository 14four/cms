<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */


namespace craftunit\helpers;


use Codeception\Test\Unit;
use craft\helpers\HtmlPurifier;

/**
 * Class HtmlPurifierTest.
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class HtmlPurifierTest extends Unit
{
    /**
     * @dataProvider utf8CleanData
     * @param $result
     * @param $input
     */
    public function testCleanUtf8($result, $input)
    {
        $cleaned = HtmlPurifier::cleanUtf8($input);
        $this->assertSame($result, $cleaned);
    }
    public function utf8CleanData()
    {
        // https://github.com/ezyang/htmlpurifier/blob/master/tests/HTMLPurifier/EncoderTest.php#L21
        return [
            ['test', 'test'],
            ['null byte: ', "null byte: \0"],
            ["あ（い）う（え）お", "あ（い）う（え）お\0"],
            ['', "\1\2\3\4\5\6\7"],
            ['', "\x7F"],
            ['', "\xC2\x80"],
            ['', "\xDF\xFF"],
            ["\xF3\xBF\xBF\xBF", "\xF3\xBF\xBF\xBF"],
            ['', "\xED\xB0\x80"],
            ['😀😘', '😀😘'],
        ];
    }

    /**
     * TODO: Do we really want to test this as it depends on the $config option which makes it quite hard to change without breaking behaviour and slightly
     * useless to test.....
     * @dataProvider convertUtf8Data
     *
     * @param $result
     * @param $input
     */
    public function testConvertToUtf8($result, $input, $config)
    {
        $converted = HtmlPurifier::convertToUtf8($input, $config);
        $this->assertSame($result, $converted);
    }
    public function convertUtf8Data()
    {
        $config = \HTMLPurifier_Config::createDefault();

        return [
            ["\xF3\xBF\xBF\xBF", "\xF3\xBF\xBF\xBF", $config],
        ];
    }

    public function testConfigure()
    {
        $purifier_config = \HTMLPurifier_Config::createDefault();
        $purifier_config->set('HTML.AllowedElements', 'p, a, ul, ol, li, h1, h2, h3, h4, h5, h6, br, strong, em, b, i');
        $purifier_config->set('HTML.AllowedAttributes', '');
        $purifier_config->set('CSS.AllowedProperties', '');
        $purifier_config->set('AutoFormat.RemoveEmpty', true);
        $purifier_config->set('AutoFormat.AutoParagraph', true);
// May cause problems with empty table cells and headers
        $purifier_config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);


    }

}