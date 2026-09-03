<?php
/**
 * Hisobotning chop etishga mo'ljallangan (print-friendly) ko'rinishi (item 14).
 *
 * Bu ko'rinish layoutsiz, minimal inline CSS bilan. Brauzer print-to-PDF
 * orqali PDF sifatida ham saqlanishi mumkin ("chop etish imkoniyati").
 *
 * @var string $reportTitle
 * @var array<int,string> $headers
 * @var array<int,array<int,scalar|null>> $rows
 * @var string $generatedAt
 */
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($reportTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        body { font-family: "Segoe UI", Arial, sans-serif; color: #1a1a1a; margin: 24px; }
        .report-head h1 { font-size: 18px; margin: 0 0 4px; }
        .report-head p { margin: 0; color: #555; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; font-size: 12px; }
        th, td { border: 1px solid #999; padding: 5px 8px; text-align: left; vertical-align: top; }
        th { background: #f0f2f5; }
        .print-actions { margin: 12px 0; }
        @media print {
            .print-actions { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button type="button" onclick="window.print()">Chop etish / PDF sifatida saqlash</button>
    </div>
    <div class="report-head">
        <h1><?= e($reportTitle) ?></h1>
        <p>Andijon davlat pedagogika instituti — doktorantura monitoringi</p>
        <p>Shakllantirilgan: <?= e($generatedAt) ?> | Yozuvlar: <?= count($rows) ?></p>
    </div>
    <table>
        <thead>
            <tr>
                <?php foreach ($headers as $h): ?><th><?= e($h) ?></th><?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="<?= count($headers) ?>">Ma'lumot topilmadi.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($row as $cell): ?><td><?= e($cell) ?></td><?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
