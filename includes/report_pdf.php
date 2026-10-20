<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/tfpdf/tfpdf.php';

final class SmartCashReportPDF extends tFPDF
{
    private const WIDTH = 267.0;
    private const BOTTOM = 192.0;
    private const LINE = 4.5;
    private array $widths;
    private string $title;
    private bool $sectionOpening = true;
    private readonly bool $complete;

    public function __construct(
        private array $columns,
        private string $kind,
        private readonly string $start,
        private readonly string $end,
        private readonly string $owner,
        private readonly string $generatedAt
    ) {
        parent::__construct('L', 'mm', 'A4');
        $this->complete = $kind === 'completo';
        $this->title = match ($kind) {
            'despesas' => 'Relatório de despesas',
            'receitas' => 'Relatório de receitas',
            'categorias' => 'Despesas por categoria',
            'saldo' => 'Fluxo de caixa',
            'completo' => 'Relatório completo'
        };
        $this->widths = $kind === 'categorias' ? [170, 45, 52] : [88, 47, 65, 28, 39];
        $this->SetMargins(15, 12, 15);
        $this->SetAutoPageBreak(false);
        $this->SetLineWidth(0.2);
        $this->AliasNbPages();
        $this->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
        $this->AddFont('DejaVu', 'B', 'DejaVuSansCondensed-Bold.ttf', true);
        $this->AddFont('DejaVu', 'BI', 'DejaVuSansCondensed-BoldOblique.ttf', true);
        $this->SetTitle($this->title . ' | SmartCash', true);
        $this->SetAuthor(self::plain($owner), true);
        $this->SetCreator('SmartCash');
        $this->SetSubject(formatDateBr($start) . ' a ' . formatDateBr($end), true);
    }

    private static function plain(string $text): string
    {
        // Mantém as quebras de linha, retirando apenas controles invisíveis.
        return preg_replace(
            '/[\x00-\x08\x0B-\x1F\x7F]/u',
            '',
            str_replace(["\r\n", "\r", "\t"], ["\n", "\n", ' '], $text)
        ) ?? '';
    }

    public function Header(): void
    {
        $this->SetFillColor(24, 31, 29);
        $this->Rect(0, 0, 297, 45, 'F');
        $this->SetFillColor(192, 237, 140);
        $this->Rect(15, 12, 10, 10, 'F');
        $this->SetTextColor(24, 31, 29);
        $this->SetFont('DejaVu', 'BI', 20);
        $this->SetXY(15, 11.8);
        $this->Cell(10, 10, '$', 0, 0, 'C');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('DejaVu', 'B', 14);
        $this->SetXY(29, 12);
        $this->Cell(90, 10, 'SmartCash');
        $this->SetFont('DejaVu', '', 8);
        $this->SetXY(145, 12);
        $this->Cell(137, 5, 'Gerado em ' . $this->generatedAt, 0, 1, 'R');
        $this->SetXY(145, 19);
        $this->MultiCell(137, 4.5, self::plain($this->owner), 0, 'R');
        $this->SetFont('DejaVu', 'B', 16);
        $this->SetXY(15, 28);
        $this->Cell(122, 8, $this->title);
        $this->SetTextColor(204, 218, 208);
        $this->SetFont('DejaVu', '', 8);
        $this->SetXY(15, 37);
        $this->Cell(
            267,
            5,
            'Período: ' . formatDateBr($this->start) . ' a ' . formatDateBr($this->end)
        );
        if ($this->complete) {
            $area = array_search($this->kind, array_keys(reportKinds()), true);
            $this->SetXY(145, 37);
            $this->Cell(137, 5, 'Relatório completo | Área ' . ($area + 1) . ' de 4', 0, 0, 'R');
        }
        $this->SetXY(15, 51);
        if (!$this->sectionOpening) {
            $this->tableHeader();
        }
    }

    public function Footer(): void
    {
        $this->SetDrawColor(219, 226, 222);
        $this->Line(15, 197, 282, 197);
        $this->SetTextColor(94, 108, 100);
        $this->SetFont('DejaVu', '', 8);
        $this->SetXY(15, 199);
        $this->Cell(180, 5, 'SmartCash | Relatório pessoal e confidencial');
        $this->Cell(87, 5, 'Página ' . $this->PageNo() . ' de {nb}', 0, 0, 'R');
    }

