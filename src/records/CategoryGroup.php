<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;
use craft\app\enums\AttributeType;

/**
 * Class CategoryGroup record.
 *
 * @var integer $id ID
 * @var integer $structureId Structure ID
 * @var integer $fieldLayoutId Field layout ID
 * @var string $name Name
 * @var string $handle Handle
 * @var boolean $hasUrls Has URLs
 * @var string $template Template
 * @var ActiveQueryInterface $structure Structure
 * @var ActiveQueryInterface $fieldLayout Field layout
 * @var ActiveQueryInterface $locales Locales
 * @var ActiveQueryInterface $categories Categories

 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CategoryGroup extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['handle'], 'craft\\app\\validators\\Handle', 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
			[['name', 'handle'], 'unique'],
			[['name', 'handle'], 'required'],
			[['name', 'handle'], 'string', 'max' => 255],
			[['template'], 'string', 'max' => 500],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%categorygroups}}';
	}

	/**
	 * Returns the category group’s structure.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getStructure()
	{
		return $this->hasOne(Structure::className(), ['id' => 'structureId']);
	}

	/**
	 * Returns the category group’s fieldLayout.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getFieldLayout()
	{
		return $this->hasOne(FieldLayout::className(), ['id' => 'fieldLayoutId']);
	}

	/**
	 * Returns the category group’s locales.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getLocales()
	{
		return $this->hasMany(CategoryGroupLocale::className(), ['groupId' => 'id']);
	}

	/**
	 * Returns the category group’s categories.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getCategories()
	{
		return $this->hasMany(Category::className(), ['groupId' => 'id']);
	}
}
