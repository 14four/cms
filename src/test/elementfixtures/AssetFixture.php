<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */
namespace craft\test\elementfixtures;

use Craft;
use craft\elements\Asset;

/**
 * Class AssetFixture.
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
abstract class AssetFixture extends ElementFixture
{
    /**
     * {@inheritdoc}
     */
    public $modelClass = Asset::class;

    /**
     * TODO: Cant we just call parent::load() here?
     * {@inheritdoc}
     */
    public function load(): void
    {

        $this->data = [];
        foreach ($this->getData() as $alias => $data) {
            $element = $this->getElement();
            foreach ($data as $handle => $value) {
                $element->$handle = $value;
            }
            try {
                $result = Craft::$app->getElements()->saveElement($element);
            } catch (\PHPUnit\Framework\Exception $e) {
                break; // do nothing while testing
            }
            if (!$result) {
                throw new ErrorException(join(' ', $element->getErrorSummary(true)));
            }
            $this->data[$alias] = array_merge($data, ['id' => $element->id]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function unload(): void
    {
        foreach ($this->getData() as $data) {
            $element = $this->getElement($data);
            if ($element) {
                try {
                    Craft::$app->getElements()->deleteElement($element);
                } catch (\PHPUnit\Framework\Exception $e) {
                    break; // do nothing while testing
                }
            }
        }
        $this->data = [];
    }

    /**
     * {@inheritdoc}
     */
    protected function isPrimaryKey(string $key): bool
    {
        return $key === 'volumeId' || $key === 'folderId' || $key === 'filename' || $key === 'title';
    }

    /**
     * Get asset model.
     *
     * @param array $data
     *
     * @return Asset
     */
    public function getElement(array $data = null): ?Asset
    {
        $element = parent::getElement($data);
        if (is_null($data)) {
            $element->avoidFilenameConflicts = true;
            $element->setScenario(Asset::SCENARIO_REPLACE);
        }
        return $element;
    }
}