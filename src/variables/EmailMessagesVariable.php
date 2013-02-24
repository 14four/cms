<?php
namespace Craft;

Craft::requirePackage(CraftPackage::Rebrand);

/**
 * Email functions
 */
class EmailMessagesVariable
{
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
	 * @param string $key
	 * @param string|null $language
	 * @return EmailMessageModel|null
	 */
	public function getMessage($key, $language = null)
	{
		return craft()->emailMessages->getMessage($key, $language);
	}
}
