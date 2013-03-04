<?php
namespace Craft;

/**
 * Username model
 */
class UsernameModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'username' => array(AttributeType::String, 'required' => true),
		);
	}
}
