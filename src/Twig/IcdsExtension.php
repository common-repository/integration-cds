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

namespace AlexaCRM\Nextgen\Twig;

use AlexaCRM\Nextgen\API\Endpoint;
use AlexaCRM\Nextgen\ConnectionService;
use AlexaCRM\Nextgen\Twig\Internals\CustomFormTokenParser;
use AlexaCRM\Nextgen\Twig\Internals\FetchxmlTokenParser;
use AlexaCRM\Nextgen\TwigProvider;
use AlexaCRM\Xrm\ColumnSet;
use AlexaCRM\Xrm\EntityReference;
use Twig\Extension\AbstractExtension;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Provides plugin-related extensions for Twig.
 */
class IcdsExtension extends AbstractExtension {

    /**
     * Returns the token parser instances to add to the existing list.
     *
     * @return TokenParserInterface[]
     */
    public function getTokenParsers(): array {
        $parsers[ FetchxmlTokenParser::TAG_BEGIN ] = new FetchxmlTokenParser();
        $parsers[ CustomFormTokenParser::TAG_BEGIN ] = new CustomFormTokenParser();

        /**
         * Filters the collection of token parser implementations for inclusion into Twig via extension.
         *
         * @param TokenParserInterface[] Maps tags (opening tags) to TokenParserInterface-compatible objects.
         */
        $parsers = apply_filters( 'integration-cds/twig/token-parsers-ext', $parsers );

        return array_values( $parsers );
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return TwigFilter[]
     */
    public function getFilters(): array {
        $filters = [
            'formatted_value' => new TwigFilter(
                'formatted_value',
                function( $record, $attributeName ) {
                    return $record->FormattedValues[ $attributeName ] ?? $record[ $attributeName ];
                }
            ),
            'to_entity_reference' => new TwigFilter(
                'to_entity_reference',
                function( $value ) {
                    return Util::toEntityReference( $value );
                }
            ),
            'expand' => new TwigFilter(
                'expand',
                function( $record, $expandAttributeName, $expandColumns = '' ) {
                    $result = null;
                    if ( $expandColumns && is_string( $expandColumns ) ) {
                        $columnsSet = new ColumnSet( array_map( 'trim', explode( ',', $expandColumns ) ) );
                    } elseif ( is_array( $expandColumns ) ) {
                        $columnsSet = new ColumnSet( $expandColumns );
                    } else {
                        $columnsSet = new ColumnSet( true );
                    }

                    if ( $record->Attributes[ $expandAttributeName ] instanceof EntityReference ) {
                        $result = ConnectionService::instance()->getClient()->Retrieve( $record[ $expandAttributeName ]->LogicalName, $record[ $expandAttributeName ]->Id, $columnsSet );
                    }

                    return $result;
                }
            ),
        ];

        /**
         * Filters the collection of Twig filters for inclusion into Twig via extension.
         *
         * @param TwigFilter[] Maps filter names to TwigFilter objects.
         */
        $filters = apply_filters( 'integration-cds/twig/filters', $filters );

        return array_values( $filters );
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions(): array {
        $functions = [
            'image_url' => new TwigFunction( 'image_url', function( $entityRef, $column = '', $isThumb = false, $headers = null ) {
                $ref = Util::toEntityReference( $entityRef );
                if ( $ref === null ) {
                    return null;
                }

                $targetColumn = $column;
                if ( $ref->LogicalName === 'annotation' ) {
                    $targetColumn = 'documentbody';
                }

                $query = [
                    'table' => $ref->LogicalName,
                    'id' => $ref->Id,
                    'column' => $targetColumn,
                    'h' => $headers,
                ];

                if ( $isThumb ) {
                    $query['isThumb'] = '1';
                }

                return get_rest_url() . Endpoint::DEFAULT_NS . '/' . 'image?' . http_build_query( $query );
            } ),
            'file_url' => new TwigFunction( 'file_url', function( $entityRef, $column = '', $headers = null ) {
                $ref = Util::toEntityReference( $entityRef );
                if ( $ref === null ) {
                    return null;
                }

                $targetColumn = $column;
                if ( $ref->LogicalName === 'annotation' ) {
                    $targetColumn = 'documentbody';
                }

                $query = [
                    'table' => $ref->LogicalName,
                    'id' => $ref->Id,
                    'column' => $targetColumn,
                    'h' => $headers,
                ];

                return get_rest_url() . Endpoint::DEFAULT_NS . '/' . 'file?' . http_build_query( $query );
            } ),
            'last_error' => new TwigFunction( 'last_error', function() {
                return TwigProvider::instance()->getLastError();
            } ),
            'is_object' => new TwigFunction( 'is_object', function( $var ) {
                return is_object( $var );
            } ),
            'is_email' => new TwigFunction( 'is_email', function( $var ) {
                return (bool)filter_var( $var, FILTER_VALIDATE_EMAIL );
            } ),
        ];

        if ( !TwigProvider::isDebug() ) {
            $functions['dump'] = new TwigFunction( 'dump', function() {} );
        }
        /**
         * Filters the collection of Twig functions for inclusion into Twig via extension.
         *
         * @param TwigFunction[] Maps function names to TwigFunction objects.
         */
        $functions = apply_filters( 'integration-cds/twig/functions', $functions );

        return array_values( $functions );
    }
}
