<?php

namespace App\Exports;

use App\Models\PurchaseLetter;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DashboardExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = PurchaseLetter::query();

        if (!empty($this->filters['cluster'])) {
            $query->where('Cluster', 'like', '%' . $this->filters['cluster'] . '%');
        }

        if (!empty($this->filters['customername'])) {
            $query->where('CustomerName', 'like', '%' . $this->filters['customername'] . '%');
        }

        if (!empty($this->filters['startdate'])) {
            $query->whereDate('PurchaseDate', '>=', $this->filters['startdate']);
        }

        if (!empty($this->filters['enddate'])) {
            $query->whereDate('PurchaseDate', '<=', $this->filters['enddate']);
        }

        return $query->get([
            'CustomerName',
            'Cluster',
            'Unit',
            'PurchaseDate',
            'LunasDate',
            'harga_netto',
        ]);
    }

    public function headings(): array
    {
        return [
            'Customer Name',
            'Cluster',
            'Unit',
            'Purchase Date',
            'Lunas Date',
            'Harga Netto',
        ];
    }
}
