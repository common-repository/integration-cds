<?php
/*
 * Copyright 2021 AlexaCRM
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

namespace AlexaCRM\Nextgen\Webhooks;

use AlexaCRM\Nextgen\ConnectionService;
use AlexaCRM\Nextgen\LoggerProvider;
use Exception;
use GuzzleHttp\Client;
use WP_Query;

/**
 * Provides a facility to notify webhook handlers about events in WordPress.
 */
class Runner {

    /**
     * Custom post type name that is used to identify webhooks in the database.
     */
    public const POST_TYPE = 'icds_webhook';

    /**
     * Meta key to store the webhook topic.
     */
    public const TOPIC_KEY = 'icds_webhook_topic';

    /**
     * Meta key to store the webhook target.
     */
    public const TARGET_KEY = 'icds_webhook_target';

    /**
     * Wodpress 'post_status' field value for enabled webhook.
     */
    public const ENABLED_STATUS = 'publish';

    /**
     * Wodpress 'post_status' field value for disabled webhook.
     */
    public const DISABLED_STATUS = 'private';

    protected string $topic;

    /**
     * @var string[]
     */
    protected array $webhooks = [];

    public function __construct( string $topic ) {
        $this->topic = $topic;

        $q = new WP_Query( [
            'post_type' => Runner::POST_TYPE,
            'post_status' => Runner::ENABLED_STATUS,
            'meta_key' => Runner::TOPIC_KEY,
            'meta_value' => $topic,
            'fields' => 'ids',
            'nopaging' => true,
        ] );

        foreach ( $q->posts as $id ) {
            $target = trim( get_post_meta( $id, static::TARGET_KEY, true ) );
            if ( $target === '' ) {
                continue;
            }

            $this->webhooks[] = $target;
        }
    }

    /**
     * Sends the payload to every registered webhook.
     *
     * @param $payload
     */
    public function trigger( $payload ): void {
        if ( count( $this->webhooks ) === 0 ) {
            return;
        }

        $client = static::createGuzzleClient();
        $logger = LoggerProvider::instance()->getLogger();

        foreach ( $this->webhooks as $target ) {
            try {
                $client->post( $target, [
                    'json' => $payload,
                ] );
            } catch ( Exception $e ) {
                $logger->error( sprintf( __( "Failed to trigger <%s> on '%s'. %s" ), $target, $this->topic, $e->getMessage() ), [
                    'exception' => $e,
                ] );
            }
        }
    }

    protected static function createGuzzleClient(): Client {
        $settings = ConnectionService::instance()->getResolvedSettings();

        $verify = $settings->tlsVerifyPeers;
        if ( $verify && $settings->caBundlePath !== null ) {
            $verify = $settings->caBundlePath;
        }

        return new Client( [
            'verify' => $verify,
        ] );
    }

}
