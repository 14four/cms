<?php
namespace Craft;

/**
 * Stores the info for a plugin release.
 */
class PluginNewReleaseModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		$attributes['version']  = AttributeType::String;
		$attributes['date']     = AttributeType::DateTime;
		$attributes['notes']    = AttributeType::String;
		$attributes['critical'] = AttributeType::Bool;

		return $attributes;
	}
}
