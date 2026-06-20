<?php
session_start();
if (empty($_SESSION["id"]) || !is_numeric($_SESSION["id"]) || $_SESSION["id_rol"] != 3) {
    header("Location: ../index.php");
    exit();
}

require_once "conexion.php";

$fechaInicio = trim($_POST["fecha_inicio"] ?? "");
$fechaFin    = trim($_POST["fecha_fin"]    ?? "");

if (empty($fechaInicio) || empty($fechaFin)) {
    $_SESSION["error"] = "Debes seleccionar inicio y fin del período.";
    header("Location: ../Administrador.php");
    exit();
}

// ── CONSULTAS ─────────────────────────────────────────────────────────────────

$totalRow = $conexion->query("SELECT COUNT(*) AS n FROM solicitud")->fetch_object();
$total    = (int)($totalRow->n ?? 0);

// Contadores por tipo de acción
$contTipos = ['Correctiva' => 0, 'Preventiva' => 0, 'Soporte Técnico' => 0];
$rTipos = $conexion->query(
    "SELECT tipo_accion, COUNT(*) AS n FROM bitacora
     WHERE tipo_accion IS NOT NULL GROUP BY tipo_accion"
);
while ($rt = $rTipos->fetch_object()) {
    if (isset($contTipos[$rt->tipo_accion])) $contTipos[$rt->tipo_accion] = (int)$rt->n;
}

$solicitudes = $conexion->query(
    "SELECT s.id_sol,
            a.nombre  AS area,
            IFNULL(CONCAT(uw.nombre,' ',uw.app), '—') AS trabajador,
            s.encabezado,
            DATE_FORMAT(s.fecha_creacion,'%d/%m/%Y') AS fecha,
            b.tipo_accion
     FROM solicitud s
     JOIN area a ON a.id_area = s.id_area
     LEFT JOIN asignacion asig ON asig.id_sol = s.id_sol
         AND asig.estado_asignacion IN ('activa','completada')
     LEFT JOIN usuario uw ON uw.id_us = asig.id_trabajador
     LEFT JOIN bitacora b ON b.id_bit = (
         SELECT MAX(id_bit) FROM bitacora WHERE id_sol = s.id_sol AND aprobado = TRUE
     )
     ORDER BY s.fecha_creacion ASC"
)->fetch_all(MYSQLI_ASSOC);

// ── PDF ───────────────────────────────────────────────────────────────────────
$fpdfPath = __DIR__ . '/../lib/fpdf/fpdf.php';
if (!file_exists($fpdfPath)) {
    $_SESSION["error"] = "FPDF no encontrado en $fpdfPath.";
    header("Location: ../Administrador.php");
    exit();
}
require_once $fpdfPath;

$enc = fn(string $s): string => iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);

$pageW    = 215.9;
$marginL  = 15;
$marginR  = 15;
$marginT  = 15;
$contentW = $pageW - $marginL - $marginR;   // 185.9 mm

$fechaInicioFmt = date('d/m/Y', strtotime($fechaInicio));
$fechaFinFmt    = date('d/m/Y', strtotime($fechaFin));
$fechaHoy       = date('d/m/Y \a \l\a\s H:i:s');
$pieTexto       = $enc("ITSRV SOPORTEC — Reporte de Período Escolar — Generado el $fechaHoy");

$pdf = new class($pieTexto, $marginL, $marginR, $pageW) extends FPDF {
    public string $pieTexto;
    public float  $mL, $mR, $pW;

    public function __construct(string $pie, float $mL, float $mR, float $pW) {
        parent::__construct('P', 'mm', 'Letter');
        $this->pieTexto = $pie;
        $this->mL = $mL; $this->mR = $mR; $this->pW = $pW;
    }

    public function Footer(): void {
        $this->SetY(-16);
        $this->SetDrawColor(180, 180, 180);
        $this->SetLineWidth(0.2);
        $this->Line($this->mL, $this->GetY(), $this->pW - $this->mR, $this->GetY());
        $this->Ln(2);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 5, $this->pieTexto, 0, 0, 'C');
    }
};

$pdf->SetMargins($marginL, $marginT, $marginR);
$pdf->SetAutoPageBreak(true, 22);
$pdf->AddPage();

// ── CABECERA ──────────────────────────────────────────────────────────────────
$logoPath = __DIR__ . '/../img/logo_tec_.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, $marginL, $marginT, 14);
}

$pdf->SetFont('Arial', 'B', 15);
$pdf->SetTextColor(27, 85, 45);
$pdf->SetXY($marginL + 17, $marginT + 1);
$pdf->Cell(0, 7, $enc('ITSRV SOPORTEC'), 0, 1);

$pdf->SetFont('Arial', '', 8.5);
$pdf->SetTextColor(100, 100, 100);
$pdf->SetX($marginL + 17);
$pdf->Cell(0, 5, $enc('Instituto Tecnológico Superior de Rioverde'), 0, 1);

