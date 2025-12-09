<?php

namespace TrueQAP\FluentEmailPiping\Email;

use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;

/**
 * IMAP/POP3 Email Fetcher using Webklex/php-imap (no ext-imap required)
 */
class Fetcher {

    /**
     * @var array Account configuration
     */
    private array $config;

    /**
     * @var Client|null
     */
    private ?Client $client = null;

    /**
     * Constructor
     *
     * @param array $config Account configuration
     */
    public function __construct( array $config ) {
        $this->config = $config;
    }

    /**
     * Get client configuration array
     */
    private function getClientConfig(): array {
        $protocol = $this->config['protocol'] ?? 'imap';
        $encryption = $this->config['encryption'] ?? 'ssl';

        return [
            'host'          => $this->config['host'] ?? '',
            'port'          => $this->config['port'] ?? ( $protocol === 'pop3' ? 995 : 993 ),
            'encryption'    => $encryption === 'none' ? false : $encryption,
            'validate_cert' => empty( $this->config['novalidate_cert'] ),
            'username'      => $this->config['username'] ?? '',
            'password'      => $this->config['password'] ?? '',
            'protocol'      => $protocol,
            'timeout'       => 30,
        ];
    }

    /**
     * Connect to mailbox
     *
     * @throws ConnectionFailedException
     */
    private function connect(): Client {
        if ( $this->client !== null && $this->client->isConnected() ) {
            return $this->client;
        }

        $cm = new ClientManager();
        $this->client = $cm->make( $this->getClientConfig() );
        $this->client->connect();

        return $this->client;
    }

