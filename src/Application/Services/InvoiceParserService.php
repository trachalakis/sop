<?php

declare(strict_types=1);

namespace Application\Services;

use RuntimeException;

/**
 * Sends an invoice image to the Claude vision API and returns structured data.
 *
 * Returns an array with keys:
 *   supplier_name    string
 *   supplier_details array{afm:?string, doy:?string, address:?string, email:?string, website:?string}
 *   invoice_number   string|null
 *   date             string|null  (YYYY-MM-DD)
 *   entries          array of {description:string, quantity:float, unit_price:float, extras:array}
 */
final class InvoiceParserService
{
    private const MODEL = 'claude-sonnet-4-6';
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    public function __construct(private string $apiKey) {}

    /**
     * @param string $imageData  Raw binary image bytes
     * @param string $mediaType  E.g. 'image/jpeg', 'image/png'
     * @return array             Parsed invoice data
     * @throws RuntimeException  On API or parse error
     */
    public function parse(string $imageData, string $mediaType): array
    {
        $base64 = base64_encode($imageData);

        $payload = [
            'model' => self::MODEL,
            'max_tokens' => 2048,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $mediaType,
                                'data' => $base64,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'Extract all invoice data from this image and return ONLY a valid JSON object with this exact structure (no markdown, no extra text):
{
  "supplier_name": "string as it appears on the invoice",
  "supplier_details": {
    "afm": "VAT number / ΑΦΜ or null",
    "doy": "ΔΟΥ or null",
    "address": "full address or null",
    "email": "email or null",
    "website": "website or null"
  },
  "invoice_number": "string or null",
  "date": "YYYY-MM-DD or null",
  "entries": [
    {
      "description": "exact description as on invoice",
      "quantity": 1.0,
      "unit_price": 0.0,
      "extras": {
        "unit": "string or null",
        "vat_rate": 0,
        "line_total": 0.0
      }
    }
  ]
}',
                        ],
                    ],
                ],
            ],
        ];

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60,
        ]);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException('Claude API curl error: ' . $curlError);
        }
        if ($httpCode !== 200) {
            throw new RuntimeException('Claude API returned HTTP ' . $httpCode . ': ' . $responseBody);
        }

        $response = json_decode($responseBody, true);
        if (!is_array($response)) {
            throw new RuntimeException('Claude API response is not valid JSON: ' . $responseBody);
        }
        $text = $response['content'][0]['text'] ?? '';

        // Strip markdown code fences if present
        $text = preg_replace('/^```json\s*|\s*```$/s', '', trim($text));

        $data = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Could not parse Claude response as JSON: ' . $text);
        }

        return $data;
    }
}