$pdf->Ln(4);
$pdf->SetDrawColor(27, 85, 45);
$pdf->SetLineWidth(0.5);
$pdf->Line($marginL, $pdf->GetY(), $pageW - $marginR, $pdf->GetY());
$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(20, 20, 20);
$pdf->Cell(0, 8, $enc('Reporte de Período Escolar'), 0, 1);

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(90, 90, 90);
$pdf->Cell(0, 5, $enc("Período: $fechaInicioFmt  —  $fechaFinFmt"), 0, 1);
$pdf->Cell(0, 5, $enc("Total de solicitudes registradas: $total"), 0, 1);
$pdf->SetFont('Arial', '', 8.5);
$pdf->Cell(0, 5, $enc(
    "Correctivas: {$contTipos['Correctiva']}    |    " .
    "Preventivas: {$contTipos['Preventiva']}    |    " .
    "Soporte Técnico: {$contTipos['Soporte Técnico']}"
), 0, 1);
$pdf->Ln(6);

// ── TABLA DE SOLICITUDES ──────────────────────────────────────────────────────
// Anchos: total 185.9 mm
// #(10) | Área(28) | Técnico(35) | Solicitud(40) | Tipo(28) | Fecha(18) | Firma(26.9)
$cW = [10, 28, 35, 40, 28, 18, 26.9];
$cH = ['#', $enc('Área'), $enc('Técnico'), $enc('Solicitud'), $enc('Tipo de Acción'), 'Fecha', 'Firma'];
$cA = ['C', 'C', 'C', 'C', 'C', 'C', 'C'];

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(27, 85, 45);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetDrawColor(200, 200, 200);
$pdf->SetLineWidth(0.2);

foreach ($cH as $i => $h) {
    $pdf->Cell($cW[$i], 8, $h, 1, 0, 'C', true);
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 7.5);
$pdf->SetTextColor(30, 30, 30);

if (empty($solicitudes)) {
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(130, 130, 130);
    $pdf->SetFillColor(250, 250, 250);
    $pdf->Cell(array_sum($cW), 9,
        $enc('No hay solicitudes registradas en este período.'), 1, 1, 'C', true);
} else {
    $fill = false;
    foreach ($solicitudes as $r) {
        $pdf->SetFillColor($fill ? 243 : 255, $fill ? 247 : 255, $fill ? 243 : 255);
        $valores = [
            '#' . $r['id_sol'],
            $r['area'],
            $r['trabajador'],
            $r['encabezado'],
            $r['tipo_accion'] ?? '—',
            $r['fecha'],
            '',
        ];
        foreach ($valores as $i => $v) {
            $pdf->Cell($cW[$i], 7, $enc($v), 1, 0, $cA[$i], true);
        }
        $pdf->Ln();
        $fill = !$fill;
    }
}

// Capturar PDF en memoria
$pdfContent = $pdf->Output('S', '');
$pdfFilename = "reporte-periodo_{$fechaInicio}_{$fechaFin}.pdf";

$limpiarBD = !empty($_POST['limpiar_bd']);

if ($limpiarBD) {
    // ── CREAR ZIP DE RESPALDO ─────────────────────────────────────────────────
    $fechaInicioDisplay = date('d-m-Y', strtotime($fechaInicio));
    $fechaFinDisplay    = date('d-m-Y', strtotime($fechaFin));
    $nombreCarpeta      = "Respaldo del Período [{$fechaInicioDisplay}] a [{$fechaFinDisplay}]";

    $zipTmp = tempnam(sys_get_temp_dir(), 'respaldo_') . '.zip';
    $zip    = new ZipArchive();
    $zip->open($zipTmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    // PDF del reporte
    $zip->addFromString("{$nombreCarpeta}/{$pdfFilename}", $pdfContent);

    // Contenido de uploads/
    $uploadsDir = realpath(__DIR__ . '/../uploads');
    if ($uploadsDir && is_dir($uploadsDir)) {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iter as $info) {
            if ($info->isFile()) {
                $rutaRel = substr($info->getRealPath(), strlen($uploadsDir) + 1);
                $rutaRel = str_replace('\\', '/', $rutaRel);
                $zip->addFile($info->getRealPath(), "{$nombreCarpeta}/uploads/{$rutaRel}");
            }
        }
    }
    $zip->close();

    // ── BORRAR UPLOADS ────────────────────────────────────────────────────────
    if ($uploadsDir && is_dir($uploadsDir)) {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iter as $info) {
            if ($info->isFile())    @unlink($info->getRealPath());
            elseif ($info->isDir()) @rmdir($info->getRealPath());
        }
    }

    // ── RESETEAR BD ──────────────────────────────────────────────────────────
    $conexion->query("SET FOREIGN_KEY_CHECKS=0");
    $conexion->query("TRUNCATE TABLE bitacora");
    $conexion->query("TRUNCATE TABLE notificacion");
    $conexion->query("TRUNCATE TABLE asignacion");
    $conexion->query("TRUNCATE TABLE solicitud");
    $conexion->query("SET FOREIGN_KEY_CHECKS=1");

    // ── ENVIAR ZIP ────────────────────────────────────────────────────────────
    $zipContent = file_get_contents($zipTmp);
    @unlink($zipTmp);

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $nombreCarpeta . '.zip"');
    header('Content-Length: ' . strlen($zipContent));
    echo $zipContent;
    exit();
}

// ── ENVIAR SOLO PDF (sin limpiar) ─────────────────────────────────────────────
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $pdfFilename . '"');
header('Content-Length: ' . strlen($pdfContent));
echo $pdfContent;
exit();
