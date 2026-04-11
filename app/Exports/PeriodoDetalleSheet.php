<?php

namespace App\Exports;

use App\Models\PeriodoReporte;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PeriodoDetalleSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    private const VERDE   = '009887';
    private const MAGENTA = 'C90166';
    private const ROJO    = 'AE1922';
    private const BLANCO  = 'FFFFFF';
    private const GRIS    = 'F3F4F6';

    public function __construct(
        private readonly PeriodoReporte $periodo,
        private readonly Collection $lineas,
    ) {}

    public function title(): string
    {
        return 'Detalle';
    }

    public function array(): array
    {
        $rows = [];

        // Encabezado
        $rows[] = [
            'Sistema de Seguimiento PI-PEA', $this->periodo->nombre, '', '', '', '', '', '', '', '', '',
        ];
        $rows[] = [
            'Eje estratégico', 'Prioridad / Línea', 'Organismo',
            'Responsable', 'Estatus', '% Avance', 'Valor avance',
            'Meta', 'Unidad medida', 'Fecha reporte', 'Medio verificación',
            'Documento / URL', 'Comentario',
        ];

        foreach ($this->lineas as $l) {
            $rows[] = [
                $l['eje'],
                $l['prioridad'],
                $l['organismo'],
                $l['usuario'],
                $l['estatus_display'],
                $l['porcentaje'] !== null ? $l['porcentaje'] / 100 : null,
                $l['valor_avance'],
                $l['meta'],
                $l['unidad_medida'],
                $l['fecha_avance'],
                $l['medio_verificacion'],
                $l['documento'] ?? $l['url'] ?? '',
                $l['comentario'],
            ];
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30, 'B' => 40, 'C' => 28, 'D' => 22,
            'E' => 14, 'F' => 12, 'G' => 14, 'H' => 10,
            'I' => 14, 'J' => 14, 'K' => 28, 'L' => 28, 'M' => 40,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Merge título y aplicar estilos
        $sheet->mergeCells('A1:M1');

        // Wrap text en columna de comentario y prioridad
        $sheet->getStyle('B3:B' . ($this->lineas->count() + 2))
            ->getAlignment()->setWrapText(true);
        $sheet->getStyle('M3:M' . ($this->lineas->count() + 2))
            ->getAlignment()->setWrapText(true);

        // Formato porcentaje
        $lastRow = $this->lineas->count() + 2;
        $sheet->getStyle("F3:F{$lastRow}")
            ->getNumberFormat()
            ->setFormatCode('0.00%');

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FF' . self::VERDE]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFf0fdf4']],
            ],
            2 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FF' . self::BLANCO]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF' . self::VERDE]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            ],
        ];
    }
}
