<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exporta "Mis avances" del usuario a Excel.
 * Incluye encabezado institucional + tabla + bloque de KPIs.
 */
class MisAvancesExport implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    private const VERDE   = '009887';
    private const MAGENTA = 'C90166';
    private const GRIS    = 'F3F4F6';
    private const BLANCO  = 'FFFFFF';

    public function __construct(
        private readonly Collection $avances,
        private readonly array $kpis,
        private readonly string $organismoNombre,
        private readonly string $usuarioNombre,
        private readonly ?string $filtrosResumen = null,
    ) {}

    public function title(): string
    {
        return 'Mis avances';
    }

    public function array(): array
    {
        $rows = [];

        // ── Encabezado ────────────────────────────────────────────────────
        $rows[] = ['Sistema PI-PEA — SESAECH', '', '', '', '', '', '', '', ''];
        $rows[] = ['Reporte: Mis avances registrados', '', '', '', '', '', '', '', ''];
        $rows[] = ['Organismo: ' . $this->organismoNombre, '', '', '', '', '', '', '', ''];
        $rows[] = ['Usuario: ' . $this->usuarioNombre, '', '', '', '', '', '', '', ''];
        $rows[] = ['Generado: ' . now()->format('d/m/Y H:i'), '', '', '', '', '', '', '', ''];
        if ($this->filtrosResumen) {
            $rows[] = ['Filtros aplicados: ' . $this->filtrosResumen, '', '', '', '', '', '', '', ''];
        }
        $rows[] = ['', '', '', '', '', '', '', '', ''];

        // ── KPIs ──────────────────────────────────────────────────────────
        $rows[] = ['INDICADORES CLAVE', '', '', '', '', '', '', '', ''];
        $rows[] = ['Total avances', $this->kpis['total_avances'] ?? 0,
                   'Líneas con avance', $this->kpis['lineas_con_avance'] ?? 0,
                   '% Promedio', ($this->kpis['pct_promedio'] ?? 0) . '%',
                   'Con evidencia', $this->kpis['con_evidencia'] ?? 0, ''];
        $rows[] = ['', '', '', '', '', '', '', '', ''];

        // ── Tabla ─────────────────────────────────────────────────────────
        $rows[] = [
            'Prioridad',
            'Línea de acción',
            'Período',
            'Estatus',
            'Fecha avance',
            'Valor',
            '% Cumplimiento',
            'Medio de verificación',
            'Evidencia',
        ];

        foreach ($this->avances as $a) {
            $evidencia = [];
            if (!empty($a['documento']))   $evidencia[] = 'Archivo: ' . $a['documento'];
            if (!empty($a['url']))         $evidencia[] = 'URL: ' . $a['url'];

            $rows[] = [
                'P' . ($a['numero_prioridad'] ?? '—'),
                $a['prioridad'] ?? '—',
                $a['periodo_nombre'] ?? '—',
                $a['estatus'] ?? '—',
                $a['fecha_avance'] ?? '—',
                $a['avance_cualitativo'] ?? '',
                isset($a['avance_cuantitativo'])
                    ? round(($a['avance_cuantitativo']) * 100, 1) . '%'
                    : '—',
                $a['medio_verificacion'] ?? '—',
                $evidencia ? implode(' | ', $evidencia) : 'Sin evidencia adjunta',
            ];

            // Fila adicional con comentario si existe
            if (!empty($a['comentario'])) {
                $rows[] = ['', 'Comentario: ' . $a['comentario'], '', '', '', '', '', '', ''];
            }
        }

        if ($this->avances->isEmpty()) {
            $rows[] = ['No hay avances registrados con los filtros aplicados.', '', '', '', '', '', '', '', ''];
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12, 'B' => 50, 'C' => 18, 'D' => 22,
            'E' => 14, 'F' => 12, 'G' => 16, 'H' => 30, 'I' => 50,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Título institucional
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => self::VERDE]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
        ]);
        $sheet->mergeCells('A3:I3');
        $sheet->mergeCells('A4:I4');
        $sheet->mergeCells('A5:I5');
        $sheet->getStyle('A3:A5')->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
        ]);

        // Bloque KPIs (fila ~8-9)
        $headerKpisRow = $this->filtrosResumen ? 9 : 8;
        $kpisRow = $headerKpisRow + 1;

        $sheet->mergeCells("A{$headerKpisRow}:I{$headerKpisRow}");
        $sheet->getStyle("A{$headerKpisRow}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => self::BLANCO], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::VERDE]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($headerKpisRow)->setRowHeight(20);

        $sheet->getStyle("A{$kpisRow}:I{$kpisRow}")->applyFromArray([
            'font'      => ['bold' => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GRIS]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);

        // Encabezado de tabla
        $tablaHeaderRow = $kpisRow + 2;
        $sheet->getStyle("A{$tablaHeaderRow}:I{$tablaHeaderRow}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => self::BLANCO], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::MAGENTA]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);
        $sheet->getRowDimension($tablaHeaderRow)->setRowHeight(28);
        $sheet->freezePane('A' . ($tablaHeaderRow + 1));

        // Wrap text para columnas largas
        $sheet->getStyle('B:B')->getAlignment()->setWrapText(true);
        $sheet->getStyle('H:H')->getAlignment()->setWrapText(true);
        $sheet->getStyle('I:I')->getAlignment()->setWrapText(true);

        return [];
    }
}
