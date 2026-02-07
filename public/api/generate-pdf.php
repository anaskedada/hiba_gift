<?php
require __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use Spatie\Browsershot\Browsershot;

$data = json_decode(file_get_contents('../storage/answers.json'), true) ?? [];

function v(array $data, string $key, string $fallback = '—'): string
{
    $val = $data[$key] ?? '';
    $val = is_string($val) ? trim($val) : '';
    return $val !== '' ? htmlspecialchars($val, ENT_QUOTES, 'UTF-8') : $fallback;
}

function img_to_data_uri(string $absPath): string
{
    if (!is_file($absPath)) return '';
    $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'jpg', 'jpeg' => 'image/jpeg',
        default => 'application/octet-stream',
    };
    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absPath));
}

/**
 * Answers
 */
$answers = [
    ['label' => "Quand je me sens le plus aimé(e)", 'key' => 'feelLoved'],
    ['label' => "Mon langage de l’amour",           'key' => 'loveLanguage'],
    ['label' => "Ma valeur principale",            'key' => 'coreValue'],
    ['label' => "Ce dont j’ai besoin davantage",   'key' => 'needs'],
    ['label' => "Résoudre les conflits",           'key' => 'conflict'],
    ['label' => "Ce que j’espère en amour",        'key' => 'hope'],
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

$goal2026 = v($data, 'goal2026', '—');

/**
 * Images (base64)
 */
$baseDir = realpath(__DIR__ . '/../assets');

$hibaMainFile = realpath(__DIR__ . '/../assets/hiba_pictures/hiba.jpg');
$valFile      = realpath(__DIR__ . '/../assets/valentine-bg.jpg');
$hrtFile      = realpath(__DIR__ . '/../assets/hearth.jpg');

if (!$hibaMainFile) die("Missing main image: " . __DIR__ . '/../assets/hiba_pictures/hiba.jpg');
if (!$valFile)      die("Missing image: " . __DIR__ . '/../assets/valentine-bg.jpg');
if (!$hrtFile)      die("Missing image: " . __DIR__ . '/../assets/hearth.jpg');

$hibaMainSrc = img_to_data_uri($hibaMainFile);
$valSrc      = img_to_data_uri($valFile);
$hrtSrc      = img_to_data_uri($hrtFile);

/**
 * Gallery thumbnails 1.jpeg .. 6.jpeg
 */
$thumbsHtml = '';
for ($i = 1; $i <= 6; $i++) {
    $thumbPath = realpath(__DIR__ . "/../assets/hiba_pictures/{$i}.jpeg");
    if (!$thumbPath) {
        // if some are missing, just skip
        continue;
    }
    $thumbSrc = img_to_data_uri($thumbPath);
    $thumbsHtml .= "<div class='thumb'><img src='{$thumbSrc}' alt='thumb {$i}'></div>";
}

if ($thumbsHtml === '') {
    // fallback text if none found
    $thumbsHtml = "<div class='thumbEmpty'>No extra photos found</div>";
}

/**
 * HTML
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
      width: 794px;
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

    /* ============================
       ✅ LEFT SIDE: One collage card
       Main photo (65%) + Thumbs (35%)
    ============================ */

    .leftCard{
      display: grid;
      grid-template-rows: 0.65fr 0.35fr;
      height: 100%;
    }

    /* Main photo */
    .mainPhoto{
      background: #eee;
    }

    .mainPhoto img{
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      border-radius: 16px;
    }

    /* Thumbnails area (2 columns × 3 rows) */
    .thumbs{
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      grid-template-rows: repeat(3, 1fr);
      gap: 12px;
      padding: 12px;
      background: rgba(255,255,255,0.20);
    }

    /* Bigger thumbnails (+30%) */
    .thumb{
      border-radius: 16px;
      overflow: hidden;
      border: 1px solid rgba(0,0,0,0.06);
      height: 120px;
      background: #eee;
    }

    .thumb img{
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    /* ============================
       ✅ RIGHT SIDE
    ============================ */

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

    .topImg{
      background:#eee;
    }

    .topImg img{
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
    }

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

    .heartImg{
      background:#eee;
      height: 100%;
    }

    .heartImg img{
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
    }
      .goalText{
  margin-top: 12px;
  padding: 12px;
  text-align: center;
  font-size: 12px;
  font-weight: 700;
  color: #b51d4a;

  border-radius: 14px;
  background: rgba(255,255,255,0.75);
  border: 1px solid rgba(0,0,0,0.06);
}

.goalText span{
  display: block;
  margin-top: 6px;
  font-weight: 400;
  color: #333;
}

  </style>
</head>

<body>
  <div class='page'>
    <div class='grid'>

      <!-- ✅ LEFT: One card collage -->
      <div class='card leftCard'>

        <!-- Main photo -->
        <div class='mainPhoto'>
          <img src='{$hibaMainSrc}' alt='Hiba Main'>
        </div>

        <!-- Gallery thumbnails -->
        <div class='thumbs'>
          {$thumbsHtml}
        </div>

      </div>

      <!-- ✅ RIGHT SIDE -->
      <div class='right'>

        <!-- Top right -->
        <div class='card topCard'>
          <div class='topImg'>
            <img src='{$valSrc}' alt='Valentine Image'>
          </div>

          <div class='answers'>
            {$rows}
            <div class='goalText'>
              ✨ 2026 Goal ✨
              <span>{$goal2026}</span>
            </div>
          </div>
        </div>

        <!-- Bottom right -->
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
 * 1) Generate PDF
 */
$options = new Options();
$options->set('isRemoteEnabled', true);

$pdf = new Dompdf($options);
$pdf->loadHtml($html, 'UTF-8');
$pdf->setPaper('A4', 'portrait');
$pdf->render();

$giftDir = __DIR__ . '/../assets/gift_images';

@mkdir($giftDir, 0777, true);

/* Save PDF */
$pdfPath = $giftDir . '/love-profile.pdf';
file_put_contents($pdfPath, $pdf->output());

/* Save PNG */
$pngPath = $giftDir . '/love-profile.png';
$pngSaved = false;

try {
    if (class_exists(Browsershot::class)) {
        Browsershot::html($html)
            ->windowSize(794, 1123)
            ->deviceScaleFactor(2)
            ->save($pngPath);

        $pngSaved = true;
    }
} catch (\Throwable $e) {
    // keep going
}

echo "✅ PDF saved: {$pdfPath}\n";
echo $pngSaved
    ? "✅ PNG saved: {$pngPath}\n"
    : "ℹ️ PNG not generated (Browsershot/Node/Chrome not available or failed).\n";
