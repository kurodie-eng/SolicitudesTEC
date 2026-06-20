<?php
function generarReportePDF(array $datos, string $carpeta): void {
    $fpdfPath = __DIR__ . '/../lib/fpdf/fpdf.php';
    if (!file_exists($fpdfPath)) return;

    require_once $fpdfPath;

    $enc  = fn(string $s): string => iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);
    $logo = __DIR__ . '/../img/tec_logo.png';
    $pageW   = 215.9;
    $marginL = 15;
    $marginR = 15;
    $contentW = $pageW - $marginL - $marginR;

    $pieTexto = $enc('Generado el ' . date('d/m/Y \a \l\a\s H:i:s') . ' — ITSRV SOPORTEC');

    $pdf = new class($pieTexto, $marginL, $marginR, $pageW) extends FPDF {
        private string $pieTexto;
        private float  $mL;
        private float  $mR;
        private float  $pW;

        public function __construct(string $pieTexto, float $mL, float $mR, float $pW) {
            parent::__construct('P', 'mm', 'Letter');
            $this->pieTexto = $pieTexto;
            $this->mL = $mL;
            $this->mR = $mR;
            $this->pW = $pW;
        }

        public function Footer(): void {
            $this->SetY(-18);
            $this->SetDrawColor(210, 210, 210);
            $this->SetLineWidth(0.2);
            $this->Line($this->mL, $this->GetY(), $this->pW - $this->mR, $this->GetY());
            $this->Ln(2);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(150, 150, 150);
            $this->Cell(0, 5, $this->pieTexto, 0, 0, 'C');
        }
    };

    $pdf->SetMargins($marginL, $marginL, $marginR);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();

    // ── CABECERA ──────────────────────────────────────────────────────────────
    $logoW = 28;
    $logoH = 26;
    if (file_exists($logo)) {
        $pdf->Image($logo, $pageW - $marginR - $logoW, 11, $logoW, $logoH, 'PNG');
    }

    $pdf->SetFont('Arial', 'B', 13);
    $pdf->SetXY($marginL, 12);
    $pdf->MultiCell(140, 7, $enc('Instituto Tecnológico Superior de Rioverde, S.L.P.'), 0, 'L');
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetX($marginL);
    $pdf->Cell(140, 5, $enc('SOPORTEC  —  Sistema de Soporte Técnico Institucional'), 0, 1, 'L');

    // Línea divisoria — debajo del logo
    $dividerY = 11 + $logoH + 4;
    $pdf->SetDrawColor(50, 90, 180);
    $pdf->SetLineWidth(0.6);
    $pdf->Line($marginL, $dividerY, $pageW - $marginR, $dividerY);

    // ── TÍTULO DEL DOCUMENTO ──────────────────────────────────────────────────
    $pdf->SetY($dividerY + 4);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, $enc('Reporte de Solicitud'), 0, 1, 'C');

    // ── METADATOS ─────────────────────────────────────────────────────────────
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(110, 110, 110);
    $pdf->Cell(0, 5,
        'Folio #' . $datos['id_bit']
        . '   |   Solicitud #' . $datos['id_sol']
        . '   |   ' . $enc($datos['trabajador'])
        . '   |   ' . date('d/m/Y H:i:s'),
        0, 1, 'C'
    );
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(4);
    $pdf->SetDrawColor(210, 210, 210);
    $pdf->SetLineWidth(0.2);
    $pdf->Line($marginL, $pdf->GetY(), $pageW - $marginR, $pdf->GetY());
    $pdf->Ln(6);

    // ── TÍTULO DEL REPORTE ────────────────────────────────────────────────────
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(235, 242, 255);
    $pdf->Cell(0, 9, ' ' . $enc($datos['encabezado']), 0, 1, 'L', true);
    $pdf->Ln(5);

    // ── DESCRIPCIÓN DEL PROBLEMA ──────────────────────────────────────────────
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(248, 248, 248);
    $pdf->Cell(0, 7, $enc('  Descripción del problema'), 'B', 1, 'L', true);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Ln(2);
    $pdf->MultiCell(0, 6, $enc($datos['descripcion_problema']), 0, 'J');
    $pdf->Ln(5);

    // ── SOLUCIÓN APLICADA ─────────────────────────────────────────────────────
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(248, 248, 248);
    $pdf->Cell(0, 7, $enc('  Solución aplicada'), 'B', 1, 'L', true);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Ln(2);
    $pdf->MultiCell(0, 6, $enc($datos['descripcion_solucion']), 0, 'J');
    $pdf->Ln(6);

    // ── EVIDENCIA FOTOGRÁFICA ─────────────────────────────────────────────────
    if (!empty($datos['evidencia'])) {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(248, 248, 248);
        $pdf->Cell(0, 7, $enc('  Evidencia fotográfica'), 'B', 1, 'L', true);
        $pdf->Ln(5);

        $tipos = ['jpg' => 'JPEG', 'jpeg' => 'JPEG', 'png' => 'PNG'];
        $maxH  = 120;

        $imagenes = [];
        foreach (explode(',', $datos['evidencia']) as $ruta) {
            $rutaAbs = __DIR__ . '/../' . trim($ruta);
            $ext     = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
            if (!isset($tipos[$ext]) || !file_exists($rutaAbs)) continue;
            $size = getimagesize($rutaAbs);
            if (!$size) continue;
            $imagenes[] = [
                'path'  => $rutaAbs,
                'type'  => $tipos[$ext],
                'ratio' => $size[1] / $size[0],
            ];
        }

        $count = count($imagenes);
        if ($count > 0) {
            $gap  = 4;
            $imgW = ($contentW - ($count - 1) * $gap) / $count;
            $x    = $marginL;
            $y    = $pdf->GetY();
            $maxRowH = 0;

            foreach ($imagenes as $img) {
                $w = $imgW;
                $h = $imgW * $img['ratio'];
                if ($h > $maxH) {
                    $h = $maxH;
                    $w = $maxH / $img['ratio'];
                }
                $maxRowH = max($maxRowH, $h);
                $pdf->Image($img['path'], $x, $y, $w, $h, $img['type']);
                $x += $imgW + $gap;
            }
            $pdf->SetY($y + $maxRowH + 6);
        }
    }

    $pdf->Output('F', $carpeta . 'reporte_' . $datos['id_bit'] . '.pdf');
}
