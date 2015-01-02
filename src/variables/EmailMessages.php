<?php
namespace craft\app\variables;

use craft\app\Craft;
use craft\app\models\RebrandEmail as RebrandEmailModel;

craft()->requireEdition(Craft::Client);

/**
 * Email functions.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.variables
 * @since     3.0
 */
class EmailMessages
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns all of the system email messages.
	 *
	 * @return array
	 */
	public function getAllMessages()
	{
		return craft()->emailMessages->getAllMessages();
	}

	/**
	 * Returns a system email message by its key.
	 *
	 * @param string      $key
	 * @param string|null $language
	 *
	 * @return RebrandEmailModel|null
	 */
	public function getMessage($key, $language = null)
	{
		return craft()->emailMessages->getMessage($key, $language);
	}
}
