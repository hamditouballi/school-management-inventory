@extends('layouts.app')

@section('title', __('messages.login'))

@section('content')
<style>
/* ---------- Smooth Animated Login (LTR + RTL) ---------- */

/* container */
.login-container {
    background: #f6f5f7;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    min-height: 100vh;
    margin: 0;
    padding: 2rem 1rem;
    position: relative;
    z-index: 1;
    
}

/* card */
.dual-panel-container {
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 18px 35px rgba(2,6,23,0.08), 0 10px 10px rgba(2,6,23,0.04);
    position: relative;
    z-index: 5;
    overflow: hidden;
    width: 768px;
    max-width: 100%;
    min-height: 480px;

    /* smoother overall transition hint */
    will-change: transform;
}

/* forms */
.form-container {
    position: absolute;
    top: 0;
    height: 100%;
    transition: transform 700ms cubic-bezier(.2,.8,.2,1), opacity 450ms ease;
    will-change: transform, opacity;
}

/* default LTR positions */
.manager-container {
    left: 0;
    width: 50%;
    z-index: 2;
    transform: translate3d(0,0,0);
    opacity: 1;
}

.administrative-container {
    left: 0;
    width: 50%;
    opacity: 0;
    z-index: 1;
    transform: translate3d(0,0,0);
}

/* show administrative by sliding manager out to the right and bring administrative forward */
.dual-panel-container.right-panel-active .manager-container {
    transform: translate3d(100%,0,0);
    opacity: 0;
}

.dual-panel-container.right-panel-active .administrative-container {
    transform: translate3d(100%,0,0);
    opacity: 1;
    z-index: 5;
    /* slight delay for smoother feel */
    transition-delay: 60ms;
}

/* ease keyframes removed — transitions handle it now */

/* overlay container */
.overlay-container {
    position: absolute;
    top: 0;
    left: 50%;
    width: 50%;
    height: 100%;
    overflow: hidden;
    transition: transform 700ms cubic-bezier(.2,.8,.2,1);
    z-index: 100;
    will-change: transform, opacity, background;
}
.dual-panel-container.right-panel-active .overlay-container {
    transform: translate3d(-100%,0,0);
}

