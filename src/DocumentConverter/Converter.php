<?php declare(strict_types=1);
namespace DocumentService\DocumentConverter;


use DocumentService\DocumentConverter\FileSystem\File;

interface Converter
{
    public function from(): array;

    public function to(): array;

    public function routeTo(): array;

    public function convert(File $file, Request $request): array;
}