<?php

namespace App\Email;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class ContactoEmailWithAttachments extends Email
{
    private array $attachmentsData = [];

    public function addAttachmentData(string $content, string $filename, string $mimeType): self
    {
        $this->attachmentsData[] = [
            'content' => base64_encode($content),
            'filename' => $filename,
            'mimeType' => $mimeType
        ];

        return $this;
    }

    public function getAttachmentsData(): array
    {
        return $this->attachmentsData;
    }

    public function processAttachments(): self
    {
        foreach ($this->attachmentsData as $attachmentData) {
            $decodedContent = base64_decode($attachmentData['content']);
            $this->addPart(new DataPart($decodedContent, $attachmentData['filename'], $attachmentData['mimeType']));
        }

        // Limpiar los datos de adjuntos después de procesarlos
        $this->attachmentsData = [];

        return $this;
    }

    public function __serialize(): array
    {
        $data = parent::__serialize();
        $data['attachmentsData'] = $this->attachmentsData;
        return $data;
    }

    public function __unserialize(array $data): void
    {
        $this->attachmentsData = $data['attachmentsData'] ?? [];
        unset($data['attachmentsData']);
        parent::__unserialize($data);
    }
}