    /**
     * Test connection
     *
     * @return array Result with success status and message/count
     */
    public function testConnection(): array {
        try {
            $client = $this->connect();
            $folder = $client->getFolder( $this->config['folder'] ?? 'INBOX' );

            if ( ! $folder ) {
                return [
                    'success' => false,
                    'message' => __( 'Folder not found', 'fluent-support-email-piping' ),
                ];
            }

            $messages = $folder->messages()->all()->get();

            return [
                'success' => true,
                'count'   => $messages->count(),
            ];
        } catch ( ConnectionFailedException $e ) {
            return [
                'success' => false,
                'message' => sprintf(
                    /* translators: %s: error message */
                    __( 'Connection failed: %s', 'fluent-support-email-piping' ),
                    $e->getMessage()
                ),
            ];
        } catch ( \Exception $e ) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch new emails
     *
     * @param int $limit Maximum number of emails to fetch
     * @return array Array of parsed emails
     */
    public function fetchEmails( int $limit = 10 ): array {
        try {
            $client = $this->connect();
            $folder = $client->getFolder( $this->config['folder'] ?? 'INBOX' );

            if ( ! $folder ) {
                $this->logError( 'Folder not found: ' . ( $this->config['folder'] ?? 'INBOX' ) );
                return [];
            }

            // Build query
            $query = $folder->messages();

            // Fetch unseen or all
            if ( empty( $this->config['fetch_read'] ) ) {
                $query = $query->unseen();
            } else {
                $query = $query->all();
            }

            $messages = $query->limit( $limit )->get();

            if ( $messages->count() === 0 ) {
                return [];
            }

            $emails = [];

            foreach ( $messages as $message ) {
                try {
                    $email = $this->parseEmail( $message );
                    if ( $email ) {
                        $emails[] = $email;
                    }
                } catch ( \Exception $e ) {
                    $this->logError( sprintf( 'Failed to parse email: %s', $e->getMessage() ) );
                }
            }

            return $emails;

        } catch ( \Exception $e ) {
            $this->logError( 'Failed to fetch emails: ' . $e->getMessage() );
            return [];
        }
    }

    /**
     * Parse a single email
     *
     * @param \Webklex\PHPIMAP\Message $message
     * @return array|null Parsed email data
     */
    private function parseEmail( $message ): ?array {
        // Get email content
        $bodyHtml = $message->getHTMLBody() ?? '';
        $bodyText = $message->getTextBody() ?? '';

        // Prefer plain text, convert HTML if needed
        if ( empty( $bodyText ) && ! empty( $bodyHtml ) ) {
            $bodyText = $this->htmlToText( $bodyHtml );
        }

        // Parse sender - Webklex returns Attribute objects
        $from = $message->getFrom();
        $fromAddress = '';
        $fromName = '';

        if ( $from ) {
            $fromArray = $from->toArray();
            if ( ! empty( $fromArray ) ) {
                $firstFrom = reset( $fromArray );
                $fromAddress = $firstFrom->mail ?? '';
                $fromName = $firstFrom->personal ?? '';
            }
        }

        // Parse recipients (To)
        $toAddresses = [];
        $to = $message->getTo();
        if ( $to ) {
            foreach ( $to->toArray() as $recipient ) {
                $toAddresses[] = [
                    'address' => $recipient->mail ?? '',
                    'name'    => $recipient->personal ?? $recipient->mail ?? '',
                ];
            }
        }

        // Parse CC
        $ccAddresses = [];
        $cc = $message->getCc();
        if ( $cc ) {
            foreach ( $cc->toArray() as $recipient ) {
                $ccAddresses[] = [
                    'address' => $recipient->mail ?? '',
                    'name'    => $recipient->personal ?? $recipient->mail ?? '',
                ];
            }
        }

        // Handle attachments
        $attachments = $this->processAttachments( $message );

        // Get subject - handle Attribute object
        $subjectAttr = $message->getSubject();
        $subject = '';
        if ( $subjectAttr ) {
            $subject = (string) $subjectAttr;
        }

        // Check for forwarded email
        $forwarded = $this->detectForwardedEmail( $subject, $bodyText );

        // Get message ID - handle Attribute object
        $messageIdAttr = $message->getMessageId();
        $messageId = '';
        if ( $messageIdAttr ) {
            $messageId = (string) $messageIdAttr;
        }

        // Get date - handle Attribute object that contains Carbon date
        $dateAttr = $message->getDate();
        $dateString = '';
        if ( $dateAttr ) {
            $dateValue = $dateAttr->first();
            if ( $dateValue instanceof \Carbon\Carbon ) {
                $dateString = $dateValue->format( 'Y-m-d H:i:s' );
            } elseif ( is_string( $dateValue ) ) {
                $dateString = $dateValue;
            }
        }

        return [
            'mail_id'     => $message->getUid(),
            'message_id'  => $messageId,
            'subject'     => $subject,
            'body_text'   => $bodyText,
            'body_html'   => $bodyHtml,
            'isMarkDown'  => false,
            'date'        => $dateString,
            'from'        => [
                'value' => [
                    [
                        'address' => $fromAddress,
                        'name'    => $fromName,
                    ],
                ],
            ],
            'to'          => [
                'value' => $toAddresses,
            ],
            'cc'          => [
                'value' => $ccAddresses,
            ],
            'forwarded'   => $forwarded,
            'attachments' => $attachments,
            '_message'    => $message, // Store reference for later operations
        ];
    }

    /**
     * Process email attachments
     *
     * @param \Webklex\PHPIMAP\Message $message
     * @return array
     */
    private function processAttachments( $message ): array {
        $result = [];
        $attachments = $message->getAttachments();

        if ( ! $attachments || $attachments->count() === 0 ) {
            return $result;
        }

        // Create attachments directory
        $uploadDir = wp_upload_dir();
        $attachmentsDir = $uploadDir['basedir'] . '/fsep-attachments';
        if ( ! file_exists( $attachmentsDir ) ) {
            wp_mkdir_p( $attachmentsDir );
        }

        $baseUrl = $uploadDir['baseurl'] . '/fsep-attachments';

        foreach ( $attachments as $attachment ) {
            $filename = $attachment->getName() ?? 'attachment';
            $content = $attachment->getContent();

            if ( empty( $content ) ) {
                continue;
            }

            // Generate unique filename
            $uniqueName = sprintf( '%d_%s_%s', time(), wp_generate_password( 8, false ), sanitize_file_name( $filename ) );
            $filePath = $attachmentsDir . '/' . $uniqueName;

            // Save file
            if ( file_put_contents( $filePath, $content ) !== false ) {
                $result[] = [
                    'filename'           => $filename,
                    'url'                => $baseUrl . '/' . $uniqueName,
                    'contentType'        => $attachment->getMimeType() ?? 'application/octet-stream',
                    'contentDisposition' => $attachment->getDisposition() ?? 'attachment',
                    'size'               => strlen( $content ),
                ];
            }
        }

        return $result;
    }

    /**
     * Detect forwarded email and extract original sender
     *
     * @param string $subject
     * @param string $body
     * @return array|null
     */
    private function detectForwardedEmail( string $subject, string $body ): ?array {
        // Check subject for forward indicators
        $forwardPatterns = [ '/^Fwd:/i', '/^FW:/i', '/^Továbbítva:/i' ];
        $isForwarded = false;

        foreach ( $forwardPatterns as $pattern ) {
            if ( preg_match( $pattern, $subject ) ) {
                $isForwarded = true;
                break;
            }
        }

        if ( ! $isForwarded ) {
            return null;
        }

        // Try to extract original sender from body
        $patterns = [
            '/From:\s*([^<\n]+)?<?([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})>?/i',
            '/Feladó:\s*([^<\n]+)?<?([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})>?/i',
        ];

        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $body, $matches ) ) {
                return [
                    'name'    => trim( $matches[1] ?? '' ),
                    'address' => trim( $matches[2] ),
                ];
            }
        }

        return null;
    }

    /**
     * Convert HTML to plain text
     *
     * @param string $html
     * @return string
     */
    private function htmlToText( string $html ): string {
        // Remove script and style tags
        $html = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $html );
        $html = preg_replace( '/<style\b[^>]*>(.*?)<\/style>/is', '', $html );

        // Convert common elements
        $html = preg_replace( '/<br\s*\/?>/i', "\n", $html );
        $html = preg_replace( '/<\/p>/i', "\n\n", $html );
        $html = preg_replace( '/<\/div>/i', "\n", $html );
        $html = preg_replace( '/<\/h[1-6]>/i', "\n\n", $html );
        $html = preg_replace( '/<li[^>]*>/i', "• ", $html );
        $html = preg_replace( '/<\/li>/i', "\n", $html );

        // Strip remaining tags
        $text = wp_strip_all_tags( $html );

        // Decode entities
        $text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

        // Clean up whitespace
        $text = preg_replace( '/[ \t]+/', ' ', $text );
        $text = preg_replace( '/\n{3,}/', "\n\n", $text );

        return trim( $text );
    }

    /**
     * Mark email as read
     *
     * @param int $mailId
     */
    public function markAsRead( int $mailId ): void {
        try {
            $client = $this->connect();
            $folder = $client->getFolder( $this->config['folder'] ?? 'INBOX' );

            if ( $folder ) {
                $message = $folder->messages()->getMessageByUid( $mailId );
                if ( $message ) {
                    $message->setFlag( 'Seen' );
                }
            }
        } catch ( \Exception $e ) {
            $this->logError( sprintf( 'Failed to mark email #%d as read: %s', $mailId, $e->getMessage() ) );
        }
    }

    /**
     * Delete email
     *
     * @param int $mailId
     */
    public function deleteEmail( int $mailId ): void {
        try {
            $client = $this->connect();
            $folder = $client->getFolder( $this->config['folder'] ?? 'INBOX' );

            if ( $folder ) {
                $message = $folder->messages()->getMessageByUid( $mailId );
                if ( $message ) {
                    $message->delete();
                }
            }
        } catch ( \Exception $e ) {
            $this->logError( sprintf( 'Failed to delete email #%d: %s', $mailId, $e->getMessage() ) );
        }
    }

    /**
     * Log error message
     *
     * @param string $message
     */
    private function logError( string $message ): void {
        $settings = get_option( 'fsep_settings', [] );

        if ( ! empty( $settings['debug_mode'] ) ) {
            error_log( '[Fluent Support Email Piping] ' . $message );
        }
    }

    /**
     * Close connection
     */
    public function disconnect(): void {
        if ( $this->client !== null ) {
            try {
                $this->client->disconnect();
            } catch ( \Exception $e ) {
                // Ignore disconnect errors
            }
            $this->client = null;
        }
    }

    /**
     * Destructor - ensure connection is closed
     */
    public function __destruct() {
        $this->disconnect();
    }
}
