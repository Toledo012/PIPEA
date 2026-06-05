<?php

namespace App\Exports;

use App\Models\PeriodoReporte;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PeriodoResumenSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    // Colores institucionales (sin #)
    private const VERDE   = '009887';
    private const MAGENTA = 'C90166';
    private const ROJO    = 'AE1922';
    private const CREMA   = 'D3C2B4';
    private const BLANCO  = 'FFFFFF';
    private const GRIS    = 'F3F4F6';

    public function __construct(
        private readonly PeriodoReporte $periodo,
        private readonly array $stats,
    ) {}

    public function title(): string
    {
        return 'Resumen';
    }

    public function array(): array
    {
        $rows = [];

        // Título principal
        $rows[] = ['Sistema de Seguimiento PI-PEA — SESAECH', '', '', ''];
        $rows[] = [$this->periodo->nombre, '', '', ''];
        $rows[] = [
            'Período: ' . $this->periodo->fecha_inicio->format('d/m/Y')
            . ' al ' . $this->periodo->fecha_fin->format('d/m/Y'),
            '',
            'Límite de reporte: ' . $this->periodo->fecha_limite_reporte->format('d/m/Y'),
            '',
        ];
        $rows[] = ['Generado: ' . now()->format('d/m/Y H:i'), '', '', ''];
        $rows[] = ['', '', '', ''];

        // Estadísticas generales
        $rows[] = ['RESUMEN GENERAL', '', '', ''];
        $rows[] = ['Indicador', 'Valor', 'Porcentaje', ''];
        $rows[] = ['Total líneas de acción', $this->stats['total'], '100%', ''];
        $rows[] = ['Con reporte', $this->stats['reportaron'], $this->stats['porcentaje'] . '%', ''];
        $rows[] = ['Sin reporte', $this->stats['sin_reporte'],
            $this->stats['total'] > 0
                ? round($this->stats['sin_reporte'] / $this->stats['total'] * 100, 1) . '%'
                : '0%',
            ''];
        $rows[] = ['Bloqueados', $this->stats['bloqueados'],
            $this->stats['total'] > 0
                ? round($this->stats['bloqueados'] / $this->stats['total'] * 100, 1) . '%'
                : '0%',
            ''];
        $rows[] = ['', '', '', ''];

        // Avance por eje
        $rows[] = ['AVANCE POR EJE ESTRATÉGICO', '', '', ''];
        $rows[] = ['Eje', 'Total líneas', 'Con reporte', '% Cumplimiento'];
        foreach ($this->stats['por_eje'] as $eje => $d) {
            $rows[] = [$eje, $d['total'], $d['reportaron'], $d['pct'] . '%'];
        }
        $rows[] = ['', '', '', ''];

        // Cumplimiento por organismo
        $rows[] = ['CUMPLIMIENTO POR ORGANISMO', '', '', ''];
        $rows[] = ['Organismo', 'Total líneas', 'Con reporte', '% Cumplimiento'];
        foreach ($this->stats['por_organismo'] as $org => $d) {
            $rows[] = [$org, $d['total'], $d['reportaron'], $d['pct'] . '%'];
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 52,
            'B' => 16,
            'C' => 16,
            'D' => 16,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [];

        // Fila 1: título sistema
        $sheet->mergeCells('A1:D1');
        $styles[1] = [
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF' . self::VERDE]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ];

        // Fila 2: nombre del período
        $sheet->mergeCells('A2:D2');
        $styles[2] = [
            'font'      => ['bold' => true, 'size' => 15],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ];

        // Fila 3-4: fechas
        $sheet->mergeCells('C3:D3');
        $styles[3] = ['font' => ['color' => ['argb' => 'FF555555']]];
        $styles[4] = ['font' => ['color' => ['argb' => 'FF777777'], 'italic' => true]];

        // Fila 6: sección resumen
        $sheet->mergeCells('A6:D6');
        $styles[6] = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FF' . self::BLANCO]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF' . self::VERDE]],
        ];

        // Fila 7: cabecera resumen
        $styles[7] = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FF' . self::BLANCO]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF' . self::ROJO]],
        ];

        // Color filas de datos (8-11)
        foreach ([8, 9, 10, 11] as $row) {
            $styles[$row] = [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF' . self::GRIS]],
            ];
        }
        // Resaltar fila "Con reporte" en verde suave
        $styles[9] = [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFd1fae5']],
        ];

        // Detectar filas de cabeceras de sección dinámicamente
        $rowOffset = 13; // después de las 11 filas fijas + 1 vacía
        // Cabecera por eje
        $sheet->mergeCells("A{$rowOffset}:D{$rowOffset}");
        $styles[$rowOffset] = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FF' . self::BLANCO]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF' . self::VERDE]],
        ];
        $cabEje = $rowOffset + 1;
        $styles[$cabEje] = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FF' . self::BLANCO]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF' . self::MAGENTA]],
        ];

        return $styles;
    }
}
