<?php

namespace App\Console\Commands;

use App\Services\EmailSettingService;
use Illuminate\Console\Command;

class SmtpDiagnoseCommand extends Command
{
    protected $signature = 'smtp:diagnose
                            {--host= : Override SMTP host to test}
                            {--port= : Override SMTP port to test}
                            {--encryption= : Override encryption mode (ssl, tls, none)}
                            {--timeout=5 : Connection timeout in seconds}';

    protected $description = 'Perform deep end-to-end diagnostics on SMTP network, DNS, TLS, and cPanel firewall connectivity';

    public function handle(): int
    {
        $this->newLine();
        $this->info('========================================================================');
        $this->info('              MAMA BAZAR PRODUCTION SMTP CONNECTIVITY DIAGNOSTICS      ');
        $this->info('========================================================================');

        $settings = EmailSettingService::all();
        $driver = (string) EmailSettingService::get('mail_mailer', 'smtp');

        $port = (int) ($this->option('port') ?: ($settings['mail_port'] ?? config('mail.mailers.smtp.port', 465)));
        $rawEncryption = (string) ($this->option('encryption') ?: ($settings['mail_encryption'] ?? config('mail.mailers.smtp.encryption', 'ssl')));
        $encryption = EmailSettingService::normalizeEncryption($rawEncryption, $port);
        $host = (string) ($this->option('host') ?: ($settings['mail_host'] ?: config('mail.mailers.smtp.host', 'mail.mama-bazar.com')));
        $username = (string) ($settings['mail_username'] ?: config('mail.mailers.smtp.username', ''));
        $timeout = (float) $this->option('timeout');

        $hasPassword = EmailSettingService::passwordSource() !== 'none';
        $maskedUsername = $this->maskUsername($username);

        // 1. Current Configuration Overview
        $this->info("\n--- 1. ACTIVE CONFIGURATION OVERVIEW ---");
        $this->table(
            ['Parameter', 'Configured Value', 'Status / Verification'],
            [
                ['Mail Driver', $driver, in_array($driver, ['smtp', 'sendmail', 'log'], true) ? '<info>Valid</info>' : '<comment>Unknown</comment>'],
                ['SMTP Host', $host, ! empty($host) ? '<info>Configured</info>' : '<error>Missing</error>'],
                ['SMTP Port', (string) $port, in_array($port, [465, 587, 25, 2525], true) ? '<info>Standard</info>' : '<comment>Custom</comment>'],
                ['Encryption Mode', strtoupper($encryption), $this->evaluateEncryptionMode($port, $encryption)],
                ['SMTP Username', $maskedUsername ?: '(none)', ! empty($username) ? '<info>Set</info>' : '<comment>Empty</comment>'],
                ['SMTP Password', $hasPassword ? 'Present (Hidden)' : 'Missing', $hasPassword ? '<info>Configured</info>' : '<error>Missing</error>'],
                ['From Address', (string) ($settings['mail_from_address'] ?: 'Not set'), filter_var($settings['mail_from_address'], FILTER_VALIDATE_EMAIL) ? '<info>Valid</info>' : '<error>Invalid</error>'],
            ]
        );

        // 2. Local Environment & Extensions Check
        $this->info("\n--- 2. PHP ENVIRONMENT & SOCKET CAPABILITIES ---");
        $opensslOk = extension_loaded('openssl');
        $socketsOk = function_exists('stream_socket_client');
        $procOpenOk = function_exists('proc_open');

        $this->line(sprintf('  • OpenSSL Extension:       %s', $opensslOk ? '<info>✓ Available</info>' : '<error>✗ Missing</error>'));
        $this->line(sprintf('  • stream_socket_client():  %s', $socketsOk ? '<info>✓ Enabled</info>' : '<error>✗ Disabled in php.ini</error>'));
        $this->line(sprintf('  • proc_open() for sendmail:%s', $procOpenOk ? '<info>✓ Enabled</info>' : '<comment>! Disabled</comment>'));

        if (! $opensslOk || ! $socketsOk) {
            $this->error("\nCritical PHP extensions/functions are missing. Network SMTP connections cannot be established.");

            return Command::FAILURE;
        }

        // 3. DNS Resolution
        $this->info("\n--- 3. DNS RESOLUTION ---");
        $startTime = microtime(true);
        $resolvedIps = @gethostbynamel($host);
        $dnsLatency = (int) round((microtime(true) - $startTime) * 1000);

        if (! empty($resolvedIps)) {
            $this->line(sprintf('  • Host: %s resolves to: <info>%s</info> (%d ms)', $host, implode(', ', $resolvedIps), $dnsLatency));
        } else {
            $this->error(sprintf('  • Host: %s could NOT be resolved via DNS. Check server /etc/resolv.conf.', $host));
        }

        // 4. TCP Network Connectivity Probe
        $this->info("\n--- 4. TCP CONNECTIVITY PROBE ---");
        $probeTarget = ($encryption === 'ssl' ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}");
        $this->line(sprintf('  • Attempting connection to: <comment>%s</comment> (timeout: %ds)...', $probeTarget, $timeout));

        $t0 = microtime(true);
        $socket = @stream_socket_client($probeTarget, $errno, $errstr, $timeout);
        $connectLatency = (int) round((microtime(true) - $t0) * 1000);

        $tcpSuccess = false;
        $connectionRefused = false;

        if ($socket) {
            $tcpSuccess = true;
            $this->info(sprintf('  • Connection SUCCESS! TCP handshake established in %d ms.', $connectLatency));
            fclose($socket);
        } else {
            $this->error(sprintf('  • Connection FAILED: %s (errno: %d)', $errstr ?: 'Unknown error', $errno));

            if ($errno === 111 || stripos($errstr, 'Connection refused') !== false) {
                $connectionRefused = true;
            }
        }

        // 5. Deep Multi-Port & Firewall Probe (especially when connection is refused)
        $this->info("\n--- 5. OUTBOUND FIREWALL & PORT RESTRICTION AUDIT ---");
        $testTargets = [
            ['host' => $host, 'port' => 465, 'ssl' => true, 'label' => "Target Host ({$host}:465 SSL)"],
            ['host' => $host, 'port' => 587, 'ssl' => false, 'label' => "Target Host ({$host}:587 STARTTLS)"],
            ['host' => 'smtp.gmail.com', 'port' => 465, 'ssl' => true, 'label' => 'Google SMTP (smtp.gmail.com:465)'],
            ['host' => 'smtp.gmail.com', 'port' => 587, 'ssl' => false, 'label' => 'Google SMTP (smtp.gmail.com:587)'],
            ['host' => '1.1.1.1', 'port' => 443, 'ssl' => true, 'label' => 'Standard HTTPS Outbound (1.1.1.1:443)'],
        ];

        $auditRows = [];
        $smtpPortsBlocked = 0;
        $httpsWorks = false;

        foreach ($testTargets as $t) {
            $prefix = $t['ssl'] ? 'ssl://' : 'tcp://';
            $uri = "{$prefix}{$t['host']}:{$t['port']}";
            $start = microtime(true);
            $fp = @stream_socket_client($uri, $errCode, $errMsg, 3);
            $ms = (int) round((microtime(true) - $start) * 1000);

            if ($fp) {
                fclose($fp);
                $status = '<info>OPEN</info>';
                if ($t['port'] === 443) {
                    $httpsWorks = true;
                }
            } else {
                if ($errCode === 111 || stripos($errMsg, 'Connection refused') !== false) {
                    $status = '<error>REFUSED (TCP RST)</error>';
                    if (in_array($t['port'], [25, 465, 587], true)) {
                        $smtpPortsBlocked++;
                    }
                } elseif (stripos($errMsg, 'timed out') !== false) {
                    $status = '<comment>TIMED OUT (DROP)</comment>';
                    if (in_array($t['port'], [25, 465, 587], true)) {
                        $smtpPortsBlocked++;
                    }
                } else {
                    $status = "<error>FAILED ({$errCode})</error>";
                }
            }

            $auditRows[] = [$t['label'], "{$t['host']}:{$t['port']}", $status, $ms > 0 ? "{$ms} ms" : '—'];
        }

        $this->table(['Target Description', 'Endpoint', 'Result', 'Latency'], $auditRows);

        // 6. Local Server Fallbacks (cPanel Exim & Sendmail)
        $this->info("\n--- 6. LOCAL SERVER FALLBACKS (cPanel Native) ---");
        $sendmailPath = config('mail.mailers.sendmail.path') ?: '/usr/sbin/sendmail -bs';
        $sendmailBinary = explode(' ', trim($sendmailPath))[0];
        $sendmailExists = file_exists($sendmailBinary) && is_executable($sendmailBinary);

        $this->line(sprintf('  • System Sendmail binary (%s): %s', $sendmailBinary, $sendmailExists ? '<info>✓ FOUND & EXECUTABLE</info>' : '<comment>✗ Missing</comment>'));

        $localSocket25 = @stream_socket_client('tcp://127.0.0.1:25', $errCode25, $errMsg25, 2);
        if ($localSocket25) {
            fclose($localSocket25);
            $this->line('  • Local Exim on 127.0.0.1:25: <info>✓ LISTENING & ACCESSIBLE</info>');
        } else {
            $this->line('  • Local Exim on 127.0.0.1:25: <comment>! Inactive or firewalled</comment>');
        }

        // 7. Full SMTP Auth Probe (if TCP passed)
        if ($tcpSuccess && $driver === 'smtp') {
            $this->info("\n--- 7. LIVE SMTP HANDSHAKE & AUTHENTICATION TEST ---");
            $probeResult = EmailSettingService::testConnection();
            if ($probeResult['success']) {
                $this->info('  • '.$probeResult['message']);
            } else {
                $this->error('  • '.$probeResult['message']);
            }
        }

        // 8. ROOT CAUSE SUMMARY & ACTIONABLE RECOMMENDATIONS
        $this->info("\n========================================================================");
        $this->info('                     ROOT CAUSE ANALYSIS & SOLUTIONS                    ');
        $this->info('========================================================================');

        if ($smtpPortsBlocked >= 3 && $httpsWorks) {
            $this->error("\n[CRITICAL ROOT CAUSE DETECTED]");
            $this->line('Standard HTTPS (port 443) works, but outbound connections on SMTP ports 465 & 587 are actively REJECTED (Connection Refused).');
            $this->line("This confirms that your cPanel server's local firewall (cPanel WHM 'SMTP Restrictions' or CSF 'SMTP_BLOCK = 1') is blocking non-root user network connections to external mail servers.\n");

            $this->info('[RECOMMENDED PERMANENT RESOLUTIONS]');
            $this->line('Option 1 (WHM / Root Server Administrator):');
            $this->line('  1. Log in to cPanel WHM as root.');
            $this->line('  2. Navigate to: Security Center › SMTP Restrictions.');
            $this->line("  3. Click 'Disable' (or in CSF edit /etc/csf/csf.conf and set SMTP_BLOCK = 0, or add your cPanel user to SMTP_ALLOWUSER).\n");

            $this->line('Option 2 (Zero WHM changes needed — Use Sendmail Driver):');
            $this->line('  1. Go to Mama Bazar Admin Panel › Email Settings.');
            $this->line("  2. Change 'Mail Driver' from 'SMTP' to 'Sendmail'.");
            $this->line("  3. Save settings. Sendmail runs locally through cPanel Exim without opening blocked network sockets!\n");

            $this->line('Option 3 (If using local domain mail):');
            $this->line("  Set SMTP Host to '127.0.0.1' or 'localhost' and Port to '25' (no SSL/TLS) so it communicates via the allowed loopback interface.");
        } elseif ($tcpSuccess) {
            $this->info("\n[STATUS: TCP CONNECTIVITY HEALTHY]");
            $this->line("Connection to {$host}:{$port} succeeded without network-level blocks.");
            if (! $hasPassword) {
                $this->warn('Note: SMTP password is not yet configured. Please enter your password in Email Settings.');
            }
        } else {
            $this->error("\n[STATUS: UNABLE TO CONNECT TO {$host}:{$port}]");
            $this->line("Error: {$errstr} ({$errno}). Please verify that {$host} is online and accepting connections on port {$port}.");
        }

        $this->newLine();

        return Command::SUCCESS;
    }

    protected function maskUsername(string $username): string
    {
        if (empty($username)) {
            return '';
        }

        if (str_contains($username, '@')) {
            [$name, $domain] = explode('@', $username, 2);
            $visible = strlen($name) > 2 ? substr($name, 0, 2).'***'.substr($name, -1) : $name.'***';

            return "{$visible}@{$domain}";
        }

        return strlen($username) > 2 ? substr($username, 0, 2).'***' : '***';
    }

    protected function evaluateEncryptionMode(int $port, string $encryption): string
    {
        if ($port === 465 && $encryption === 'ssl') {
            return '<info>Optimal (Implicit SSL/SMTPS)</info>';
        }

        if ($port === 587 && $encryption === 'tls') {
            return '<info>Optimal (STARTTLS)</info>';
        }

        if ($port === 465 && $encryption !== 'ssl') {
            return '<comment>Warning: Port 465 requires SSL</comment>';
        }

        if ($port === 587 && $encryption !== 'tls') {
            return '<comment>Warning: Port 587 requires TLS</comment>';
        }

        return '<info>Configured</info>';
    }
}
