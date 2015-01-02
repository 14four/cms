<?php
namespace craft\app\variables;

use craft\app\Craft;
use craft\app\helpers\IOHelper;
use craft\app\helpers\UrlHelper;

craft()->requireEdition(Craft::Client);

/**
 * Class LogoVariable
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.variables
 * @since     3.0
 */
class LogoVariable extends ImageVariable
{
	// Public Methods
	// =========================================================================

	/**
	 * Return the URL to the logo.
	 *
	 * @return string|null
	 */
	public function getUrl()
	{
		return UrlHelper::getResourceUrl('logo/'.IOHelper::getFileName($this->path));
	}
}
