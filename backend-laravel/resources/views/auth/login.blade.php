@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<style>
    /*
     * ---------------------------------------------------------------
     * FULL-VIEWPORT OVERRIDE
     * ---------------------------------------------------------------
     * If layouts.guest wraps @yield('content') in its own centered
     * "card" container with a dark background (this is what was
     * causing the narrow mobile-sized card in the middle of a dark
     * screen), the rules below force this auth screen to break out
     * of that wrapper and cover the entire browser viewport, no
     * matter what markup surrounds it.
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

    .g-form-note strong {
        color: #1c2333;
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

    .g-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        font-size: 13px;
    }

    .g-check {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #4b5563;
        cursor: pointer;
    }

    .g-check input {
        width: 15px;
        height: 15px;
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

    @media (max-width: 900px) {
        .g-split {
            grid-template-columns: 1fr;
        }

        .g-visual-side {
            display: none;
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
                <a href="{{ route('register') }}">Sign Up</a>
                <a href="{{ route('login') }}" class="active">Sign In</a>
            </div>
        </div>

        <div class="g-form-side">
            <div class="g-form-inner">
                <h1>Welcome back</h1>

                <div class="g-form-note-block">
                    <p class="g-form-note">Sign in to access your projects, UX test runs, and reports.</p>
                </div>

                <form
                    method="POST"
                    action="{{ route('login.store') }}"
                >
                    @csrf

                    <div class="g-field">
                        <label for="email">Email Address</label>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            required
                            autofocus
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
                            autocomplete="current-password"
                            required
                        >

                        @error('password')
                            <div class="g-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="g-row">
                        <label class="g-check">
                            <input
                                type="checkbox"
                                name="remember"
                                value="1"
                            >
                            <span>Remember me</span>
                        </label>
                    </div>

                    <button type="submit" class="g-btn-primary">
                        Sign in
                    </button>
                </form>

                <div class="g-footer-note">
                    Don't have an account?
                    <a href="{{ route('register') }}">Sign up</a>
                </div>
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
    </div>
</div>
@endsection
