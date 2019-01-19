<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craftunit\web;


use Codeception\Stub;
use craft\test\TestCase;
use craft\web\ErrorHandler;
use yii\web\HttpException;

/**
 * Unit tests for ErrorHandlerTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class ErrorHandlerTest extends TestCase
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var ErrorHandler $errorHandler
     */
    protected $errorHandler;

    public function _before()
    {
        parent::_before();

        $this->errorHandler = \Craft::createObject(ErrorHandler::class);
    }

    /**
     * Test that Twig runtime errors use the previous error (if it exists).
     * @throws \Exception
     */
    public function testHandleTwigException()
    {
        // Disable clear output as this throws: Test code or tested code did not (only) close its own output buffers
        $this->errorHandler = Stub::construct(ErrorHandler::class, [], [
            'logException' => $this->assertObjectIsInstanceOfClassCallback(\Exception::class),
            'clearOutput' => null,
            'renderException' => $this->assertObjectIsInstanceOfClassCallback(\Exception::class)
        ]);

        $exception = new \Twig_Error_Runtime('A twig error occured');
        $this->setInaccessibleProperty($exception, 'previous', new \Exception('Im not a twig error'));
        $this->errorHandler->handleException($exception);
    }

    public function testHandle404Exception()
    {
        // Disable clear output as this throws: Test code or tested code did not (only) close its own output buffers
        $this->errorHandler = Stub::construct(ErrorHandler::class, [], [
            'logException' => $this->assertObjectIsInstanceOfClassCallback(HttpException::class),
            'clearOutput' => null,
            'renderException' => $this->assertObjectIsInstanceOfClassCallback(HttpException::class)
        ]);

        // Oops. Page not found
        $exception = new HttpException('Im an error');
        $exception->statusCode = 404;

        // Test 404's are treated with a different file
        $this->errorHandler->handleException($exception);
        $this->assertSame(\Craft::getAlias('@crafttestsfolder/storage/logs/web-404s.log'), \Craft::$app->getLog()->targets[0]->logFile);
    }


    /**
     * @param \Throwable $exception
     * @param $message
     * @dataProvider exceptionTypeAndNameData
     */
    public function testGetExceptionName(\Throwable $exception, $message)
    {
        $this->assertSame($message, $this->errorHandler->getExceptionName($exception));
    }
    public function exceptionTypeAndNameData()
    {
        return [
            [new \Twig_Error_Syntax('Twig go boom'), 'Twig Syntax Error'],
            [new \Twig_Error_Loader('Twig go boom'), 'Twig Template Loading Error'],
            [new \Twig_Error_Runtime('Twig go boom'), 'Twig Runtime Error'],
        ];
    }

    /**
     * @param $result
     * @param $class
     * @param $method
     * @dataProvider getTypeUrlData
     */
    public function testGetTypeUrl($result, $class, $method)
    {
        $this->assertSame($result, $this->invokeMethod($this->errorHandler, 'getTypeUrl', [$class, $method]));
    }
    public function getTypeUrlData() : array
    {
        return [
            ['http://twig.sensiolabs.org/api/2.x/Twig_Template.html#method_render', '__TwigTemplate_', 'render'],
            ['http://twig.sensiolabs.org/api/2.x/Twig_.html#method_render', 'Twig_', 'render'],
            ['http://twig.sensiolabs.org/api/2.x/Twig_.html', 'Twig_', null],
        ];
    }

    /**
     * @throws \yii\base\ErrorException
     */
    public function testHandleError()
    {
        if (PHP_VERSION_ID >= 70100) {
            $this->assertNull($this->errorHandler->handleError(null, 'Narrowing occurred during type inference. Please file a bug report', null, null));
        } else {
            $this->markTestSkipped('Running on PHP 70100. parent::handleError() should be called in the craft ErrorHandler.');
        }
    }
}
