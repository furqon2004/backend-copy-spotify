<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spotify Clone API</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #191414 0%, #1DB954 100%);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            text-align: center;
            padding: 2rem;
        }
        .logo {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            letter-spacing: -1px;
        }
        .logo span { color: #1DB954; }
        .subtitle {
            font-size: 1.1rem;
            color: rgba(255,255,255,0.7);
            margin-bottom: 2rem;
        }
        .badge {
            display: inline-block;
            background: rgba(29, 185, 84, 0.2);
            border: 1px solid #1DB954;
            color: #1DB954;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .version {
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">Spotify <span>Clone</span></div>
        <p class="subtitle">Backend API Server</p>
        <div class="badge">✓ Running</div>
        <p class="version">Laravel {{ app()->version() }} • PHP {{ phpversion() }}</p>
    </div>
</body>
</html>
