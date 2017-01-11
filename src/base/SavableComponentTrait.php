<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\base;

use DateTime;

/**
 * SavableComponentTrait implements the common methods and properties for savable component classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
trait SavableComponentTrait
{
    // Properties
    // =========================================================================

    /**
     * @var int|null The component’s ID
     */
    public $id;

    /**
     * @var DateTime|null The date that the component was created
     */
    public $dateCreated;

    /**
     * @var DateTime|null The date that the component was last updated
     */
    public $dateUpdated;
}
