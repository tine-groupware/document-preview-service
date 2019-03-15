<?php declare(strict_types=1);
namespace DocumentService\DocumentConverter;


interface Finalizer
{
    public function format(): array;

    public function convert(array $files, Request $request): array;
}