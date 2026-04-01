<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Haber Yayına Alındı</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center;
               align-items: center; min-height: 100vh; background: #f3f4f6; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 12px;
                text-align: center; max-width: 480px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #16a34a; margin-top: 0; }
        a { color: #2563eb; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Haber Yayına Alındı</h1>
        <p><strong>{{ $haber->baslik }}</strong> başlıklı haber başarıyla yayına alındı.</p>
        <p><a href="{{ config('app.url') }}/yonetim/haberler">Haber Listesine Dön</a></p>
    </div>
</body>
</html>
