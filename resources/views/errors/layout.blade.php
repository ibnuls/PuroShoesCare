<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} · PURO</title>
    <style>
        body {
            margin: 0;
            background: #f6f6f2;
            font: 16px/1.7 system-ui;
            color: #111
        }

        main {
            max-width: 600px;
            margin: 12vh auto;
            padding: 30px
        }

        small {
            background: #e8ff00;
            padding: 8px 15px;
            font-weight: 800
        }

        h1 {
            font-size: clamp(32px, 7vw, 52px);
            line-height: 1.1;
            letter-spacing: -.05em
        }

        a {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 30px;
            background: #111;
            color: #e8ff00;
            text-decoration: none;
            font-weight: 700
        }
    </style>
</head>

<body>
    <main><small>PURO / {{ $code }}</small>
        <h1>{{ $heading }}</h1>
        <p>{{ $text }}</p><a href="{{ url('/') }}">Kembali ke beranda ↗</a>
    </main>
</body>

</html>
