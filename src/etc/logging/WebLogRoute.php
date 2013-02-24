<?php
namespace Craft;

/**
 *
 */
class WebLogRoute extends \CWebLogRoute
{
	/**
	 * @access protected
	 * @param $view
	 * @param $data
	 * @return mixed
	 */
	protected function render($view, $data)
	{
		$isAjax = craft()->request->isAjaxRequest();
		$mimeType = craft()->request->getMimeType();

		if (craft()->config->get('devMode') && !craft()->request->isResourceRequest())
		{
			if ($this->showInFireBug)
			{
				if ($isAjax && $this->ignoreAjaxInFireBug)
				{
					return;
				}

				$view .= '-firebug';
			}
			else if(!(craft() instanceof \CWebApplication) || $isAjax)
			{
				return;
			}

			if ($mimeType !== 'text/html')
			{
				return;
			}

			$viewFile = craft()->path->getCpTemplatesPath().'logging/'.$view.'.php';
			include(craft()->findLocalizedFile($viewFile, 'en'));
		}
	}
}
