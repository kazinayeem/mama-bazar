<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Symfony\Component\Mime\Email;

/**
 * Carrier for an already-rendered email (subject, HTML, plain text,
 * attachments). Rendering happens in EmailTemplateService.
 */
class RenderedEmail extends Mailable
{
    /**
     * @param  array<int, array{data: string, name: string, mime?: string}>  $fileAttachments
     * @param  array<string, string>  $extraHeaders
     */
    public function __construct(
        public string $renderedSubject,
        public string $renderedHtml,
        public ?string $renderedText = null,
        public array $fileAttachments = [],
        public array $extraHeaders = [],
        public ?string $fromAddress = null,
        public ?string $fromName = null,
        public ?string $replyToAddress = null,
        public string $emailType = 'transactional',
    ) {}

    public function build(): static
    {
        $this->subject($this->renderedSubject)->html($this->renderedHtml);

        if ($this->fromAddress) {
            $this->from($this->fromAddress, $this->fromName);
        }
        if ($this->replyToAddress) {
            $this->replyTo($this->replyToAddress);
        }

        foreach ($this->fileAttachments as $attachment) {
            $this->attachData($attachment['data'], $attachment['name'], ['mime' => $attachment['mime'] ?? 'application/pdf']);
        }

        $text = $this->renderedText;
        $headers = $this->extraHeaders;

        return $this->withSymfonyMessage(function (Email $message) use ($text, $headers) {
            if (! empty($text)) {
                $message->text($text, 'utf-8');
            }
            foreach ($headers as $name => $value) {
                $message->getHeaders()->addTextHeader($name, $value);
            }
        });
    }

    /**
     * @return array<int, string>
     */
    public function attachmentNames(): array
    {
        return array_column($this->fileAttachments, 'name');
    }
}
