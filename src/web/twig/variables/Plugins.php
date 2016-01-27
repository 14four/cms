<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

/**
 * Plugin functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Plugins
{
    // Public Methods
    // =========================================================================

    /**
     * Returns info about all of the plugins saved in craft/plugins, whether they’re installed or not.
     *
     * @return array Info about all of the plugins saved in craft/plugins
     */
    public function getPluginInfo()
    {
        return \Craft::$app->getPlugins()->getPluginInfo();
    }
}
