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

use Twig\Compiler;
use Twig\Node\Node;

/**
 * Represents the `fetchxml` tag node.
 */
class FetchxmlNode extends Node {

    /**
     * FetchxmlNode constructor.
     *
     * @param Node $fetchxmlNode
     * @param array $attributes
     * @param int $lineNo
     * @param null $tag
     */
    public function __construct( $fetchxmlNode = null, array $attributes = [], $lineNo = 0, $tag = null ) {
        parent::__construct( [ 'fetchxml' => $fetchxmlNode ], $attributes, $lineNo, $tag );
    }

    /**
     * Compiles the node.
     *
     * @param Compiler $compiler
     */
    public function compile( Compiler $compiler ) {
        $compiler->write( "ob_start();\n" );
        $compiler->subcompile( $this->getNode( 'fetchxml' ) );
        $compiler->write( "\$fetchXML = trim(ob_get_clean());\n" );

        if ( $this->hasAttribute( 'cache' ) ) {
            $compiler->write( <<<PHP
/** @var \$fetchXML string */
\$cacheKey = '{$this->getAttribute( 'collection' )}.{$this->getAttribute( 'cache' )}.' . sha1( \$fetchXML );

try {
    \$di = new \DateInterval( '{$this->getAttribute( 'cache' )}' );
    \$now = new \DateTimeImmutable();
    \$exp = \$now->add( \$di );
    \$ttl = \$exp->getTimestamp() - \$now->getTimestamp();
    \$entityName = ( simplexml_load_string( \$fetchXML ) )->xpath( '//entity/@name' )[0];
    \$pool = \AlexaCRM\Nextgen\CacheProvider::instance()->providePool( 'fetchxml-' . \$entityName, \$ttl );
} catch ( \Exception \$e ) {
    echo '<div class="alert alert-warning">' . __( 'Invalid cache interval format.', 'integration-cds' ) . '</div>';
    \$pool = new \Symfony\Component\Cache\Adapter\NullAdapter();
}
\$logger = \AlexaCRM\Nextgen\LoggerProvider::instance()->getLogger();

\$cache = \$pool->getItem( \$cacheKey );
if ( \$cache->isHit() ) {
    \$logger->debug( "[isHit]: Get \$cacheKey from the cache" );
    \$context['{$this->getAttribute( 'collection' )}'] = \$cache->get();
} else {
    \$logger->debug( "[isHit]: \$cacheKey was not found in cache" );
PHP
            );
        }
        $compiler->write( <<<PHP
/** @var \$fetchXML string */
/** @var \$cache \Psr\Cache\CacheItemInterface */
/** @var \$pool \Psr\Cache\CacheItemPoolInterface */
if ( \AlexaCRM\Nextgen\ConnectionService::instance()->isAvailable() ) {
    \$errorMessage = null;
    \$collection = new \AlexaCRM\Xrm\EntityCollection();
    \$client = \AlexaCRM\Nextgen\ConnectionService::instance()->getClient();
    \$query = new \AlexaCRM\Xrm\Query\FetchExpression( \$fetchXML );

    \$result = new \AlexaCRM\Nextgen\Twig\FetchxmlResult();
    \$result->xml = \$fetchXML;
    \$logger = \AlexaCRM\Nextgen\LoggerProvider::instance()->getLogger();

    try {
        \$logger->debug( 'Twig compile query request', [ \$query ] );
        \$collection = \$client->RetrieveMultiple( \$query );
        \$logger->debug( 'Twig compile query response', [ \$collection ] );
        \$result->results = \AlexaCRM\Nextgen\Twig\FetchxmlCollection::createFromEntityCollection( \$collection );
    } catch ( \Exception \$e ) {
        \$result->error = \$e->getMessage();
    }

    if ( isset( \$pool ) ) {
        \$pool->save( \$cache->set( \$result ) );
    }

    \$context['{$this->getAttribute( 'collection' )}'] = \$result;
} else {
    echo '<div class="alert alert-warning">' . __( 'FetchXML queries work with a configured CDS connection only.', 'integration-cds' ) . '</div>';
}
PHP
        );

        if ( $this->hasAttribute( 'cache' ) ) {
            // Close the else block.
            $compiler->write( <<<PHP
}
PHP
            );
        }
    }

}
