<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\fields;

use Craft;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\helpers\Db;
use yii\db\Schema;

/**
 * PlainText represents a Plain Text field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class PlainText extends Field implements PreviewableFieldInterface
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return Craft::t('app', 'Plain Text');
    }

    // Properties
    // =========================================================================

    /**
     * @var string The input’s placeholder text
     */
    public $placeholder;

    /**
     * @var bool Whether the input should allow line breaks
     */
    public $multiline;

    /**
     * @var int The minimum number of rows the input should have, if multi-line
     */
    public $initialRows = 4;

    /**
     * @var int The maximum number of characters allowed in the field
     */
    public $maxLength;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['initialRows', 'maxLength'], 'integer', 'min' => 1];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/PlainText/settings',
            [
                'field' => $this
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType()
    {
        if (!$this->maxLength) {
            return Schema::TYPE_TEXT;
        }

        return Db::getTextualColumnTypeByContentLength($this->maxLength);
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, $element)
    {
        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/PlainText/input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
            ]);
    }
}
