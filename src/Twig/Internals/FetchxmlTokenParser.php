<?php
/**
 * Copyright 2018-2019 AlexaCRM
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

use Twig\Error\SyntaxError;
use Twig\Node\Node;
use Twig\Token;

/**
 * Implements token parser for the `fetchxml` tag.
 */
class FetchxmlTokenParser extends TokenParser {

    const TAG_BEGIN = 'fetchxml';
    const TAG_END = 'endfetchxml';

    /**
     * Parses a token and returns a node.
     *
     * @param Token $token
     *
     * @return Node
     * @throws SyntaxError
     */
    public function parse( Token $token ) {
        $parser = $this->parser;
        $stream = $parser->getStream();
        $lineNo = $token->getLine();

        $arguments = [];

        while ( !$stream->test( Token::BLOCK_END_TYPE ) ) {
            if ( !$stream->test( Token::NAME_TYPE ) ) {
                $stream->next();
                continue;
            }

            $argName = $stream->expect( Token::NAME_TYPE )->getValue();
            $stream->expect( Token::OPERATOR_TYPE, '=' );
            $argValue = $stream->expect( Token::STRING_TYPE )->getValue();
            $arguments[ $argName ] = $argValue;
        }

        $stream->expect( Token::BLOCK_END_TYPE );
        $fetchxml = $parser->subparse( [ $this, 'decideFetchxmlEnd' ] );

        $stream->expect( Token::NAME_TYPE, static::TAG_END );
        $stream->expect( Token::BLOCK_END_TYPE );

        if ( !array_key_exists( 'collection', $arguments ) ) {
            throw new SyntaxError( __('FetchXML tag requires the `collection` parameter.', 'integration-cds') );
        }

        return new FetchxmlNode( $fetchxml, $arguments, $lineNo );
    }

    /**
     * Tells whether the current node is END_TOKEN.
     *
     * @param Token $token
     *
     * @return bool
     */
    public function decideFetchxmlEnd( Token $token ) {
        return $token->test( static::TAG_END );
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag() {
        return static::TAG_BEGIN;
    }
}
