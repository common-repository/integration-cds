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

use Twig\Node\Node;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Base class for token parsers in the plugin.
 */
abstract class TokenParser extends AbstractTokenParser {

    /**
     * Tells whether given template is empty - no nodes or only spaces/new lines/etc.
     *
     * @param Node $template
     *
     * @return bool
     */
    protected function isEmptyTemplate( Node $template ) {
        $allowedNodes = [
            'Twig\Node\Node', 'Twig\Node\TextNode', 'Twig_Node', 'Twig_Node_Text',
        ];

        return in_array( get_class( $template ), $allowedNodes, true )
               && !$template->count()
               && ( !$template->hasAttribute( 'data' ) || trim( $template->getAttribute( 'data' ) ) === '' );
    }

    /**
     * Gets the ending tag name associated with this token parser if presents.
     *
     * @return string|null
     */
    public function getTagEnd() {
        if ( defined('static::TAG_END') ) {
            return constant('static::TAG_END');
        }

        return null;
    }

    /**
     * Gets all handled attributes names associated with this token parser.
     *
     * @return string[]
     */
    public static function getAttributes() {
        $reflection = new \ReflectionClass( static::class );

        $attrConstants = array_filter(
            $reflection->getConstants(),
            function( $key ) {
                return ( strpos( $key, 'ATTR_' ) === 0 );
            },
            ARRAY_FILTER_USE_KEY
        );

        return array_values( $attrConstants );
    }

}