    private function summaryCards(array $summary): void
    {
        $cards = [
            'Saldo no fim do período' => $summary['saldo_total'],
            'Receitas do período' => $summary['receitas'],
            'Despesas do período' => $summary['despesas'],
            'Resultado do período' => $summary['resultado']
        ];
        $x = 15.0;
        $width = (self::WIDTH - 18) / 4;
        foreach ($cards as $label => $value) {
            $this->SetFillColor(243, 247, 244);
            $this->Rect($x, 51, $width, 24, 'F');
            $this->SetFillColor(192, 237, 140);
            $this->Rect($x, 51, 1, 24, 'F');
            $this->SetTextColor(84, 101, 91);
            $this->SetFont('DejaVu', '', 8.5);
            $this->SetXY($x + 4, 54);
            $this->Cell($width - 8, 5, $label);
            $this->SetTextColor(24, 49, 34);
            $this->SetFont('DejaVu', 'B', 14);
            while ($this->GetStringWidth(money($value)) > $width - 8) {
                $this->SetFontSize($this->FontSizePt - 0.5);
            }
            $this->SetXY($x + 4, 61);
            $this->Cell($width - 8, 9, money($value));
            $x += $width + 6;
        }
        $this->SetTextColor(84, 101, 91);
        $this->SetFont('DejaVu', '', 8);
        $this->SetXY(15, 80);
        $this->MultiCell(self::WIDTH, 4.5, reportNote($this->kind), 0, 'L');
        $this->Ln(5);
    }

    private function tableHeader(): void
    {
        $this->SetFillColor(37, 55, 45);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('DejaVu', 'B', 8.5);
        $this->SetX(15);
        foreach ($this->columns as $index => $column) {
            $this->Cell(
                $this->widths[$index],
                9,
                $column,
                0,
                0,
                $index === count($this->columns) - 1 ? 'R' : 'L',
                true
            );
        }
        $this->Ln(9);
        $this->SetFont('DejaVu', '', 8.5);
    }

