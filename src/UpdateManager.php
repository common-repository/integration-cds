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

namespace AlexaCRM\Nextgen;

use AlexaCRM\Nextgen\Forms\FormRegistrationRepository;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Performs required changes upon plugin update.
 */
class UpdateManager {

    /**
     * @var string[]
     */
    public static array $gutenberBlocksReplacementMap = [
        'icds/simple-twig-block' => 'icds/gutenberg-monaco-block',
    ];

    /**
     * Converts deprecated Gutenberg editor blocks.
     */
    public static function convertGutenbergBlocks() {
        $query = new \WP_Query( [
            'post_type' => get_post_types( [
                'public' => true,
            ] ),

            'posts_per_page' => -1,
            'nopaging' => true,
        ] );

        foreach ( $query->get_posts() as $post ) {
            foreach ( static::$gutenberBlocksReplacementMap as $from => $to ) {
                if ( !has_block( $from, $post ) ) {
                    continue;
                }

                $fromClassName = str_replace( '/', '-', $from );
                $toClassName = str_replace( '/', '-', $to );

                $replaceMap = [
                    "<!-- wp:{$from}" => "<!-- wp:{$to}",
                    "<div class=\"wp-block-{$fromClassName}" => "<div class=\"wp-block-{$toClassName}",
                    "<!-- /wp:{$from}" => "<!-- /wp:{$to}",
                ];

                $replacedContent = str_replace(
                    array_keys( $replaceMap ),
                    array_values( $replaceMap ),
                    $post->post_content
                );

                $updateResult = wp_update_post( (object)[
                    'ID' => $post->ID,
                    'post_content' => $replacedContent,
                ], true );

                if ( $updateResult instanceof \WP_Error ) {
                    LoggerProvider::instance()->getLogger()->error( __( "Failed to convert block for post", 'integration-cds' ) . "{$post->ID}: {$updateResult->get_error_message()} ", [
                        'result' => $updateResult,
                    ] );
                } else {
                    LoggerProvider::instance()->getLogger()->info( "Deprecated page block converted successfully for post {$post->ID}" );
                }
            }
        }
    }

    /**
     * Transform custom button label to array of labels
     */
    public static function updateCustomButtonLabels(): void {
        if ( !InformationProvider::instance()->isPremiumOperating() ) {
            return;
        }

        $repo = FormRegistrationRepository::instance();
        $formRegistrations = $repo->getRegistrations();
        foreach ( $formRegistrations as $registration ) {
            if ( !empty( $registration->customButtons ) ) {
                foreach ( $registration->customButtons as &$button ) {
                    $labels = [];
                    if ( isset( $button['label'] ) && !empty( $button['label'] ) && !isset( $button['labels'] ) ) {
                        $labels[] = [
                            'code' => 'en-gb',
                            'text' => 'Submit',
                        ];

                        unset( $button['label'] );
                        $button['labels'] = $labels;
                        $repo->update( $registration );
                    }
                }
            }
        }
    }

    /**
     * Add existing fetchXmls to repository of fetchXml templates and bind to the corresponding posts.
     *
     * @throws \Twig\Error\SyntaxError
     */
    public static function addExistingFetchXmlTemplates(): void {
        if ( !InformationProvider::instance()->isPremiumOperating() ) {
            return;
        }

        // Add fetch xmls from bounded posts
        foreach ( BindingService::instance()->getBoundPosts() as $postId ) {
            $post = get_post( $postId );
            $binding = BindingService::instance()->getPostBinding( $post );
            if ( $binding === null ) {
                continue;
            }

            $settings = $binding->getBinding();
            if ( $settings === null && empty( $settings->conditionalQuery ) ) {
                continue;
            }

            $fetchXmlTemplateSettings = [
                'name' => $post->post_name . ' template',
                'source' => $settings->conditionalQuery,
                'params' => [],
            ];

            // Bind created fetchXml template to the post
            $template = FetchXmlTemplate::createFetchXmlTemplate( $fetchXmlTemplateSettings );
            $settings->fetchXmlTemplateSettings = (array)$template;
            $binding->bind( $settings );
        }

        // Add fetch xmls from form registrations
        $repo = FormRegistrationRepository::instance();
        $formRegistrations = $repo->getRegistrations();
        foreach ( $formRegistrations as $registration ) {
            if ( !empty( $registration->lookupSettings ) ) {
                foreach ( $registration->lookupSettings as &$setting ) {
                    if ( isset( $setting['lookupFiltersQuery'] ) && !empty( $setting['lookupFiltersQuery'] ) ) {
                        $fetchXmlTemplateSettings = [
                            'name' => $registration->name . ' template',
                            'source' => $setting['lookupFiltersQuery'],
                            'params' => [],
                        ];
                        // Bind created fetchXml template to the post
                        $setting['fetchXmlTemplateSettings'] = (array)FetchXmlTemplate::createFetchXmlTemplate( $fetchXmlTemplateSettings );
                        $repo->update( $registration );
                    }
                }
            }
        }
    }
}
