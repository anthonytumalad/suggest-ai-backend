<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FeedbackExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
    /**
     * Create a new class instance.
     */

    protected array $data;
    protected string $formTitle;

    public function __construct(array $data, string $formTitle)
    {
        $this->data = $data;
        $this->formTitle = $formTitle;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return array_keys($this->data[0] ?? []);
    }

    public function title(): string
    {
        return 'Feedback Responses';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
