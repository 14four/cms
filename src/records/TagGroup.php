<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\records;

use yii\db\ActiveQueryInterface;
use craft\db\ActiveRecord;

/**
 * Class TagGroup record.
 *
 * @property integer     $id            ID
 * @property integer     $fieldLayoutId Field layout ID
 * @property string      $name          Name
 * @property string      $handle        Handle
 * @property FieldLayout $fieldLayout   Field layout
 * @property Tag[]       $tags          Tags
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class TagGroup extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%taggroups}}';
    }

    /**
     * Returns the tag group’s fieldLayout.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFieldLayout()
    {
        return $this->hasOne(FieldLayout::class,
            ['id' => 'fieldLayoutId']);
    }

    /**
     * Returns the tag group’s tags.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getTags()
    {
        return $this->hasMany(Tag::class, ['groupId' => 'id']);
    }
}
