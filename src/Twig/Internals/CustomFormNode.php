<?php
/**
 * Copyright 2018 AlexaCRM
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

use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

/**
 * Represents the `form_custom` node.
 */
class CustomFormNode extends Node {

    /**
     * @var AbstractExpression[]
     */
    protected $attributes;

    /**
     * FormNode constructor.
     *
     * @param Node $formNode
     * @param array $attributes
     * @param int $lineno
     * @param string|null $tag
     */
    public function __construct( $formNode = null, array $attributes = [], int $lineno = 0, string $tag = null ) {
        parent::__construct( [ 'form' => $formNode ], $attributes, $lineno, $tag );
    }

    /**
     * Compiles the node.
     *
     * @param Compiler $compiler
     */
    public function compile( Compiler $compiler ) {
        $compiler->write( 'ob_start();' );
        $compiler->subcompile( $this->getNode( 'form' ) );
        $compiler->write( '$form = trim(ob_get_clean());' );

        $compiler->write( '$tagAttributes = [' );
        foreach ( $this->attributes as $attributeName => $attribute ) {
            $compiler->write( "'{$attributeName}' => " );
            if ( $attribute instanceof AbstractExpression ) {
                $attribute->compile( $compiler );
            } elseif ( is_bool( $attribute ) ) {
                $compiler->write( $attribute ? 'true' : 'false' );
            } else {
                $compiler->write( $attribute );
            }
            $compiler->write( ",\n" );
        }
        $compiler->write( '];' );

        $compiler->write( "
            \$renderer = new \AlexaCRM\Nextgen\Forms\CustomFormRenderer( \$form );
            if ( \$renderer->checkForm() ){
                \$renderer->render( \AlexaCRM\Nextgen\Forms\CustomFormAttributes::createFromArray( \$tagAttributes ) );
            } else {
                echo '<div class=\"alert alert-warning\">' . __( 'Custom form is malformed.', 'integration-cds' ) . '</div>';
            }
        " );
    }

}
