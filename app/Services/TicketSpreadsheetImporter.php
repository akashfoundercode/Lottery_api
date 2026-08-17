<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use SimpleXMLElement;
use ZipArchive;

class TicketSpreadsheetImporter
{
    /**
     * @return array<int, string>
     */
    public function ticketNumbersFromFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        $rows = match ($extension) {
            'csv', 'txt' => $this->rowsFromCsv($file),
            'xlsx' => $this->rowsFromXlsx($file),
            'xls' => throw ValidationException::withMessages([
                'file' => ['Legacy XLS files are not readable by this installation. Please upload CSV or XLSX.'],
            ]),
            default => throw ValidationException::withMessages([
                'file' => ['Ticket spreadsheet must be a CSV, XLSX, or XLS file.'],
            ]),
        };

        return $this->validateAndExtractTicketNumbers($rows);
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function rowsFromCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => ['Ticket spreadsheet could not be read.'],
            ]);
        }

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_map(static fn ($value) => (string) $value, $row);
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function rowsFromXlsx(UploadedFile $file): array
    {
        $zip = new ZipArchive;

        if ($zip->open($file->getRealPath()) !== true) {
            throw ValidationException::withMessages([
                'file' => ['Ticket spreadsheet could not be opened.'],
            ]);
        }

        try {
            $sharedStrings = $this->sharedStrings($zip);
            $sheetPath = $this->firstWorksheetPath($zip);
            $sheetXml = $zip->getFromName($sheetPath);

            if ($sheetXml === false) {
                throw ValidationException::withMessages([
                    'file' => ['Ticket spreadsheet worksheet could not be read.'],
                ]);
            }

            $sheet = simplexml_load_string($sheetXml);

            if (! $sheet instanceof SimpleXMLElement) {
                throw ValidationException::withMessages([
                    'file' => ['Ticket spreadsheet worksheet is invalid.'],
                ]);
            }

            $rows = [];

            foreach ($sheet->sheetData->row as $row) {
                $values = [];

                foreach ($row->c as $cell) {
                    $reference = (string) $cell['r'];
                    $columnIndex = $this->columnIndexFromCellReference($reference);
                    $values[$columnIndex] = $this->cellValue($cell, $sharedStrings);
                }

                if ($values === []) {
                    $rows[] = [];

                    continue;
                }

                ksort($values);
                $maxIndex = max(array_keys($values));
                $normalized = [];

                for ($index = 0; $index <= $maxIndex; $index++) {
                    $normalized[] = $values[$index] ?? '';
                }

                $rows[] = $normalized;
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<int, string>
     */
    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $sharedStrings = [];
        $document = simplexml_load_string($xml);

        if (! $document instanceof SimpleXMLElement) {
            return [];
        }

        foreach ($document->si as $item) {
            if (isset($item->t)) {
                $sharedStrings[] = (string) $item->t;

                continue;
            }

            $text = '';

            foreach ($item->r as $run) {
                $text .= (string) $run->t;
            }

            $sharedStrings[] = $text;
        }

        return $sharedStrings;
    }

    private function firstWorksheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = simplexml_load_string($workbookXml);
        $relationships = simplexml_load_string($relsXml);

        if (! $workbook instanceof SimpleXMLElement || ! $relationships instanceof SimpleXMLElement) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook->registerXPathNamespace('sheet', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $sheets = $workbook->xpath('//sheet:sheet');

        if ($sheets === false || $sheets === []) {
            return 'xl/worksheets/sheet1.xml';
        }

        $relationshipId = (string) $sheets[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];

        $relationshipElements = $relationships
            ->children('http://schemas.openxmlformats.org/package/2006/relationships')
            ->Relationship;

        foreach ($relationshipElements as $relationship) {
            if ((string) $relationship['Id'] !== $relationshipId) {
                continue;
            }

            $target = (string) $relationship['Target'];

            return str_starts_with($target, 'xl/')
                ? $target
                : 'xl/'.ltrim($target, '/');
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];

        if ($type === 's') {
            return $sharedStrings[(int) $cell->v] ?? '';
        }

        if ($type === 'inlineStr') {
            return (string) ($cell->is->t ?? '');
        }

        if ($type === 'str') {
            return (string) ($cell->v ?? '');
        }

        return (string) ($cell->v ?? '');
    }

    private function columnIndexFromCellReference(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $matches);

        $letters = strtoupper($matches[0] ?? 'A');
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, string>
     */
    private function validateAndExtractTicketNumbers(array $rows): array
    {
        $rows = array_values(array_filter(
            $rows,
            static fn (array $row) => collect($row)->contains(static fn (string $value) => trim($value) !== '')
        ));

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => ['Ticket spreadsheet is empty.'],
            ]);
        }

        $headers = array_map(
            static fn (string $header) => trim(str_replace("\xEF\xBB\xBF", '', $header)),
            $rows[0]
        );

        $ticketColumn = array_search('ticket_number', $headers, true);

        if ($ticketColumn === false) {
            throw ValidationException::withMessages([
                'file' => ['Ticket spreadsheet must contain a ticket_number header.'],
            ]);
        }

        $ticketNumbers = [];
        $errors = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            $spreadsheetRow = $index + 2;
            $ticketNumber = (string) ($row[$ticketColumn] ?? '');

            if (trim($ticketNumber) === '') {
                $errors["tickets.row_{$spreadsheetRow}"][] = 'Ticket number is required.';

                continue;
            }

            if ($ticketNumber !== trim($ticketNumber)) {
                $errors["tickets.row_{$spreadsheetRow}"][] = 'Ticket number may not contain leading or trailing spaces.';

                continue;
            }

            $ticketNumbers[] = $ticketNumber;
        }

        if ($ticketNumbers === [] && $errors === []) {
            $errors['file'][] = 'Ticket spreadsheet does not contain any ticket rows.';
        }

        $duplicates = collect($ticketNumbers)
            ->duplicates()
            ->values()
            ->unique()
            ->values()
            ->all();

        if ($duplicates !== []) {
            $errors['ticket_number'][] = 'Duplicate ticket numbers found in spreadsheet: '.implode(', ', $duplicates).'.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        Validator::make(
            ['ticket_numbers' => $ticketNumbers],
            [
                'ticket_numbers' => ['required', 'array', 'min:1'],
                'ticket_numbers.*' => ['required', 'string', 'max:50', 'distinct', 'unique:tickets,ticket_number'],
            ],
            [
                'ticket_numbers.*.unique' => 'Ticket number already exists.',
            ]
        )->validate();

        return $ticketNumbers;
    }
}
