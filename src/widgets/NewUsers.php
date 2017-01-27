<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\widgets;

use Craft;
use craft\base\Widget;
use craft\helpers\Json;
use craft\web\assets\newusers\NewUsersAsset;
use yii\base\Exception;

/**
 * NewUsers represents a New Users dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class NewUsers extends Widget
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'New Users');
    }

    /**
     * @inheritdoc
     */
    public static function isSelectable(): bool
    {
        // This widget is only available for Craft Pro
        return (Craft::$app->getEdition() === Craft::Pro);
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        $iconPath = Craft::getAlias('@app/icons/users.svg');

        if ($iconPath === false) {
            throw new Exception('There was a problem getting the icon path.');
        }

        return $iconPath;
    }

    // Properties
    // =========================================================================

    /**
     * @var int|null The ID of the user group
     */
    public $userGroupId;

    /**
     * @var string|null The date range
     */
    public $dateRange;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        if ($groupId = $this->userGroupId) {
            $userGroup = Craft::$app->getUserGroups()->getGroupById($groupId);

            if ($userGroup) {
                return Craft::t('app', 'New Users').' – '.Craft::t('app', $userGroup->name);
            }
        }

        return parent::getTitle();
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml()
    {
        if (Craft::$app->getEdition() !== Craft::Pro) {
            return false;
        }

        $options = $this->getSettings();
        $options['orientation'] = Craft::$app->getLocale()->getOrientation();

        $view = Craft::$app->getView();
        $view->registerAssetBundle(NewUsersAsset::class);
        $view->registerJs('new Craft.NewUsersWidget('.$this->id.', '.Json::encode($options).');');

        return '<div></div>';
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('_components/widgets/NewUsers/settings',
            [
                'widget' => $this
            ]);
    }
}
