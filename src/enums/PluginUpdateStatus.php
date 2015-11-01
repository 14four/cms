<?php
namespace Craft;

/**
 * The PluginUpdateStatus class is an abstract class that defines all of the possible plugin update statuses.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.enums
 * @since     2.5
 */
abstract class PluginUpdateStatus extends BaseEnum
{
	// Constants
	// =========================================================================

	const UpToDate         = 'uptodate';
	const UpdatesAvailable = 'updatesavailable';
	const Unknown          = 'unknown';
}
