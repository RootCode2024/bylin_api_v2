<?php

declare(strict_types=1);

namespace Modules\Customer\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomersExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithTitle
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * Query to export
     */
    public function query(): Builder
    {
        return $this->query->with(['addresses']);
    }

    /**
     * Column headings
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nom complet',
            'Prénom',
            'Nom',
            'Email',
            'Téléphone',
            'Statut',
            'Date de naissance',
            'Genre',
            'Email vérifié',
            'Fournisseur OAuth',
            'Date de création',
            'Dernière modification',
        ];
    }

    /**
     * Map data for each row
     */
    public function map($customer): array
    {
        return [
            $customer->id,
            $customer->full_name,
            $customer->first_name,
            $customer->last_name,
            $customer->email,
            $customer->phone ?? 'N/A',
            ucfirst($customer->status),
            $customer->date_of_birth?->format('d/m/Y') ?? 'N/A',
            $customer->gender ? ucfirst($customer->gender) : 'N/A',
            $customer->email_verified_at ? 'OUi' : 'Non',
            $customer->oauth_provider ? ucfirst($customer->oauth_provider) : 'N/A',
            $customer->created_at->format('d/m/Y H:i:s'),
            $customer->updated_at->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Style for header row
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4A5568'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 36, // ID (UUID)
            'B' => 25, // Full Name
            'C' => 20, // First Name
            'D' => 20, // Last Name
            'E' => 30, // Email
            'F' => 15, // Phone
            'G' => 12, // Status
            'H' => 15, // Date of Birth
            'I' => 10, // Gender
            'J' => 15, // Email Verified
            'K' => 15, // OAuth Provider
            'L' => 20, // Created At
            'M' => 20, // Updated At
        ];
    }

    /**
     * Sheet title
     */
    public function title(): string
    {
        return 'CLients';
    }
}
