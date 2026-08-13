<?php

declare(strict_types=1);

namespace App\Services\Cfdi;

use DOMDocument;

class CfdiXsdValidator
{
    public function validate(DOMDocument $document): array
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $valid = $document->schemaValidate(Cfdi40Schema::cfdi40Schema());
            $errors = array_map(
                fn (\LibXMLError $error): array => [
                    'line' => $error->line,
                    'column' => $error->column,
                    'message' => preg_replace('/\s+/', ' ', trim($error->message)),
                ],
                libxml_get_errors(),
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return ['valid' => $valid, 'errors' => $errors];
    }
}
