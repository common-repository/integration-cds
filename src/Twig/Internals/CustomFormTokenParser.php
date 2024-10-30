<?php
/**
 * Copyright 2019 AlexaCRM
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
 * associated documentation files (the "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE
 * OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace AlexaCRM\Nextgen\Twig\Internals;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use AlexaCRM\Nextgen\Forms\CustomFormAttributes;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\AbstractExpression;
use Twig\Token;

/**
 * Parses the `form` token.
 */
class CustomFormTokenParser extends TokenParser {

    const TAG_BEGIN = 'form';
    const TAG_END = 'endform';

    const ATTR_ENTITY = 'entity';
    const ATTR_MODE = 'mode';
    const ATTR_RECORD = 'record';

    /**
     * Parses a token and returns a node.
     *
     * @param Token $token
     *
     * @return CustomFormNode A Twig_Node instance
     * @throws SyntaxError
     */
    public function parse( Token $token ) {
        $parser = $this->parser;
        $stream = $parser->getStream();
        $lineNo = $token->getLine();

        /** @var AbstractExpression[] $arguments */
        $arguments = [];

        while ( !$stream->test( Token::BLOCK_END_TYPE ) ) {
            if ( !$stream->test( Token::NAME_TYPE ) ) {
                $stream->next();
                continue;
            }

            $argName = $stream->expect( Token::NAME_TYPE )->getValue();
            $arguments[$argName] = true;

            if ( $stream->test( Token::OPERATOR_TYPE, '=' ) ) {
                $stream->expect( Token::OPERATOR_TYPE, '=' );
                $argValue = $parser->getExpressionParser()->parsePrimaryExpression();
                $arguments[$argName] = $argValue;
            }
        }

        $stream->expect( Token::BLOCK_END_TYPE );

        if ( !$this->testRequiredArguments( [ static::ATTR_ENTITY ], $arguments ) ) {
            throw new SyntaxError( __( 'Form must have `' . static::ATTR_ENTITY . '` argument.', 'integration-cds' ), $lineNo );
        }

        if ( isset( $arguments[static::ATTR_MODE] )
             && $arguments[static::ATTR_MODE]->getAttribute( 'value' ) === CustomFormAttributes::MODE_UPDATE
             && !$this->testRequiredArguments( [ static::ATTR_RECORD ], $arguments )
        ) {
            throw new SyntaxError( __( 'Form `' . static::ATTR_RECORD . '` argument must be specified for update forms.', 'integration-cds' ), $lineNo, $stream->getSourceContext() );

        }
        $form = $parser->subparse( [ $this, 'decideTagEnd' ] );

        $stream->expect( Token::NAME_TYPE, static::TAG_END );
        $stream->expect( Token::BLOCK_END_TYPE );

        return new CustomFormNode( $form, $arguments, $lineNo );
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag() {
        return static::TAG_BEGIN;
    }

    /**
     * Tells whether the current node is end of the token.
     *
     * @param Token $token
     *
     * @return bool
     */
    public function decideTagEnd( Token $token ) {
        return $token->test( static::TAG_END );
    }

    /**
     * Tests if given arguments presents in declared tag arguments.
     *
     * @param array $requiredArguments
     * @param array $testedArgumentsList
     *
     * @return bool
     */
    protected function testRequiredArguments( array $requiredArguments, array $testedArgumentsList ) {
        $testedArgumentsNames = array_keys( $testedArgumentsList );
        $presentedRequiredArgumentsInTested = array_intersect( $requiredArguments, $testedArgumentsNames );

        return count( $presentedRequiredArgumentsInTested ) === count( $requiredArguments );
    }

}
