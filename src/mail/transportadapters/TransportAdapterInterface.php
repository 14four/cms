<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\mail\transportadapters;

use craft\base\SavableComponentInterface;

/**
 * TransportAdapterInterface defines the common interface to be implemented by SwiftMailer transport adapter classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
interface TransportAdapterInterface extends SavableComponentInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the config array that should be passed to [[\craft\mail\Mailer::setTransport()]].
     *
     * Note that the return value will be stored in the database as JSON,
     * so you cannot create an actual \Swift_Transport object here.
     *
     * @return array The transport config
     */
    public function getTransportConfig();
}
