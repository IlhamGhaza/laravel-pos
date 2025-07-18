<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>POS Tukang Pupuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        html,
        body {
            height: 100%;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F5F7FA 0%, #E9ECF5 100%);
            margin: 0;
            color: #232B4B;
            min-height: 100vh;
            min-width: 100vw;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 8px;
        }

        .card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 6px 32px rgba(57, 73, 171, 0.10), 0 1.5px 6px rgba(57, 73, 171, 0.04);
            padding: 32px 18px 28px 18px;
            text-align: center;
            margin-bottom: 32px;
            max-width: 420px;
            width: 100%;
            transition: box-shadow 0.2s, transform 0.2s;
        }

        .card:hover {
            box-shadow: 0 12px 48px rgba(57, 73, 171, 0.16), 0 2px 8px rgba(57, 73, 171, 0.06);
            transform: translateY(-2px) scale(1.012);
        }

        .logo {
            width: 68px;
            height: 68px;
            border-radius: 18px;
            background: #E9ECF5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px auto;
            box-shadow: 0 2px 8px rgba(57, 73, 171, 0.10);
        }

        .logo img,
        .logo svg {
            width: 40px;
            height: 40px;
        }

        .title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: #232B4B;
        }

        .subtitle {
            color: #3949AB;
            font-size: 1.15rem;
            margin-bottom: 18px;
            font-weight: 500;
        }

        .desc {
            color: #4B5B7C;
            font-size: 1.08rem;
            margin-bottom: 32px;
        }

        .admin-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            background: linear-gradient(90deg, #3949AB 60%, #232B4B 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 16px 0;
            font-size: 1.15rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s, transform 0.2s;
            margin-bottom: 18px;
            box-shadow: 0 2px 8px rgba(57, 73, 171, 0.10);
        }

        .admin-btn:hover {
            background: linear-gradient(90deg, #232B4B 60%, #3949AB 100%);
            box-shadow: 0 4px 16px rgba(57, 73, 171, 0.18);
            transform: scale(1.02);
        }

        .admin-btn svg {
            fill: #fff;
        }

        .info {
            color: #7C88A6;
            font-size: 1.01rem;
            margin-bottom: 0;
        }

        .mockup {
            margin: 0 auto;
            margin-top: 32px;
            max-width: 420px;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(57, 73, 171, 0.10);
            background: #F5F7FA;
            padding: 24px 0 0 0;
        }

        /* Tablet */
        @media (min-width: 600px) {
            .container {
                padding: 48px 0;
            }

            .card {
                max-width: 520px;
                padding: 44px 36px 36px 36px;
                border-radius: 28px;
            }

            .logo {
                width: 80px;
                height: 80px;
                border-radius: 22px;
                margin-bottom: 22px;
            }

            .logo img,
            .logo svg {
                width: 48px;
                height: 48px;
            }

            .title {
                font-size: 2.3rem;
            }

            .subtitle {
                font-size: 1.25rem;
            }

            .desc {
                font-size: 1.13rem;
            }

            .mockup {
                max-width: 520px;
                border-radius: 32px;
                margin-top: 40px;
            }
        }

        /* Desktop */
        @media (min-width: 900px) {

            body,
            .container {
                min-height: 100vh;
                height: 100vh;
            }

            .container {
                flex-direction: row;
                justify-content: center;
                align-items: center;
                gap: 48px;
                padding: 0 0;
                max-width: 1100px;
                margin: 0 auto;
            }

            .card {
                max-width: 480px;
                min-width: 400px;
                margin-bottom: 0;
                border-radius: 32px;
                padding: 56px 48px 48px 48px;
            }

            .mockup {
                max-width: 400px;
                min-width: 320px;
                border-radius: 36px;
                margin-top: 0;
                box-shadow: 0 8px 32px rgba(57, 73, 171, 0.13);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="logo">
                <img src="{{ asset('logo.png') }}" alt="Logo POS Tukang Pupuk"
                    style="width:40px;height:40px;object-fit:contain;">
            </div>
            <div class="title">POS Tukang Pupuk</div>
            <div class="subtitle">Aplikasi Kasir Mobile Modern</div>
            <div class="desc">Solusi Point of Sale (POS) khusus kios pupuk. Mudah, cepat, dan efisien untuk kebutuhan
                harian penjualan pupuk dan pertanian.</div>
            <button class="admin-btn" onclick="window.location.href='/admin'">
                <svg style="vertical-align:middle;" width="22" height="22" fill="none" viewBox="0 0 24 24">
                    <path d="M16 17v-1a4 4 0 0 0-8 0v1" stroke="#fff" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <circle cx="12" cy="9" r="4" fill="#fff" stroke="#fff" stroke-width="0.5" />
                </svg>
                Masuk Admin
            </button>
            <div class="info">Hanya admin yang dapat login. Pengguna kasir dapat menggunakan aplikasi mobile.</div>
        </div>
        <div class="mockup">
            <!-- Ilustrasi POS Mobile (SVG) nuansa indigo -->
            <svg viewBox="0 0 320 480" fill="none" style="width:100%;height:auto;">
                <rect x="24" y="24" width="272" height="432" rx="36" fill="#fff" stroke="#3949AB"
                    stroke-width="6" />
                <rect x="48" y="60" width="224" height="320" rx="18" fill="#F5F7FA" />
                <rect x="70" y="90" width="180" height="36" rx="10" fill="#E9ECF5" />
                <rect x="70" y="140" width="180" height="36" rx="10" fill="#E9ECF5" />
                <rect x="70" y="190" width="180" height="36" rx="10" fill="#E9ECF5" />
                <rect x="70" y="240" width="180" height="36" rx="10" fill="#E9ECF5" />
                <rect x="120" y="400" width="80" height="16" rx="8" fill="#3949AB" />
                <circle cx="160" cy="390" r="10" fill="#232B4B" />
                <rect x="110" y="350" width="100" height="24" rx="8" fill="#3949AB" />
                <rect x="140" y="370" width="40" height="10" rx="5" fill="#E9ECF5" />
            </svg>
        </div>
    </div>
</body>

</html>
