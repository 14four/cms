<?php
namespace Blocks;

/**
 *
 */
class SessionRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'sessions';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'token' => array(AttributeType::String, 'maxLength' => 100, 'column' => ColumnType::Char, 'required' => true),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'user' => array(static::BELONGS_TO, 'UserRecord', 'onDelete' => static::CASCADE, 'required' => true),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('uid')),
			array('columns' => array('token')),
			array('columns' => array('dateUpdated')),
		);
	}
}
