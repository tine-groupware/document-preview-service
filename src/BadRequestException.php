<?php declare(strict_types=1);

namespace DocumentService;

class BadRequestException extends DocumentPreviewException
{
    public function __construct(string $message, int $code = 0, int $statusCode = 400)
    {
        parent::__construct($message, $code, $statusCode);
    }
}
