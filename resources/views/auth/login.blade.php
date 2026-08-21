<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập | Quần Áo Shop</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --ink: #172033;
            --muted: #687385;
            --line: #dfe6ee;
            --canvas: #f4f7f9;
            --surface: #fff;
            --accent: #167c80;
            --accent-dark: #106b6e;
            --danger: #b42318;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-width: 320px;
            min-height: 100vh;
            margin: 0;
            background: var(--canvas);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        .shell {
            display: grid;
            min-height: 100vh;
            grid-template-columns: minmax(0, 1.05fr) minmax(420px, .95fr);
        }

        .intro {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 9%;
            background: #172033;
            color: #f7fafc;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            font-size: 16px;
            font-weight: 700;
        }

        .brand-mark,
        .point-icon {
            display: grid;
            place-items: center;
            border-radius: 8px;
            background: var(--accent);
        }

        .brand-mark {
            width: 36px;
            height: 36px;
        }

        .brand-mark svg {
            width: 19px;
        }

        .intro-copy {
            max-width: 470px;
            margin: 80px 0;
        }

        .eyebrow {
            margin: 0 0 16px;
            color: #8ed7d3;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0 0 18px;
            font-size: 42px;
            line-height: 1.12;
        }

        .intro-copy p:last-child {
            max-width: 390px;
            margin: 0;
            color: #b9c5d5;
            font-size: 16px;
            line-height: 1.65;
        }

        .points {
            display: grid;
            gap: 17px;
        }

        .point {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #cdd6e2;
            font-size: 14px;
        }

        .point-icon {
            width: 30px;
            height: 30px;
            background: #27344a;
            color: #8ed7d3;
        }

        .point-icon svg {
            width: 16px;
        }

        .form-side {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 36px;
            background: var(--surface);
        }

        .form-card {
            width: 100%;
            max-width: 390px;
        }

        .heading {
            margin-bottom: 30px;
        }

        .heading h2 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        .heading p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        .notice {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 10;
            width: min(420px, calc(100vw - 32px));
            margin: 0;
            padding: 12px 14px;
            border: 1px solid;
            border-radius: 6px;
            box-shadow: 0 10px 28px rgba(23, 32, 51, .16);
            font-size: 14px;
            line-height: 1.45;
            transition: opacity .2s ease, transform .2s ease;
        }

        .notice.is-hiding { opacity: 0; transform: translateX(18px); }

        .success {
            border-color: #a7dfcf;
            background: #effaf6;
            color: #087f5b;
        }

        .error {
            border-color: #f2c5c2;
            background: #fff5f4;
            color: var(--danger);
        }

        .field {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 14px;
            font-weight: 650;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap svg {
            position: absolute;
            top: 50%;
            left: 13px;
            width: 18px;
            color: #7b8798;
            transform: translateY(-50%);

            }input[type="email"],
            input[type="password"] {
                width: 100%;
                height: 46px;
                padding: 0 13px 0 43px;
                border: 1px solid var(--line);
                border-radius: 6px;
                outline: 0;
                color: var(--ink);
                font: inherit;
                font-size: 14px;
            }

            input:focus {
                border-color: var(--accent);
                box-shadow: 0 0 0 3px rgba(22, 124, 128, .12);
            }

            .options {
                display: flex;
                align-items: center;
                gap: 8px;
                margin: 2px 0 23px;
                color: var(--muted);
                font-size: 13px;
            }

            .options input {
                width: 15px;
                height: 15px;
                accent-color: var(--accent);
            }

            .submit {
                width: 100%;
                height: 46px;
                border: 0;
                border-radius: 6px;
                background: var(--accent);
                color: #fff;
                font: inherit;
                font-size: 14px;
                font-weight: 700;
                cursor: pointer;
            }

            .submit:hover {
                background: var(--accent-dark);
            }

            .footer {
                margin: 24px 0 0;
                color: var(--muted);
                font-size: 14px;
                text-align: center;
            }

            .footer a {
                color: var(--accent);
                font-weight: 700;
                text-decoration: none;
            }

            @media (max-width:800px) {
                .shell {
                    grid-template-columns: 1fr;
                }

                .intro {
                    display: none;
                }

                .form-side {
                    min-height: 100vh;
                }
            }

            @media (max-width:440px) {
                .form-side {
                    padding: 24px 20px;
                }

                .heading h2 {
                    font-size: 24px;
                }
            }
    </style>
</head>

<body>
    <main class="shell">

        <section class="intro">
            <div class="brand"><span class="brand-mark"><i data-lucide="shopping-bag"></i></span>Quản lý học sinh</div>
            <div class="intro-copy">
                <p class="eyebrow">Khu vực nội bộ</p>
                <h1>Vận hành cửa hàng gọn gàng, rõ ràng.</h1>
                <p>Đăng nhập để theo dõi thông tin tài khoản và truy cập các công cụ được cấp quyền.</p>
            </div>
            <div class="points">
                <div class="point"><span class="point-icon"><i data-lucide="shield-check"></i></span>Test</div>
                <div class="point"><span class="point-icon"><i data-lucide="layout-dashboard"></i></span>Demo</div>
            </div>
        </section>
        <section class="form-side">
            <div class="form-card">
                <div class="heading">
                    <h2>Đăng nhập</h2>
                    <p>Nhập thông tin tài khoản để tiếp tục.</p>
                </div>
                @if (session('success'))
                    <div class="notice success">{{ session('success') }}</div>
                    @endif @if ($errors->any())
                        <div class="notice error">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif
                    <form action="{{ route('login') }}" method="POST">@csrf<div class="field"><label
                                for="email">Email</label>
                            <div class="input-wrap"><i data-lucide="mail"></i><input id="email" type="email"
                                    name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                            </div>
                        </div>
                        <div class="field"><label for="password">Mật khẩu</label>
                            <div class="input-wrap"><i data-lucide="lock-keyhole"></i><input id="password"
                                    type="password" name="password" autocomplete="current-password" required></div>
                        </div><label class="options"><input type="checkbox" name="remember" value="1"
                                @checked(old('remember'))>Ghi nhớ đăng nhập</label><button class="submit"
                            type="submit">Đăng nhập</button>
                    </form>
                    {{-- <p class="footer">Chưa có tài khoản? <a href="{{ route('register') }}">Tạo tài khoản</a></p> --}}
            </div>
        </section>
    </main>
    <script>
        lucide.createIcons();
        document.querySelectorAll('.notice').forEach((notice) => window.setTimeout(() => {
            notice.classList.add('is-hiding');
            window.setTimeout(() => notice.remove(), 220);
        }, 5000));
    </script>
</body>

</html>