    /** Quebra também palavras longas, sem cortar ou descartar o texto. */
    private function wrap(string $text, float $width): array
    {
        $lines = [];
        foreach (explode("\n", self::plain($text)) as $paragraph) {
            $line = '';
            foreach (preg_split('/(?<=\s)/u', $paragraph, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
                if ($line !== '' && $this->GetStringWidth($line . $word) > $width) {
                    $lines[] = rtrim($line);
                    $line = '';
                }
                foreach (mb_str_split($word) as $character) {
                    if ($line !== '' && $this->GetStringWidth($line . $character) > $width) {
                        $lines[] = rtrim($line);
                        $line = '';
                    }
                    $line .= $character;
                }
            }
            $lines[] = rtrim($line);
        }
        return $lines ?: [''];
    }

    private function tableRow(array $values, array $moneyColumns, int $index): void
    {
        $this->SetFont('DejaVu', '', 8.5);
        $lines = [];
        foreach ($values as $column => $text) {
            $lines[] = $this->wrap((string) $text, $this->widths[$column] - 8);
        }
        $remaining = max(array_map('count', $lines));
        $offset = 0;
        while ($remaining > 0) {
            $available = (int) floor((self::BOTTOM - $this->GetY() - 6) / self::LINE);
            // Uma linha que cabe inteira na página seguinte não é dividida.
            if ($available < 1 || ($offset === 0 && $remaining > $available && $remaining <= 28)) {
                $this->AddPage();
                $available = (int) floor((self::BOTTOM - $this->GetY() - 6) / self::LINE);
            }
            $take = min($remaining, $available);
            $height = $take * self::LINE + 6;
            $y = $this->GetY();
            $this->SetFillColor(...$index % 2 ? [246, 248, 246] : [255, 255, 255]);
            $this->Rect(15, $y, self::WIDTH, $height, 'F');
            $x = 15.0;
            foreach ($lines as $column => $cellLines) {
                $this->SetTextColor(42, 57, 48);
                foreach (array_slice($cellLines, $offset, $take) as $lineIndex => $line) {
                    $this->SetXY($x + 4, $y + 3 + $lineIndex * self::LINE);
                    $this->Cell(
                        $this->widths[$column] - 8,
                        self::LINE,
                        $line,
                        0,
                        0,
                        in_array($column, $moneyColumns, true) ? 'R' : 'L'
                    );
                }
                $x += $this->widths[$column];
            }
            $this->SetDrawColor(228, 234, 229);
            $this->Line(15, $y + $height, 282, $y + $height);
            $this->SetXY(15, $y + $height);
            $remaining -= $take;
            $offset += $take;
        }
    }

    public function render(array $data, array $summary): string
    {
        $this->renderSection($data, $summary);
        return $this->Output('S');
    }

    public function renderComplete(array $sections, array $summary): string
    {
        foreach (reportKinds() as $kind => $label) {
            $this->kind = $kind;
            $this->title = $label;
            $this->columns = $sections[$kind]['headers'];
            $this->widths = $kind === 'categorias' ? [170, 45, 52] : [88, 47, 65, 28, 39];
            $this->renderSection($sections[$kind], $kind === 'despesas' ? $summary : null);
        }
        return $this->Output('S');
    }

    private function renderSection(array $data, ?array $summary): void
    {
        // Cada área começa em uma página própria. Continuações repetem só as colunas.
        $this->sectionOpening = true;
        $this->AddPage();
        $this->sectionOpening = false;
        if ($summary !== null) {
            $this->summaryCards($summary);
        } else {
            $this->SetTextColor(84, 101, 91);
            $this->SetFont('DejaVu', '', 8);
            $this->MultiCell(self::WIDTH, 4.5, reportNote($this->kind), 0, 'L');
            $this->Ln(5);
        }
        $this->SetTextColor(24, 49, 34);
        $this->SetFont('DejaVu', 'B', 11);
        $this->Cell(180, 7, 'Detalhamento do período');
        $this->SetFont('DejaVu', '', 8);
        $count = count($data['rows']);
        $this->Cell(
            87,
            7,
            $count . ' ' . ($count === 1 ? 'registro' : 'registros') . ' | Todos os resultados',
            0,
            1,
            'R'
        );
        $this->Ln(2);
        $this->tableHeader();
        $total = 0;
        foreach ($data['rows'] as $index => $row) {
            $total += cents((string) $row[count($this->columns) - 1], true);
            $formatted = [];
            foreach ($row as $column => $value) {
                $formatted[] = in_array($column, $data['money'], true)
                    ? money($value)
                    : (in_array($column, $data['dates'] ?? [], true)
                        ? formatDateBr($value)
                        : (string) $value);
            }
            $this->tableRow($formatted, $data['money'], $index);
        }
        if (!$count) {
            $this->SetTextColor(94, 108, 100);
            $this->Cell(
                self::WIDTH,
                18,
                'Nenhum registro encontrado no período selecionado.',
                0,
                1,
                'C'
            );
        }
        if ($this->GetY() + 16 > self::BOTTOM) {
            $this->AddPage();
        }
        $this->Ln(3);
        $this->SetFillColor(232, 244, 220);
        $this->SetTextColor(24, 49, 34);
        $this->SetFont('DejaVu', 'B', 10);
        $this->Cell(
            200,
            12,
            $this->kind === 'saldo' ? 'Variação líquida de caixa no período' : 'Total do relatório',
            0,
            0,
            'L',
            true
        );
        $this->Cell(67, 12, money(dec($total)), 0, 1, 'R', true);
    }
}

function buildReportPDF(
    array $data,
    string $kind,
    string $start,
    string $end,
    array $summary,
    string $owner
): string {
    if (!in_array($kind, [...array_keys(reportKinds()), 'completo'], true)) {
        throw new DomainException('Tipo de relatório inválido.');
    }
    $pdf = new SmartCashReportPDF(
        $kind === 'completo' ? [] : $data['headers'],
        $kind,
        $start,
        $end,
        $owner,
        date('d/m/Y H:i') . ' (' . date_default_timezone_get() . ')'
    );
    return $kind === 'completo'
        ? $pdf->renderComplete($data, $summary)
        : $pdf->render($data, $summary);
}

function exportPDF(array $data, string $kind, string $start, string $end): never
{
    // Conclui a geração antes de enviar cabeçalhos: falhas não viram PDFs corrompidos.
    $bytes = buildReportPDF(
        $data,
        $kind,
        $start,
        $end,
        summary($start, $end),
        $_SESSION['nome_usuario'] ?? ''
    );
    header('Content-Type: application/pdf');
    header(
        'Content-Disposition: attachment; filename="smartcash-' .
            $kind .
            '-' .
            $start .
            '-a-' .
            $end .
            '.pdf"'
    );
    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit();
}
