<?php declare(strict_types=1);

namespace DocumentService;

class ExtensionDoseNotMatchMineTypeException extends DocumentPreviewException
{
    public function __construct(string $ext, string $mime, int $code = 0)
    {
        parent::__construct("Extension $ext dose not match mime-type $mime", $code, 422);
    }
}
