<?php
namespace Blocks;

/**
 *
 */
class IncludeResource_Node extends \Twig_Node
{
	/**
	 * Compiles an IncludeResource_Node into PHP.
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$function = $this->getAttribute('function');
		$path = $this->getNode('path');

		$compiler
			->addDebugInfo($this)
			->write('\Blocks\blx()->templates->'.$function.'(')
			->subcompile($path);

		if ($this->getAttribute('first'))
		{
			$compiler->raw(', true');
		}

		$compiler->raw(");\n");
	}
}
