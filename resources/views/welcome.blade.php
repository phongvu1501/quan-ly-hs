<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>For My Love ❤️</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,500;1,600&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="style.css">
</head>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    :root {
        --pink: #e8899d;
        --pink-dark: #c9637b;
        --pink-light: #fce7ec;
        --cream: #fffaf8;
        --text: #59464b;
        --muted: #947d83;
    }

    body {
        min-height: 100vh;
        overflow-x: hidden;

        font-family: "Be Vietnam Pro", sans-serif;

        color: var(--text);

        background:
            radial-gradient(circle at 20% 20%,
                rgba(248, 192, 204, 0.5),
                transparent 30%),
            radial-gradient(circle at 80% 80%,
                rgba(239, 211, 218, 0.6),
                transparent 30%),
            linear-gradient(135deg,
                #fff7f5,
                #fcebef,
                #fffaf8);
    }


    /* =========================
   BACKGROUND
========================= */

    .background {
        position: fixed;
        inset: 0;

        overflow: hidden;

        pointer-events: none;
    }

    .blur {
        position: absolute;

        width: 300px;
        height: 300px;

        border-radius: 50%;

        filter: blur(80px);

        opacity: 0.35;
    }

    .blur-1 {
        top: -100px;
        left: -100px;

        background: #f4a7b9;
    }

    .blur-2 {
        right: -100px;
        top: 35%;

        background: #eab9c7;
    }

    .blur-3 {
        bottom: -150px;
        left: 35%;

        background: #f7d2da;
    }


    /* =========================
   MAIN
========================= */

    .love-container {
        position: relative;

        width: 100%;
        min-height: 100vh;

        padding: 70px 20px 40px;

        display: flex;
        align-items: center;
        flex-direction: column;

        z-index: 2;
    }


    /* =========================
   TOP TEXT
========================= */

    .top-text {
        display: flex;
        align-items: center;
        gap: 14px;

        color: var(--pink-dark);

        font-size: 10px;
        font-weight: 600;

        letter-spacing: 4px;
    }

    .line {
        width: 35px;
        height: 1px;

        background: #dda2b1;
    }


    /* =========================
   TITLE
========================= */

    h1 {
        margin-top: 22px;

        text-align: center;

        font-family: "Playfair Display", serif;

        font-size: clamp(38px, 6vw, 65px);

        font-weight: 500;

        line-height: 1.1;

        color: #59464b;
    }

    h1 span {
        display: block;

        color: var(--pink-dark);

        font-style: italic;
    }


    /* =========================
   SUBTITLE
========================= */

    .subtitle {
        margin-top: 18px;

        text-align: center;

        color: var(--muted);

        font-size: 14px;

        line-height: 1.9;

        font-weight: 300;
    }


    /* =========================
   LOVE CARD
========================= */

    .love-card {
        position: relative;

        width: min(520px, 100%);

        margin-top: 42px;

        padding: 48px 35px 42px;

        text-align: center;

        border-radius: 30px;

        background: rgba(255, 255, 255, 0.62);

        border: 1px solid rgba(255, 255, 255, 0.85);

        box-shadow:
            0 25px 80px rgba(163, 101, 115, 0.14),
            inset 0 1px 0 rgba(255, 255, 255, 0.9);

        backdrop-filter: blur(20px);

        transition: 0.5s ease;
    }

    .love-card:hover {
        transform: translateY(-6px);

        box-shadow:
            0 35px 90px rgba(163, 101, 115, 0.2),
            inset 0 1px 0 rgba(255, 255, 255, 0.9);
    }


    /* =========================
   CORNERS
========================= */

    .corner {
        position: absolute;

        width: 35px;
        height: 35px;

        border-color: rgba(210, 116, 138, 0.35);

        border-style: solid;
    }

    .corner-left {
        top: 20px;
        left: 20px;

        border-width: 1px 0 0 1px;

        border-radius: 12px 0 0 0;
    }

    .corner-right {
        right: 20px;
        bottom: 20px;

        border-width: 0 1px 1px 0;

        border-radius: 0 0 12px 0;
    }


    /* =========================
   PHOTO
========================= */

    .photo-wrapper {
        position: relative;

        width: 230px;
        height: 230px;

        margin: 0 auto 30px;

        display: flex;
        align-items: center;
        justify-content: center;
    }


    /* Outer ring */

    .photo-ring {
        position: absolute;

        width: 230px;
        height: 230px;

        border-radius: 50%;

        border: 1px solid rgba(208, 115, 137, 0.3);

        animation: rotate 15s linear infinite;
    }

    .photo-ring::before,
    .photo-ring::after {
        content: "♥";

        position: absolute;

        color: var(--pink);

        font-size: 15px;

        background: #fff8f8;

        padding: 5px;
    }

    .photo-ring::before {
        top: -10px;
        left: 50%;

        transform: translateX(-50%);
    }

    .photo-ring::after {
        bottom: -10px;
        left: 50%;

        transform: translateX(-50%);
    }


    /* Image */

    .photo {
        position: relative;

        width: 195px;
        height: 195px;

        padding: 6px;

        border-radius: 50%;

        background: linear-gradient(135deg,
                #ffffff,
                #f7d7de);

        box-shadow:
            0 15px 40px rgba(192, 93, 117, 0.25);

        z-index: 2;
    }

    .photo img {
        width: 100%;
        height: 100%;

        object-fit: cover;

        border-radius: 50%;

        display: block;

        transition: transform 0.6s ease;
    }

    .photo:hover img {
        transform: scale(1.06);
    }


    /* Badge */

    .heart-badge {
        position: absolute;

        right: 10px;
        bottom: 5px;

        width: 48px;
        height: 48px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 50%;

        color: white;

        background: linear-gradient(135deg,
                #e993a7,
                #cf6b84);

        font-size: 20px;

        box-shadow:
            0 8px 25px rgba(205, 100, 125, 0.35);

        z-index: 4;

        animation: heartbeat 2s infinite;
    }


    /* =========================
   NAME
========================= */

    .love-card h2 {
        font-family: "Playfair Display", serif;

        font-size: 38px;

        font-weight: 500;

        color: #604a50;
    }

    .love-card h2 span {
        color: var(--pink);

        font-size: 23px;

        margin-left: 5px;
    }


    /* =========================
   MESSAGE
========================= */

    .message {
        margin-top: 15px;

        color: #8c757b;

        font-size: 13px;

        line-height: 1.9;

        font-weight: 300;
    }


    /* =========================
   DIVIDER
========================= */

    .divider {
        margin: 25px auto;

        display: flex;
        align-items: center;

        justify-content: center;

        gap: 12px;

        width: 70%;
    }

    .divider span {
        height: 1px;

        flex: 1;

        background: linear-gradient(to right,
                transparent,
                #e6bdc7);
    }

    .divider span:last-child {
        background: linear-gradient(to left,
                transparent,
                #e6bdc7);
    }

    .divider i {
        color: var(--pink);

        font-size: 12px;

        font-style: normal;
    }


    /* =========================
   QUOTE
========================= */

    .quote {
        font-family: "Playfair Display", serif;

        font-size: 17px;

        line-height: 1.8;

        color: #705960;

        font-style: italic;
    }


    /* =========================
   BUTTON
========================= */

    .love-button {
        margin-top: 28px;

        padding: 13px 24px;

        border: 0;

        border-radius: 50px;

        color: white;

        background: linear-gradient(135deg,
                #e79aaa,
                #cc6d84);

        font-family: "Be Vietnam Pro", sans-serif;

        font-size: 12px;

        cursor: pointer;

        box-shadow:
            0 10px 25px rgba(202, 105, 129, 0.25);

        transition:
            transform 0.3s ease,
            box-shadow 0.3s ease;
    }

    .love-button span {
        margin: 0 5px;
    }

    .love-button:hover {
        transform: translateY(-3px) scale(1.03);

        box-shadow:
            0 15px 35px rgba(202, 105, 129, 0.35);
    }

    .love-button:active {
        transform: scale(0.96);
    }


    /* =========================
   HIDDEN MESSAGE
========================= */

    .hidden-message {
        max-height: 0;

        opacity: 0;

        overflow: hidden;

        transform: translateY(-10px);

        transition:
            max-height 0.6s ease,
            opacity 0.5s ease,
            transform 0.5s ease;
    }

    .hidden-message.show {
        max-height: 150px;

        margin-top: 25px;

        opacity: 1;

        transform: translateY(0);
    }

    .hidden-message p {
        font-family: "Playfair Display", serif;

        font-size: 25px;

        color: var(--pink-dark);
    }

    .hidden-message small {
        display: block;

        margin-top: 8px;

        color: var(--muted);

        line-height: 1.7;
    }


    /* =========================
   BOTTOM
========================= */

    .bottom-text {
        margin-top: 25px;

        color: #b18e96;

        font-size: 10px;

        letter-spacing: 1px;
    }

    .bottom-text span {
        color: var(--pink-dark);

        font-size: 13px;
    }


    /* =========================
   FLOATING HEARTS
========================= */

    .hearts span {
        position: fixed;

        bottom: -30px;

        color: rgba(211, 112, 135, 0.35);

        font-size: 18px;

        pointer-events: none;

        animation: floatHeart 12s linear infinite;

        z-index: 1;
    }

    .hearts span:nth-child(1) {
        left: 10%;
        animation-delay: 0s;
    }

    .hearts span:nth-child(2) {
        left: 25%;
        animation-delay: 4s;
    }

    .hearts span:nth-child(3) {
        left: 40%;
        animation-delay: 8s;
    }

    .hearts span:nth-child(4) {
        left: 55%;
        animation-delay: 2s;
    }

    .hearts span:nth-child(5) {
        left: 70%;
        animation-delay: 6s;
    }

    .hearts span:nth-child(6) {
        left: 82%;
        animation-delay: 10s;
    }

    .hearts span:nth-child(7) {
        left: 92%;
        animation-delay: 3s;
    }


    /* =========================
   CLICK HEART
========================= */

    .click-heart {
        position: fixed;

        bottom: 30%;

        color: #e4829a;

        font-size: 20px;

        pointer-events: none;

        z-index: 100;

        animation: clickHeart 2s ease-out forwards;
    }


    /* =========================
   ANIMATIONS
========================= */

    @keyframes rotate {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    @keyframes heartbeat {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.12);
        }
    }

    @keyframes floatHeart {

        0% {
            transform:
                translateY(0) rotate(0deg);

            opacity: 0;
        }

        10% {
            opacity: 1;
        }

        90% {
            opacity: 0.7;
        }

        100% {
            transform:
                translateY(-110vh) rotate(25deg);

            opacity: 0;
        }
    }

    @keyframes clickHeart {

        0% {
            transform:
                translateY(0) scale(0.5);

            opacity: 0;
        }

        20% {
            opacity: 1;
        }

        100% {
            transform:
                translateY(-250px) translateX(calc(-50px + 100px * var(--random)));

            opacity: 0;

        }
    }


    /* =========================
   MOBILE
========================= */

    @media (max-width: 600px) {

        .love-container {
            padding-top: 45px;
        }

        .top-text {
            font-size: 8px;

            letter-spacing: 2px;
        }

        .line {
            width: 20px;
        }

        h1 {
            font-size: 42px;
        }

        .subtitle {
            font-size: 12px;
        }

        .love-card {
            margin-top: 30px;

            padding: 40px 20px 35px;

            border-radius: 25px;
        }

        .photo-wrapper {
            width: 200px;
            height: 200px;
        }

        .photo-ring {
            width: 200px;
            height: 200px;
        }

        .photo {
            width: 170px;
            height: 170px;
        }

        .heart-badge {
            right: 8px;
            bottom: 5px;
        }

        .message {
            font-size: 12px;
        }

        .message br {
            display: none;
        }

        .quote {
            font-size: 15px;
        }
    }
</style>

<body>

    <!-- Background -->
    <div class="background">
        <div class="blur blur-1"></div>
        <div class="blur blur-2"></div>
        <div class="blur blur-3"></div>
    </div>

    <!-- Floating hearts -->
    <div class="hearts">
        <span>♡</span>
        <span>♥</span>
        <span>♡</span>
        <span>♥</span>
        <span>♡</span>
        <span>♥</span>
        <span>♡</span>
    </div>

    <main class="love-container">

        <!-- Small top text -->
        <div class="top-text">
            <span class="line"></span>
            <span>MY SPECIAL PERSON</span>
            <span class="line"></span>
        </div>

        <!-- Heading -->
        <h1>
            Gửi đến
            <span>người anh yêu</span>
        </h1>

        <p class="subtitle">
            Có những người chỉ cần xuất hiện thôi,
            <br>
            cũng đủ khiến một ngày bình thường trở nên thật đặc biệt.
        </p>

        <!-- Main Card -->
        <div class="love-card">

            <!-- Decorative corner -->
            <div class="corner corner-left"></div>
            <div class="corner corner-right"></div>

            <!-- Image -->
            <div class="photo-wrapper">

                <div class="photo-ring"></div>

                <div class="photo">
                    <img src="../../anh/hang.jpg" alt="Người con gái anh yêu">
                </div>

                <div class="heart-badge">
                    ♥
                </div>

            </div>

            <!-- Name -->
            <h2>
                Em
                <span>♡</span>
            </h2>

            <p class="message">
                Cảm ơn em vì đã bước vào cuộc đời anh.
                <br>
                Mong rằng chúng ta sẽ luôn có thật nhiều
                <br>
                những khoảnh khắc đẹp bên nhau.
            </p>

            <!-- Divider -->
            <div class="divider">
                <span></span>
                <i>♥</i>
                <span></span>
            </div>

            <!-- Love quote -->
            <p class="quote">
                “Anh không cần một tình yêu hoàn hảo,
                <br>
                anh chỉ cần một tình yêu có em.”
            </p>

            <!-- Button -->
            <button class="love-button" onclick="showLove()">
                <span>♥</span>
                Một điều anh muốn nói
                <span>♥</span>
            </button>

            <!-- Hidden message -->
            <div class="hidden-message" id="loveMessage">
                <p>
                    Anh yêu em rất nhiều ❤️
                </p>

                <small>
                    Và anh hy vọng chúng ta sẽ cùng nhau
                    viết tiếp thật nhiều câu chuyện đẹp.
                </small>
            </div>

        </div>

        <!-- Bottom -->
        <div class="bottom-text">
            Made with <span>♥</span> for my favorite person
        </div>

    </main>

    <script>
        function showLove() {
            const message = document.getElementById("loveMessage");

            message.classList.toggle("show");

            createHearts();
        }

        function createHearts() {

            for (let i = 0; i < 15; i++) {

                const heart = document.createElement("div");

                heart.className = "click-heart";
                heart.innerHTML = "♥";

                heart.style.left = (45 + Math.random() * 10) + "%";
                heart.style.animationDelay = (Math.random() * 0.5) + "s";

                document.body.appendChild(heart);

                setTimeout(() => {
                    heart.remove();
                }, 2500);
            }
        }
    </script>

</body>

</html>
