<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\d3;

use craft\web\AssetBundle;

/**
 * D3 asset bundle.
 */
class D3Asset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@bower/d3';

        $this->js = [
            'd3'.$this->dotJs(),
        ];

        parent::init();
    }
}
