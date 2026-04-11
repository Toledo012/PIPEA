<?php

namespace App\Exports;

use App\Models\PeriodoReporte;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PeriodoExcelExport implements WithMultipleSheets
{
    public function __construct(
        public readonly PeriodoReporte $periodo,
        public readonly Collection $lineas,
        public readonly array $stats,
    ) {}

    public function sheets(): array
    {
        return [
            new PeriodoResumenSheet($this->periodo, $this->stats),
            new PeriodoDetalleSheet($this->periodo, $this->lineas),
        ];
    }
}
