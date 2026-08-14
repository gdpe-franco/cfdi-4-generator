<?php

declare(strict_types=1);

namespace App\Services\Cfdi\V40;

use CfdiUtils\CfdiValidator40;
use CfdiUtils\XmlResolver\XmlResolver;
use DOMDocument;
use Eclipxe\XmlResourceRetriever\Downloader\DownloaderInterface;
use RuntimeException;

class AdvisoryValidator
{
    public function validate(DOMDocument $document): array
    {
        $xml = $document->saveXML();
        if ($xml === false) {
            throw new RuntimeException('Unable to serialize XML for advisory validation.');
        }

        $resolver = new XmlResolver(
            Schema::xsdRoot(),
            new class implements DownloaderInterface
            {
                public function downloadTo(string $source, string $destination)
                {
                    throw new RuntimeException('Network access is disabled for CfdiUtils resources.');
                }
            },
        );
        $asserts = (new CfdiValidator40($resolver))->validateXml($xml);
        $findings = [];
        $skipped = [];

        foreach ($asserts as $assert) {
            $finding = [
                'code' => $assert->getCode(),
                'status' => (string) $assert->getStatus(),
                'title' => $assert->getTitle(),
                'explanation' => $assert->getExplanation(),
            ];

            if ($this->isOutOfScope($finding['code'])) {
                $skipped[] = $finding;
            } elseif (! $assert->getStatus()->isOk()) {
                $findings[] = $finding;
            }
        }

        return ['findings' => $findings, 'skipped' => $skipped];
    }

    private function isOutOfScope(string $code): bool
    {
        return str_starts_with($code, 'SELLO') || str_starts_with($code, 'TFD');
    }
}
