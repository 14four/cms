<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craftunit\validators;


use Codeception\Test\Unit;
use craft\validators\HandleValidator;
use craftunit\support\mockclasses\models\ExampleModel;

/**
 * Class HandleValidatorTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class HandleValidatorTest extends Unit
{
    /**
     * @var HandleValidator
     */
    protected $handleValidator;

    /**
     * @var ExampleModel
     */
    protected $model;
    /*
     * @var \UnitTester
     */
    protected $tester;

    protected $reservedWords =  ['bird', 'is', 'the', 'word'];
    public function _before()
    {
        $this->model = new ExampleModel();
        $this->handleValidator = new HandleValidator(['reservedWords' => $this->reservedWords]);

        $this->assertSame($this->reservedWords, $this->handleValidator->reservedWords);
        $this->reservedWords  = array_merge($this->reservedWords, HandleValidator::$baseReservedWords);
    }

    public function testStaticConstants()
    {
        $this->assertSame('[a-zA-Z][a-zA-Z0-9_]*', HandleValidator::$handlePattern);
        $this->assertSame(
            [
                'attribute', 'attributeLabels','attributeNames', 'attributes', 'classHandle', 'content',
                'dateCreated', 'dateUpdated', 'false', 'fields', 'handle', 'id', 'n', 'name', 'no',
                'rawContent', 'rules', 'searchKeywords', 'section', 'this',
                'true', 'type', 'uid', 'value', 'y','yes',
            ],
            HandleValidator::$baseReservedWords
        );
    }

    public function testStaticConstantsArentAllowed()
    {

        foreach ($this->reservedWords as $reservedWord) {
            $this->model->exampleParam = $reservedWord;
            $this->handleValidator->validateAttribute($this->model, 'exampleParam');

            $this->assertArrayHasKey('exampleParam', $this->model->getErrors(), $reservedWord);

            $this->model->clearErrors();
            $this->model->exampleParam = null;
        }
    }

    /**
     * @dataProvider handleValidationData
     *
     * @param bool $result
     * @param      $input
     */
    public function testHandleValidation(bool $mustValidate, $input)
    {
        $this->model->exampleParam = $input;

        $validatorResult = $this->handleValidator->validateAttribute($this->model, 'exampleParam');

        $this->assertSame(null, $validatorResult);

        if ($mustValidate) {
            $this->assertArrayNotHasKey('exampleParam', $this->model->getErrors());
        } else {
            $this->assertArrayHasKey('exampleParam', $this->model->getErrors());
        }

        $this->model->clearErrors();
        $this->model->exampleParam = null;
    }

    public function handleValidationData()
    {
        return [
            [true, 'iamAHandle'],
            [true, 'ASDFGHJKLQWERTYUIOPZXCVBNM'],
            [false, '!@#$%^&*()'],
            [false, '🔥'],
            [false, '123'],
        ];
    }
}