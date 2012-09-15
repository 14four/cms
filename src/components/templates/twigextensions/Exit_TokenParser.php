<?php
namespace Blocks;

/**
 *
 */
class Exit_TokenParser extends \Twig_TokenParser
{
	/**
	 * Parses {% exit %} tags.
	 *
	 * @param \Twig_Token $token
	 * @return IncludeCssFile_Node
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();

		if ($stream->test(\Twig_Token::NUMBER_TYPE))
			$status = $this->parser->getExpressionParser()->parseExpression();
		else
			$status = null;

		$stream->expect(\Twig_Token::BLOCK_END_TYPE);

		return new Exit_Node(array('status' => $status), array(), $lineno, $this->getTag());
	}

	/**
	 * Defines the tag name.
	 *
	 * @return string
	 */
	public function getTag()
	{
		return 'exit';
	}
}
