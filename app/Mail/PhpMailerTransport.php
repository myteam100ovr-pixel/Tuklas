<?php

namespace App\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class PhpMailerTransport extends AbstractTransport
{
    public function __construct(
        private readonly array $config,
        private readonly string $fromAddress,
        private readonly string $fromName,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $mailer = new PHPMailer(true);

        $mailer->isSMTP();
        $mailer->Host = $this->config['host'];
        $mailer->Port = (int) $this->config['port'];
        $mailer->SMTPAuth = filled($this->config['username'] ?? null);
        $mailer->Username = $this->config['username'] ?? '';
        $mailer->Password = $this->config['password'] ?? '';
        $mailer->Timeout = (int) ($this->config['timeout'] ?? 15);
        $mailer->CharSet = PHPMailer::CHARSET_UTF8;
        $mailer->SMTPOptions = [
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ];

        if (($this->config['scheme'] ?? 'tls') === 'ssl') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif (($this->config['scheme'] ?? 'tls') === 'tls') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $from = $email->getFrom()[0] ?? new Address($this->fromAddress, $this->fromName);
        $mailer->setFrom($from->getAddress(), $from->getName());

        foreach ($email->getTo() as $recipient) {
            $mailer->addAddress($recipient->getAddress(), $recipient->getName());
        }

        foreach ($email->getCc() as $recipient) {
            $mailer->addCC($recipient->getAddress(), $recipient->getName());
        }

        foreach ($email->getBcc() as $recipient) {
            $mailer->addBCC($recipient->getAddress(), $recipient->getName());
        }

        foreach ($email->getReplyTo() as $replyTo) {
            $mailer->addReplyTo($replyTo->getAddress(), $replyTo->getName());
        }

        foreach ($email->getAttachments() as $attachment) {
            $mailer->addStringAttachment(
                $attachment->getBody(),
                $attachment->getPreparedHeaders()->getHeaderParameter('content-disposition', 'filename') ?? 'attachment',
                'base64',
                $attachment->getMediaType().'/'.$attachment->getMediaSubtype(),
            );
        }

        $mailer->Subject = (string) $email->getSubject();
        $mailer->Body = $email->getHtmlBody() ?? $email->getTextBody() ?? '';
        $mailer->AltBody = $email->getTextBody() ?? '';

        foreach ($email->getHeaders()->all() as $header) {
            if ($header->getName() !== 'From' && $header->getName() !== 'To' && $header->getName() !== 'Subject') {
                $mailer->addCustomHeader($header->getName(), $header->getBodyAsString());
            }
        }

        $mailer->send();
    }

    public function __toString(): string
    {
        return 'phpmailer';
    }
}
