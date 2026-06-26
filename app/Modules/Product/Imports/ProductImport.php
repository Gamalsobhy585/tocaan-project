<?php

namespace App\Modules\Product\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpsertColumns;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    WithBatchInserts,
    WithChunkReading,
    WithUpserts,
    WithUpsertColumns,
    SkipsOnFailure,
    SkipsEmptyRows
{
    use Importable;
    use SkipsFailures;

    private int $processedRows = 0;

    public function model(array $row): Product
    {
        $this->processedRows++;

        return new Product([
            'name_ar' => trim((string) $row['name_ar']),
            'name_en' => trim((string) $row['name_en']),

            'code' => strtoupper(
                trim((string) $row['code'])
            ),

            'quantity_in_stock' => (int) $row['quantity_in_stock'],
            'unit_price' => (float) $row['unit_price'],
        ]);
    }

    public function rules(): array
    {
        return [
            'name_ar' => [
                'required',
                'string',
                'max:150',
            ],

            'name_en' => [
                'required',
                'string',
                'max:150',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
            ],

            'quantity_in_stock' => [
                'required',
                'integer',
                'min:0',
            ],

            'unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }

    /**
     * The code identifies an existing product during import.
     */
    public function uniqueBy(): string
    {
        return 'code';
    }

    /**
     * These fields will be updated when the code already exists.
     */
    public function upsertColumns(): array
    {
        return [
            'name_ar',
            'name_en',
            'quantity_in_stock',
            'unit_price',
        ];
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function processedRows(): int
    {
        return $this->processedRows;
    }
}