/* overlay background (color switching via classes .green / .red) */
.overlay {
    /* default: green gradient (manager) */
    background: linear-gradient(135deg, #15a34a 0%, #15803d 100%);
    color: #FFFFFF;
    position: relative;
    left: -100%;
    height: 100%;
    width: 200%;
    transform: translate3d(0,0,0);
    transition: transform 700ms cubic-bezier(.2,.8,.2,1), background 420ms ease;
    will-change: transform, background;
    backface-visibility: hidden;
}

/* explicit variants */
.overlay.green {
    background: linear-gradient(135deg, #15a34a 0%, #15803d 100%);
}
.overlay.red {
    background: linear-gradient(135deg, #d33b2f 0%, #b82a1f 100%);
}

/* overlay slide in when active */
.dual-panel-container.right-panel-active .overlay {
    transform: translate3d(50%,0,0);
}

/* overlay panels (content blocks) */
.overlay-panel {
    position: absolute;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    padding: 0 40px;
    text-align: center;
    top: 0;
    height: 100%;
    width: 50%;
    transform: translate3d(0,0,0);
    transition: transform 700ms cubic-bezier(.2,.8,.2,1), opacity 450ms ease;
    will-change: transform, opacity;
    pointer-events: auto; /* keep buttons clickable */
}

.overlay-left {
    transform: translate3d(-20%,0,0);
    left: 0;
}
.dual-panel-container.right-panel-active .overlay-left {
    transform: translate3d(0,0,0);
}

.overlay-right {
    right: 0;
    transform: translate3d(0,0,0);
}
.dual-panel-container.right-panel-active .overlay-right {
    transform: translate3d(20%,0,0);
}

/* login forms visuals */
.login-form {
    background-color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    padding: 0 50px;
    height: 100%;
    text-align: center;
    transition: padding 300ms ease;
}
.login-form h2 {
    font-weight: 700;
    margin-bottom: 1.25rem;
    color: #0f172a;
    transition: color 300ms ease;
}

/* inputs */
.login-form input {
    background-color: #eef2ff;
    border: none;
    padding: 12px 15px;
    margin: 8px 0;
    width: 100%;
    border-radius: 8px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.6);
    font-size: 16px;
    transition: background 200ms ease, box-shadow 200ms ease;
}
.login-form input:focus {
    outline: 2px solid #16a34a;
    background-color: #fff;
}

/* buttons */
.login-form button {
    border-radius: 9999px;
    border: 1px solid #16a34a;
    background-color: #16a34a;
    color: #FFFFFF;
    font-size: 14px;
    font-weight: 700;
    padding: 10px 38px;
    transition: transform 120ms cubic-bezier(.2,.8,.2,1), background-color 200ms ease;
    cursor: pointer;
    margin-top: 1rem;
    will-change: transform, background-color;
}
.login-form button:hover { transform: translateY(-3px); background-color: #15803d; }
.login-form button:active { transform: translateY(0); }

/* ghost button */
.ghost-btn {
    background-color: transparent;
    color: #FFFFFF;
    border: 2px solid #FFFFFF;
    padding: 12px 36px;
    border-radius: 9999px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: transform 120ms cubic-bezier(.2,.8,.2,1), opacity 200ms ease;
    margin-top: 1rem;
}
.ghost-btn:hover { transform: translateY(-3px); opacity: 0.95; }

/* overlay text */
.overlay-panel h1 {
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    transition: transform 420ms cubic-bezier(.2,.8,.2,1), opacity 420ms ease;
}
.overlay-panel p {
    font-size: 14px;
    font-weight: 300;
    line-height: 1.75;
    margin: 12px 0 0;
}

/* errors & demo */
.error-message { color: #dc2626; font-size: 0.875rem; margin-top: 0.25rem; }
/* .demo-credentials {
    margin-top: 1rem;
    padding: 1rem;
    background-color: #f8fafc;
    border-radius: 8px;
    font-size: 0.85rem;
    color: #374151;
    text-align: left;
    width: 100%;
} */

/* ---------- RTL tweaks (text alignment and positioning) ---------- */
[dir="rtl"] .manager-container,
[dir="rtl"] .administrative-container {
    left: auto;
    right: 0;
}

[dir="rtl"] .overlay-container { left: auto; right: 50%; }
[dir="rtl"] .overlay { left: auto; right: -100%; }

[dir="rtl"] .overlay-left { left: auto; right: 0; transform: translate3d(20%,0,0); }
[dir="rtl"] .overlay-right { right: auto; left: 0; transform: translate3d(0,0,0); }

[dir="rtl"] .dual-panel-container.right-panel-active .manager-container { transform: translate3d(-100%,0,0); }
[dir="rtl"] .dual-panel-container.right-panel-active .administrative-container { transform: translate3d(-100%,0,0); }
[dir="rtl"] .dual-panel-container.right-panel-active .overlay-container { transform: translate3d(100%,0,0); }
[dir="rtl"] .dual-panel-container.right-panel-active .overlay { transform: translate3d(-50%,0,0); }

[dir="rtl"] .overlay-panel,
[dir="rtl"] .login-form,
[dir="rtl"] .demo-credentials { text-align: right; }

/* ---------- Responsive ---------- */
@media (max-width: 720px) {
    .dual-panel-container { width: 100%; min-height: 640px; }
    .overlay { display: none; } /* simpler layout on phones */
    .manager-container, .administrative-container {
        position: relative; width: 100%; left: 0; right: 0; opacity: 1; transform: none !important;
    }
    .administrative-container { margin-top: 18px; }
    .login-form { padding: 20px; }
    .demo-credentials { text-align: left; }
    [dir="rtl"] .demo-credentials { text-align: right; }
}



/* ---------- Animated Blurry Orbs Background ---------- */

.bg-orbs {
    position: fixed;
    inset: 0;
    z-index: 0;
    pointer-events: none;
}

/* shared orb style */
.orb {
    position: absolute;
    width: 420px;
    height: 420px;
    border-radius: 50%;
    filter: blur(90px);
    opacity: 0.55;
    animation: orbit 22s linear infinite;
}

/* green orb */
.orb-green {
    background: radial-gradient(circle at 30% 30%, #22c55e, #15803d);
    animation-delay: 0s;
}

/* red orb */
.orb-red {
    background: radial-gradient(circle at 30% 30%, #ef4444, #b91c1c);
    animation-delay: -11s;
}

/* orbit animation around center */
@keyframes orbit {
    0% {
        transform: translate(-50%, -50%) rotate(0deg) translateX(320px);
        top: 50%;
        left: 50%;
    }
    100% {
        transform: translate(-50%, -50%) rotate(360deg) translateX(320px);
        top: 50%;
        left: 50%;
    }
}

/* make sure card stays above background */
.dual-panel-container {
    position: relative;
    z-index: 2;
}

.login-container {
    position: relative;
}
</style>

@php $locale = app()->getLocale(); @endphp

<div class="login-container" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
    <div class="bg-orbs">
<span class="orb orb-green"></span>
<span class="orb orb-red"></span>
</div>
    <div class="dual-panel-container" id="container">
        <!-- Manager Login Form -->
        <div class="form-container manager-container" id="managerContainer">
            <form method="POST" action="{{ route('login.post') }}" class="login-form" autocomplete="on">
                @csrf
                <input type="hidden" name="user_type" value="manager">
                <h2>{{ __('messages.manager_login') }}</h2>

                <input type="text" name="username" placeholder="{{ __('messages.username') }}" value="{{ old('username') }}" required autocomplete="username">
                @error('username') <p class="error-message">{{ $message }}</p> @enderror

                <input type="password" name="password" placeholder="{{ __('messages.password') }}" required autocomplete="current-password">
                @error('password') <p class="error-message">{{ $message }}</p> @enderror

                <button type="submit">{{ __('messages.sign_in') }}</button>

                {{-- <div class="demo-credentials" aria-hidden="true">
                    <strong>{{ __('messages.demo') }}:</strong><br>
                    <span><strong>HR:</strong> hr_manager / password</span><br>
                    <span><strong>{{ __('messages.stock_manager') }}:</strong> stock_manager / password</span><br>
                    <span><strong>{{ __('messages.finance_manager') }}:</strong> finance_manager / password</span>
                </div> --}}
            </form>
        </div>

        <!-- administrative Login Form -->
        <div class="form-container administrative-container" id="administrativeContainer">
            <form method="POST" action="{{ route('login.post') }}" class="login-form" autocomplete="on">
                @csrf
                <input type="hidden" name="user_type" value="administrative">
                <h2>{{ __('messages.administrative_login') }}</h2>

                <input type="text" name="username" placeholder="{{ __('messages.username') }}" value="{{ old('username') }}" required autocomplete="username">
                @error('username') <p class="error-message">{{ $message }}</p> @enderror

                <input type="password" name="password" placeholder="{{ __('messages.password') }}" required autocomplete="current-password">
                @error('password') <p class="error-message">{{ $message }}</p> @enderror

                <button type="submit">{{ __('messages.sign_in') }}</button>

                {{-- <div class="demo-credentials" aria-hidden="true">
                    <strong>{{ __('messages.demo') }}:</strong> administrative_nursery / password
                </div> --}}
            </form>
        </div>

        <!-- Overlay -->
        <div class="overlay-container" id="overlayContainer" aria-hidden="true">
            <div class="overlay green" id="overlayElement" role="presentation">
                <div class="overlay-panel overlay-left" id="overlayLeft">
                    <h1>{{ __('messages.welcome_back') }}</h1>
                    <p>{{ __('messages.manager_portal') }}</p>
                    <button class="ghost-btn" id="signIn">{{ __('messages.manager_login_btn') }}</button>
                </div>
                <div class="overlay-panel overlay-right" id="overlayRight">
                    <h1>{{ __('messages.hello_administrative') }}</h1>
                    <p>{{ __('messages.administrative_portal') }}</p>
                    <button class="ghost-btn" id="signUp">{{ __('messages.administrative_login_btn') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    const container = document.getElementById('container');
    const overlayEl = document.getElementById('overlayElement');
    const signUpButton = document.getElementById('signUp');   // administrative
    const signInButton = document.getElementById('signIn');   // manager

    // smoother toggle: use requestAnimationFrame to apply class changes so the browser can animate well
    function addRightActive() {
        // ensure any layout work is applied before adding class
        window.requestAnimationFrame(() => {
            container.classList.add('right-panel-active');
        });
    }
    function removeRightActive() {
        window.requestAnimationFrame(() => {
            container.classList.remove('right-panel-active');
        });
    }

    // set overlay color (green or red) with a small fade using CSS background transition
    function setOverlayColor(color) {
        if (!overlayEl) return;
        overlayEl.classList.remove('green','red');
        // small timeout so transform & color transitions don't fight when toggling quickly
        window.requestAnimationFrame(() => {
            overlayEl.classList.add(color === 'red' ? 'red' : 'green');
        });
    }

    // click handlers (preserve LTR/RTL functionality because transforms are symmetric)
    signUpButton.addEventListener('click', (e) => {
        e.preventDefault();
        addRightActive();   // show administrative
        setOverlayColor('red');
    });

    signInButton.addEventListener('click', (e) => {
        e.preventDefault();
        removeRightActive(); // show manager
        setOverlayColor('green');
    });

    // init state: if server returned old('user_type') === 'administrative', show administrative
    @if(old('user_type') === 'administrative')
        setOverlayColor('red');
        addRightActive();
    @else
        setOverlayColor('green');
    @endif

    // Accessibility: when transitions end, set aria-hidden appropriately for panels
    container.addEventListener('transitionend', (ev) => {
        // If the class is present then administrative is visible
        const isRight = container.classList.contains('right-panel-active');
        const manager = document.getElementById('managerContainer');
        const administrative = document.getElementById('administrativeContainer');
        if (manager) manager.setAttribute('aria-hidden', isRight ? 'true' : 'false');
        if (administrative) administrative.setAttribute('aria-hidden', isRight ? 'false' : 'true');
    });

    // initial aria-hidden state
    (function setInitialAria() {
        const isRight = container.classList.contains('right-panel-active');
        const manager = document.getElementById('managerContainer');
        const administrative = document.getElementById('administrativeContainer');
        if (manager) manager.setAttribute('aria-hidden', isRight ? 'true' : 'false');
        if (administrative) administrative.setAttribute('aria-hidden', isRight ? 'false' : 'true');
    })();
})();
</script>
@endsection
