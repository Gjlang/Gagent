@extends('layouts.guest')

@section('title', 'Register')

@section('content')
<style>
    /*
     * ---------------------------------------------------------------
     * FULL-VIEWPORT OVERRIDE (same fix as login.blade.php)
     * ---------------------------------------------------------------
     * Breaks this page out of any centered "card" / dark-background
     * wrapper that layouts.guest may impose, and forces it to cover
     * the entire browser viewport.
     */
    html, body {
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        overflow-x: hidden;
    }

    * { box-sizing: border-box; }

    .g-auth-screen {
        position: fixed;
        inset: 0;
        width: 100vw;
        height: 100vh;
        overflow-y: auto;
        overflow-x: hidden;
        background: #ffffff;
        z-index: 1;
    }

    .g-split {
        width: 100%;
        min-height: 100vh;
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto 1fr;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background: #ffffff;
    }

    .g-nav {
        grid-column: 1 / -1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 40px;
        border-bottom: 1px solid #eef0f3;
    }

    .g-nav-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        font-size: 15px;
        color: #1c2333;
    }

    .g-nav-brand-mark {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: #2d5cf6;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }

    .g-nav-links {
        display: flex;
        align-items: center;
        gap: 24px;
        font-size: 14px;
    }

    .g-nav-links a {
        color: #4b5563;
        text-decoration: none;
    }

    .g-nav-links a.active {
        color: #1c2333;
        font-weight: 600;
    }

    .g-form-side {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px;
        width: 100%;
    }

    .g-form-inner {
        width: 100%;
        max-width: 380px;
    }

    .g-form-inner h1 {
        font-size: 32px;
        font-weight: 800;
        color: #1c2333;
        margin: 0 0 14px;
    }

    .g-form-note {
        text-align: center;
        color: #6b7280;
        font-size: 14px;
        margin: 0 0 4px;
    }

    .g-form-note-block {
        margin-bottom: 24px;
    }

    .g-field {
        margin-bottom: 18px;
    }

    .g-field label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #1c2333;
        margin-bottom: 6px;
    }

    .g-field input {
        width: 100%;
        padding: 11px 13px;
        border: 1px solid #dfe3e8;
        border-radius: 8px;
        font-size: 14px;
        color: #1c2333;
        outline: none;
    }

    .g-field input:focus {
        border-color: #2d5cf6;
    }

    .g-error {
        margin-top: 6px;
        font-size: 12px;
        color: #c0392b;
    }

    .g-btn-primary {
        width: 100%;
        padding: 12px 16px;
        border: none;
        border-radius: 8px;
        background: #1c2333;
        color: #ffffff;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        margin-bottom: 12px;
        margin-top: 4px;
    }

    .g-btn-primary:hover {
        background: #10141f;
    }

    .g-footer-note {
        text-align: center;
        font-size: 13px;
        color: #6b7280;
        margin-top: 20px;
    }

    .g-footer-note a {
        color: #1c2333;
        font-weight: 700;
        text-decoration: none;
    }

    .g-visual-side {
        position: relative;
        background: #ffffff;
        display: flex;
        align-items: flex-end;
        overflow: hidden;
        width: 100%;
        height: 100%;
    }

    .g-visual-logo-wrap {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px;
    }

    .g-visual-logo-wrap img {
        max-width: 520px;
        width: 85%;
        height: auto;
    }

    .g-visual-card {
        position: relative;
        width: 100%;
        background: rgba(255, 255, 255, 0.9);
        padding: 40px;
        text-align: center;
    }

    .g-visual-card h2 {
        font-size: 26px;
        font-weight: 800;
        color: #1c2333;
        margin: 0 0 24px;
        line-height: 1.3;
    }

    .g-visual-card p {
        font-size: 13px;
        color: #6b7280;
        margin: 0;
    }

    /*
     * Column order for the Register page is reversed vs. Login:
     * visual panel on the LEFT, form on the RIGHT. This is done via
     * `order` so the markup can stay in the same readable structure
     * (nav, form, visual) while the grid renders visual first.
     */
    .g-visual-side {
        order: 1;
    }

    .g-form-side {
        order: 2;
    }

    @media (max-width: 900px) {
        .g-split {
            grid-template-columns: 1fr;
        }

        .g-visual-side {
            display: none;
        }

        .g-form-side {
            order: 1;
        }
    }
</style>

<div class="g-auth-screen">
    <div class="g-split">
        <div class="g-nav">
            <div class="g-nav-brand">
                <div class="g-nav-brand-mark">G</div>
                GAgent
            </div>

            <div class="g-nav-links">
                <a href="{{ route('register') }}" class="active">Sign Up</a>
                <a href="{{ route('login') }}">Sign In</a>
            </div>
        </div>

        <div class="g-visual-side">
            <div class="g-visual-logo-wrap">
                <img src="{{ asset('images/gagent-logo.png') }}" alt="GAgent logo">
            </div>

            <div class="g-visual-card">
                <h2>Autonomous UX testing, built for developers.</h2>
                <p>GAgent AI UX Testing</p>
            </div>
        </div>

        <div class="g-form-side">
            <div class="g-form-inner">
                <h1>Create an account</h1>

                <div class="g-form-note-block">
                    <p class="g-form-note">Your projects, tests, and reports will be stored under your account.</p>
                </div>

                <form
                    method="POST"
                    action="{{ route('register.store') }}"
                >
                    @csrf

                    <div class="g-field">
                        <label for="name">Full name</label>

                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            autocomplete="name"
                            required
                            autofocus
                        >

                        @error('name')
                            <div class="g-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="g-field">
                        <label for="email">Email Address</label>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            required
                        >

                        @error('email')
                            <div class="g-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="g-field">
                        <label for="password">Password</label>

                        <input
                            id="password"
                            type="password"
                            name="password"
                            autocomplete="new-password"
                            required
                        >

                        @error('password')
                            <div class="g-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="g-field">
                        <label for="password_confirmation">Confirm password</label>

                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <button type="submit" class="g-btn-primary">
                        Create account
                    </button>
                </form>

                <div class="g-footer-note">
                    Already have an account?
                    <a href="{{ route('login') }}">Sign in</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
