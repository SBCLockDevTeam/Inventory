<?php
/**
 * SmtpMailer — minimal SMTP client for sending transactional email.
 *
 * Supports STARTTLS (port 587) and implicit SSL/TLS (port 465).
 * Authentication uses AUTH LOGIN with credentials from config/secrets.php
 * (EMAIL_USER / EMAIL_PASS).
 *
 * Usage:
 *   $mailer = new SmtpMailer();
 *   $mailer->send($to, $subject, $body, $replyToName, $replyToEmail);
 */

class SmtpMailer {

    private string $host;
    private int    $port;
    private string $encryption; // 'tls' | 'ssl' | ''
    private string $user;
    private string $pass;
    private string $fromAddr;
    private string $fromName;

    /** @var resource|false */
    private $socket = false;

    private int $timeout = 15; // seconds per socket operation

    public function __construct() {
        $this->host       = defined('SMTP_HOST')       ? SMTP_HOST       : 'localhost';
        $this->port       = defined('SMTP_PORT')       ? (int)SMTP_PORT  : 587;
        $this->encryption = defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'tls';
        $this->fromAddr   = defined('SMTP_FROM_ADDR')  ? SMTP_FROM_ADDR  : '';
        $this->fromName   = defined('SMTP_FROM_NAME')  ? SMTP_FROM_NAME  : '';
        $this->user       = defined('EMAIL_USER')      ? EMAIL_USER      : '';
        $this->pass       = defined('EMAIL_PASS')      ? EMAIL_PASS      : '';
    }

    /**
     * Send an email via the configured SMTP relay.
     *
     * @param string $to           Recipient address
     * @param string $subject      Email subject
     * @param string $body         Plain-text body
     * @param string $replyToName  Reply-To display name
     * @param string $replyToEmail Reply-To address
     * @return bool                true on success
     * @throws RuntimeException    on SMTP protocol error
     */
    public function send(
        string $to,
        string $subject,
        string $body,
        string $replyToName  = '',
        string $replyToEmail = ''
    ): bool {
        $this->connect();

        try {
            $this->ehlo();

            if (strtolower($this->encryption) === 'tls') {
                $this->startTls();
                $this->ehlo(); // re-issue EHLO after TLS handshake
            }

            $this->authenticate();
            $this->mailTransaction($to, $subject, $body, $replyToName, $replyToEmail);
            $this->quit();
        } finally {
            $this->close();
        }

        return true;
    }

    // ---------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------

    private function connect(): void {
        $scheme = (strtolower($this->encryption) === 'ssl') ? 'ssl' : 'tcp';
        $dsn    = "{$scheme}://{$this->host}:{$this->port}";

        $errNo  = 0;
        $errStr = '';
        $sock = stream_socket_client(
            $dsn,
            $errNo,
            $errStr,
            $this->timeout,
            STREAM_CLIENT_CONNECT
        );

        if ($sock === false) {
            throw new RuntimeException("SMTP connect failed ({$dsn}): {$errStr} [{$errNo}]");
        }

        stream_set_timeout($sock, $this->timeout);
        $this->socket = $sock;

        // Read the server greeting (220)
        $this->expect('220');
    }

    private function ehlo(): void {
        $host = gethostname() ?: 'localhost';
        $this->send_command("EHLO {$host}");
        $this->expect('250');
    }

    private function startTls(): void {
        $this->send_command('STARTTLS');
        $this->expect('220');

        // Upgrade the plain TCP socket to TLS
        $ok = stream_socket_enable_crypto(
            $this->socket,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );

        if ($ok === false) {
            throw new RuntimeException('SMTP STARTTLS crypto negotiation failed');
        }
    }

    private function authenticate(): void {
        if ($this->user === '' && $this->pass === '') {
            return; // anonymous relay — skip AUTH
        }

        $this->send_command('AUTH LOGIN');
        $this->expect('334');

        $this->send_command(base64_encode($this->user));
        $this->expect('334');

        $this->send_command(base64_encode($this->pass));
        $this->expect('235');
    }

    private function mailTransaction(
        string $to,
        string $subject,
        string $body,
        string $replyToName,
        string $replyToEmail
    ): void {
        $this->send_command("MAIL FROM:<{$this->fromAddr}>");
        $this->expect('250');

        $this->send_command("RCPT TO:<{$to}>");
        $this->expect('250');

        $this->send_command('DATA');
        $this->expect('354');

        $message = $this->buildMessage($to, $subject, $body, $replyToName, $replyToEmail);

        // Dot-stuff: lines starting with '.' must be escaped (multiline match)
        $stuffed = preg_replace('/^\./m', '..', $message);

        $this->write($stuffed . "\r\n.\r\n");
        $this->expect('250');
    }

    private function buildMessage(
        string $to,
        string $subject,
        string $body,
        string $replyToName,
        string $replyToEmail
    ): string {
        $date    = date('r');
        $fromHdr = $this->formatAddress($this->fromName, $this->fromAddr);
        $toHdr   = $to;

        $headers  = "Date: {$date}\r\n";
        $headers .= "From: {$fromHdr}\r\n";
        $headers .= "To: {$toHdr}\r\n";

        if ($replyToEmail !== '') {
            $headers .= 'Reply-To: ' . $this->formatAddress($replyToName, $replyToEmail) . "\r\n";
        }

        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";

        // Normalise line endings in the body
        $body = str_replace("\r\n", "\n", $body);
        $body = str_replace("\n", "\r\n", $body);

        return $headers . "\r\n" . $body;
    }

    private function formatAddress(string $name, string $addr): string {
        if ($name === '') {
            return $addr;
        }
        return "\"{$name}\" <{$addr}>";
    }

    private function quit(): void {
        $this->send_command('QUIT');
        // 221 is normal; tolerate disconnects too
    }

    private function close(): void {
        if ($this->socket !== false) {
            fclose($this->socket);
            $this->socket = false;
        }
    }

    /** Write a CRLF-terminated command line to the socket. */
    private function send_command(string $cmd): void {
        $this->write($cmd . "\r\n");
    }

    /** Write raw data to the socket. */
    private function write(string $data): void {
        if ($this->socket === false) {
            throw new RuntimeException('SMTP socket is not open');
        }
        fwrite($this->socket, $data);
    }

    /**
     * Read lines from the socket until the final response line
     * (digit-digit-digit space …) and assert the expected code prefix.
     */
    private function expect(string $code): string {
        $response = '';
        while (($line = fgets($this->socket, 512)) !== false) {
            $response .= $line;
            // A line with "NNN " (space after 3-digit code) is the last line
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }

        if (substr(trim($response), 0, 3) !== $code) {
            throw new RuntimeException("SMTP expected {$code}, got: " . trim($response));
        }

        return $response;
    }
}
