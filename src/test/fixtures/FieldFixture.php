<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craft\test\fixtures;


use craft\fields\PlainText;
use craft\records\Field;
use craft\services\Fields;
use craft\test\Craft;
use craft\test\Fixture;
use yii\base\InvalidArgumentException;

/**
 * Base fixture for setting up fields
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class FieldFixture extends Fixture
{
    public $modelClass = Field::class;

    public function load()
    {
        foreach ($this->getData() as $alias => $row) {
            if (isset($row['fieldType'])) {
                $field = new $row['fieldType'];
                unset($field['fieldType']);
            } else {
                $field = new PlainText();
            }

            foreach ($row as $key => $value) {
                $field->$key = $value;
            }

            if (!\Craft::$app->getFields()->saveField($field)) {
                throw new InvalidArgumentException('Unable to save field');
            }
        }

        \Craft::$app->set('fields', new Fields());
    }

    public function unload()
    {
        $fieldsThatDidntSave = [];
        foreach ($this->getData() as $toBeDeletedRow) {
            $field = \Craft::$app->getFields()->getFieldByHandle($toBeDeletedRow['handle']);

            if ($field instanceof \craft\base\Field) {
                if (!\Craft::$app->getFields()->deleteField($field)) {
                    $fieldsThatDidntSave[$field->handle] = $field->name;
                }
            }
        }
        if ($fieldsThatDidntSave !== []) {
            throw new InvalidArgumentException(implode(', ', $fieldsThatDidntSave));
        }
    }
}