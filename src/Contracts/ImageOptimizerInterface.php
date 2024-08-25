<?php

namespace Ngfw\WebpConverter\Contracts;

interface ImageOptimizerInterface
{
    public function load(string $file): self;

    public function optimize(): self;

    public function convert(?string $outputFile = null): string;

    public function setQuality(int $quality): self;

    public function resize(?int $width, ?int $height): self;

    public function isExternalUrl(string $url): bool;
    
    public function downloadExternalImage(string $url): string;
}
