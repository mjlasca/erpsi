<?php

namespace App\Modules\Catalog\Support;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class CsvReader
{
    /**
     * @return array<int, array<string, string>>
     */
    public function read(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw new RuntimeException('No se pudo leer el archivo CSV.');
        }

        try {
            $firstLine = fgets($handle);

            if ($firstLine === false) {
                throw new RuntimeException('El archivo CSV está vacío.');
            }

            $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine) ?? $firstLine;
            $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

            rewind($handle);
            $headers = fgetcsv($handle, 0, $delimiter);

            if ($headers === false) {
                throw new RuntimeException('No se pudieron leer los encabezados del CSV.');
            }

            $headers = array_map(
                fn ($header): string => mb_strtolower(trim((string) $header)),
                $headers,
            );

            if (in_array('', $headers, true) || count($headers) !== count(array_unique($headers))) {
                throw new RuntimeException('Los encabezados del CSV están vacíos o repetidos.');
            }

            $rows = [];

            while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($values === [null] || array_filter($values, fn ($value): bool => trim((string) $value) !== '') === []) {
                    continue;
                }

                $row = [];

                foreach ($headers as $index => $header) {
                    $row[$header] = trim((string) ($values[$index] ?? ''));
                }

                $rows[] = $row;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }
}
