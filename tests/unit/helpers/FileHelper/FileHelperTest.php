<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */
namespace craftunit\helpers\filehelper;

use Codeception\Test\Unit;
use craft\helpers\FileHelper;
use yii\base\ErrorException;
use yii\base\InvalidArgumentException;

/**
 * Class FileHelperTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class FileHelperTest extends Unit
{
    public function _before()
    {
        FileHelper::clearDirectory(__DIR__ . '/sandbox/copyInto');
    }

    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testCreateRemove()
    {
        $location = dirname(__DIR__, 4) . '/at-root';
        FileHelper::createDirectory('at-root');
        $this->assertDirectoryExists($location);

        FileHelper::removeDirectory($location);
        $this->assertDirectoryNotExists($location);

        $this->assertNull(FileHelper::removeDirectory('notadir'));
    }

    public function testCopyAndClear()
    {
        $copyIntoDir = __DIR__ . '/sandbox/copyInto';
        $copyFromDir = dirname(__DIR__, 3) . '/_data/assets/files';

        // Clear it.
        FileHelper::clearDirectory($copyIntoDir);

        // Make sure its clear
        $this->assertTrue(FileHelper::isDirectoryEmpty($copyIntoDir));

        // Test that clearing an empty dir wont make things go wrong.
        FileHelper::clearDirectory($copyIntoDir);

        // Copy into the directory
        FileHelper::copyDirectory($copyFromDir, $copyIntoDir);

        // Make sure something exists
        $this->assertSame(scandir($copyFromDir, 1), scandir($copyIntoDir, 1));
        $this->assertFalse(FileHelper::isDirectoryEmpty($copyIntoDir));

        // Clear it out.
        FileHelper::clearDirectory($copyIntoDir);

        // Ensure everything is empty.
        $this->assertTrue(FileHelper::isDirectoryEmpty($copyIntoDir));
    }

    /**
     * @dataProvider pathNormalizedData
     *
     * @param $result
     * @param $path
     * @param $dirSeperator
     */
    public function testPathNormalization($result, $path, $dirSeperator)
    {
        $normalized = FileHelper::normalizePath($path, $dirSeperator);
        $this->assertSame($result, $normalized);
    }

    public function pathNormalizedData()
    {
        return [
            ['Im a string', 'Im a string', DIRECTORY_SEPARATOR],
            [
                'c:' . DIRECTORY_SEPARATOR . 'vagrant' . DIRECTORY_SEPARATOR . 'box',
                'c:/vagrant/box',
                DIRECTORY_SEPARATOR
            ],
            ['c:\\vagrant\\box', 'c:/vagrant/box', '\\'],
            ['c:|vagrant|box', 'c:\\vagrant\\box', '|'],
            [' +HostName[@SSL][@Port]+SharedFolder+Resource', ' \\HostName[@SSL][@Port]\SharedFolder\Resource', '+'],
            ['|?|C:|my_dir', '\\?\C:\my_dir', '|'],
            ['==stuff', '\\\\stuff', '='],
        ];
    }

    /**
     * @dataProvider dirCreationData
     *
     * @param $result
     * @param $path
     * @param $mode
     * @param $recursive
     */
    public function testDirCreation($result, $path, $mode, $recursive)
    {

    }

    public function dirCreationData()
    {
        return [

        ];
    }

    /**
     * @dataProvider isDirEmptyData
     *
     * @param $result
     * @param $input
     */
    public function testIsDirEmpty($result, $input)
    {
        $isEmpty = FileHelper::isDirectoryEmpty($input);
        $this->assertSame($result, $isEmpty);
    }

    public function isDirEmptyData()
    {
        return [
            [true, __DIR__ . '/sandbox/isdirempty/yes'],
            [false, __DIR__ . '/sandbox/isdirempty/no'],
            [false, __DIR__ . '/sandbox/isdirempty/dotfile'],
        ];
    }

    public function testIsDirEmptyExceptions()
    {
        $this->tester->expectException(InvalidArgumentException::class, function () {
            FileHelper::isDirectoryEmpty('aaaaa//notadir');
        });
        $this->tester->expectException(InvalidArgumentException::class, function () {
            FileHelper::isDirectoryEmpty(__DIR__ . '/sandbox/isdirempty/dotfile/no/test');
        });
        $this->tester->expectException(InvalidArgumentException::class, function () {
            FileHelper::isDirectoryEmpty('ftp://google.com');
        });
    }

    /**
     * @dataProvider isWritableDataProvider
     *
     * @param $result
     * @param $input
     *
     * @throws ErrorException
     */
    public function testIsWritable($result, $input)
    {
        $isWritable = FileHelper::isWritable($input);
        $this->assertTrue($result, $isWritable);
    }

    public function isWritableDataProvider()
    {
        return [
            [true, __DIR__ . '/sandbox/iswritable/dir'],
            [true, __DIR__ . '/sandbox/iswritable/dirwithfile'],
            [true, __DIR__ . '/sandbox/iswritable/dirwithfile/test.text'],
            [true, 'i/dont/exist/as/a/dir/'],
        ];
    }


    /**
     * @dataProvider mimeTypeData
     *
     * @param $result
     * @param $file
     * @param $magicFile
     * @param $checkExtension
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function testGetMimeType($result, $file, $magicFile, $checkExtension)
    {
        $mimeType = FileHelper::getMimeType($file, $magicFile, $checkExtension);
        $this->assertSame($result, $mimeType);
    }

    public function mimeTypeData()
    {
        return [
            ['application/pdf', dirname(__DIR__, 3) . '/_data/assets/files/pdf-sample.pdf', null, true],
            ['text/plain', dirname(__DIR__, 3) . '/_data/assets/files/empty-file.text', null, true],
            ['text/html', dirname(__DIR__, 3) . '/_data/assets/files/test.html', null, true],
            ['image/gif', dirname(__DIR__, 3) . '/_data/assets/files/example-gif.gif', null, true],
            ['application/pdf', dirname(__DIR__, 3) . '/_data/assets/files/pdf-sample.pdf', null, true],
            ['image/svg+xml', dirname(__DIR__, 3) . '/_data/assets/files/gng.svg', null, true],
            ['application/xml', dirname(__DIR__, 3) . '/_data/assets/files/random.xml', null, true],
            ['directory', __DIR__, null, true],
        ];
    }

    /**
     * @dataProvider mimeTypeFalseData
     *
     * @param $result
     * @param $file
     *
     * @throws \yii\base\InvalidConfigException\
     */
    public function testGetMimeTypeOnFalse($result, $file)
    {
        $mimeType = FileHelper::getMimeType($file, null, false);
        $this->assertSame($result, $mimeType);
    }

    public function mimeTypeFalseData()
    {
        return [
            ['text/plain', dirname(__DIR__, 3) . '/_data/assets/files/test.html'],

        ];
    }

    public function testGetMimeTypeExceptions()
    {
        $this->tester->expectException(ErrorException::class, function () {
            FileHelper::getMimeType('notafile');
        });
    }

    /**
     * @dataProvider sanitizedFilenameData
     *
     * @param $result
     * @param $input
     * @param $options
     */
    public function testFilenameSanitazion($result, $input, $options)
    {
        $sanitized = FileHelper::sanitizeFilename($input, $options);
        $this->assertSame($result, $sanitized);
    }

    public function sanitizedFilenameData()
    {
        return [
            ['notafile', 'notafile', []],
            ['not-a-file', 'not a file', []],
            ['im-a-file@.svg', 'im-a-file!@#$%^&*(.svg', []],
            ['i(c)m-a-file.svg', 'i£©m-a-file⚽🐧🎺.svg', ['asciiOnly' => true]],
            ['not||a||file', 'not a file', ['separator' => '||']],

            // Set the seperator to an non-ascii char will get added and then stripped.
            ['notafile', 'not a file', ['separator' => '🐧', 'asciiOnly' => true]],
            ['notafile', 'not a file', ['separator' => '🐧', 'asciiOnly' => true]],
        ];
    }

    /**
     * @dataProvider mimeTypeData
     *
     * @param $result
     * @param $input
     * @param $magicFile
     * @param $checkExtension
     */
    public function testIsSvg($result, $input, $magicFile, $checkExtension)
    {
        $result = false;
        if (strpos($input, '.svg') !== false) {
            $result = true;
        }

        $isSvg = FileHelper::isSvg($input, $magicFile, $checkExtension);
        $this->assertSame($result, $isSvg);
    }

    /**
     * @dataProvider mimeTypeData
     *
     * @param $result
     * @param $input
     * @param $magicFile
     * @param $checkExtension
     */
    public function testIsGif($result, $input, $magicFile, $checkExtension)
    {
        $result = false;
        if (strpos($input, '.gif') !== false) {
            $result = true;
        }

        $isSvg = FileHelper::isGif($input, $magicFile, $checkExtension);
        $this->assertSame($result, $isSvg);
    }

    /**
     * @dataProvider writeToFileData
     *
     * @param $results
     * @param $file
     * @param $contents
     * @param $options
     */
    public function testWriteToFile($content, $file, $contents, $options, $removeDir = false, $removeableDir = '')
    {
        $writeToFile = FileHelper::writeToFile($file, $contents, $options);

        $this->assertTrue(is_file($file));
        $this->assertSame($content, file_get_contents($file));

        if ($removeDir) {
            FileHelper::removeDirectory($removeableDir);
        } else {
            FileHelper::unlink($file);
        }

    }

    public function writeToFileData()
    {
        $sandboxDir = __DIR__.'/sandbox/writeto';
        return [
            ['content', $sandboxDir.'/notafile', 'content', []],
            ['content', $sandboxDir.'/notadir/notafile', 'content', [], true, $sandboxDir.'/notadir'],
        ];
    }
    public function testWriteToFileAppend()
    {
        $sandboxDir = __DIR__.'/sandbox/writeto';
        $file = $sandboxDir.'/test-file';
        
        FileHelper::writeToFile($file, 'contents');
        $this->assertSame('contents', file_get_contents($file));

        FileHelper::writeToFile($file, 'changed');
        $this->assertSame('changed', file_get_contents($file));

        FileHelper::writeToFile($file, 'andappended', ['append' => true]);
        $this->assertSame('changedandappended', file_get_contents($file));

        FileHelper::unlink($file);
    }
    public function testWriteToFileExceptions()
    {
        $this->tester->expectException(InvalidArgumentException::class, function () {
            FileHelper::writeToFile('notafile/folder', 'somecontent', ['createDirs' => false]);
        });
    }
}