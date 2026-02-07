<?php
require '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use Spatie\Browsershot\Browsershot;

$data = json_decode(file_get_contents('../storage/answers.json'), true) ?? [];

function v(array $data, string $key, string $fallback = '—'): string {
    $val = $data[$key] ?? '';
    $val = is_string($val) ? trim($val) : '';
    return $val !== '' ? htmlspecialchars($val, ENT_QUOTES, 'UTF-8') : $fallback;
}

function img_to_data_uri(string $absPath): string {
    $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'png'  => 'image/png',
        'webp' => 'image/webp',
        default => 'image/jpeg',
    };
    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absPath));
}

/**
 * Answers
 */
$answers = [
    ['label' => 'Feel loved when', 'key' => 'feelLoved'],
    ['label' => 'Love language',   'key' => 'loveLanguage'],
    ['label' => 'Core value',      'key' => 'coreValue'],
    ['label' => 'Needs more',      'key' => 'needs'],
    ['label' => 'Conflict resolution',  'key' => 'conflict'],
    ['label' => 'Hope in love',      'key' => 'hope'],

    // ['label' => 'Hope in love', 'key' => 'hope'],
];

$rows = '';
foreach ($answers as $r) {
    $label = htmlspecialchars($r['label'], ENT_QUOTES, 'UTF-8');
    $val   = v($data, $r['key'], '—');
    $rows .= "
      <div class='row'>
        <div class='label'>{$label}</div>
        <div class='val'>{$val}</div>
      </div>
    ";
}

/**
 * Images -> base64 (Dompdf-safe + Browsershot-safe)
 */
$hibaFile = realpath(__DIR__ . '/../public/assets/hiba.jpg');
$valFile  = realpath(__DIR__ . '/../public/assets/valentine-bg.jpg');
$hrtFile  = realpath(__DIR__ . '/../public/assets/hearth.jpg');

if (!$hibaFile) die("Missing image: " . __DIR__ . '/../public/assets/hiba.jpg');
if (!$valFile)  die("Missing image: " . __DIR__ . '/../public/assets/valentine-bg.jpg');
if (!$hrtFile)  die("Missing image: " . __DIR__ . '/../public/assets/hearth.jpg');

$hibaSrc = img_to_data_uri($hibaFile);
$valSrc  = img_to_data_uri($valFile);
$hrtSrc  = img_to_data_uri($hrtFile);

/**
 * HTML (same)
 */
$html = "
<!doctype html>
<html lang='en'>
<head>
  <meta charset='utf-8' />
  <title>Love Profile</title>

  <style>
    @page { margin: 14px; }

    body{
      margin:0;
      font-family: DejaVu Sans, Arial, sans-serif;
      background:#ffffff;
    }

    .page{
      width: 794px;      /* A4-ish for PNG capture */
      height: 1123px;
      background:#ffffff;
      border-radius:18px;
      overflow:hidden;
      padding:14px;
      box-sizing:border-box;
    }

    .grid{
      display: grid;
      grid-template-columns: 1.15fr 0.85fr;
      gap: 14px;
      height: 100%;
    }

    .card{
      border-radius:18px;
      overflow:hidden;
      background: rgba(255,255,255,0.92);
      border: 1px solid rgba(0,0,0,0.06);
    }

    .letterImg{ position: relative; height: 100%; background: #eee; }
    .letterImg img{ width: 100%; height: 100%; object-fit: cover; display: block; }

    .right{
      display:grid;
      grid-template-rows: 1fr 0.9fr;
      gap:14px;
      height:100%;
    }

    .topCard{
      display:grid;
      grid-template-columns: 0.9fr 1.1fr;
      height:100%;
    }

    .topImg{ background:#eee; }
    .topImg img{ width:100%; height:100%; object-fit:cover; display:block; }

    .answers{
      padding:12px;
      box-sizing:border-box;
      background: rgba(255,255,255,0.86);
    }

    .row{
      padding:9px 9px;
      border-radius:14px;
      background: rgba(255,255,255,0.70);
      border: 1px solid rgba(0,0,0,0.05);
      margin-bottom:9px;
    }

    .label{
      font-size:11px;
      font-weight:700;
      color:#b51d4a;
      margin-bottom:5px;
    }

    .val{
      font-size:12px;
      color:#333;
      line-height:1.35;
      white-space: pre-wrap;
    }

    .heartImg{ background:#eee; height: 100%; }
    .heartImg img{ width:100%; height:100%; object-fit:cover; display:block; }
  </style>
</head>

<body>
  <div class='page'>
    <div class='grid'>

      <div class='card letterImg'>
        <img src='{$hibaSrc}' alt='Left Image'>
      </div>

      <div class='right'>

        <div class='card topCard'>
          <div class='topImg'>
            <img src='{$valSrc}' alt='Valentine Image'>
          </div>

          <div class='answers'>
            {$rows}
          </div>
        </div>

        <div class='card heartImg'>
          <img src='{$hrtSrc}' alt='Heart Image'>
        </div>

      </div>
    </div>
  </div>
</body>
</html>
";

/**
 * 1) Generate PDF (Dompdf)
 */
$options = new Options();
$options->set('isRemoteEnabled', true);

$pdf = new Dompdf($options);
$pdf->loadHtml($html, 'UTF-8');
$pdf->setPaper('A4', 'portrait');
$pdf->render();

@mkdir('../storage/pdf', 0777, true);

$pdfPath = '../storage/pdf/love-profile.pdf';
file_put_contents($pdfPath, $pdf->output());

/**
 * 2) Generate PNG (Browsershot) - safe fallback
 * Requires: composer require spatie/browsershot
 * And server must have: Node + Chrome/Chromium
 */
$pngPath = '../storage/pdf/love-profile.png';
$pngSaved = false;

try {
    if (class_exists(Browsershot::class)) {
        Browsershot::html($html)
        
            ->windowSize(794, 1123)
            ->deviceScaleFactor(2)     // higher quality
            ->waitUntilNetworkIdle()
            ->save($pngPath);

        $pngSaved = true;
    }
} catch (\Throwable $e) {
    // keep going, PDF is saved
}

echo "✅ PDF saved: {$pdfPath}\n";
echo $pngSaved
    ? "✅ PNG saved: {$pngPath}\n"
    : "ℹ️ PNG not generated (Browsershot/Node/Chrome not available or failed).\n";
