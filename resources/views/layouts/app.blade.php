<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @yield('title', 'Dashboard') - {{ config('app.name', 'Transport Management') }}
    </title>

    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">

    @stack('styles')


    {{-- ==========================================================
         LAYOUT OVERRIDES
         ========================================================== --}}
<style>

    /* ==========================================================
       GLOBAL RESET & BASE
       ========================================================== */

    *,
    *::before,
    *::after {
        box-sizing: border-box;
    }

    html,
    body {
        margin: 0 !important;
        padding: 0 !important;

        width: 100%;
        min-width: 100%;
        min-height: 100%;

        font-family:
            Inter,
            ui-sans-serif,
            system-ui,
            -apple-system,
            BlinkMacSystemFont,
            "Segoe UI",
            sans-serif;

        -webkit-font-smoothing: antialiased;
        text-rendering: optimizeLegibility;
    }

    body {
        overflow-x: hidden;
        background: #f4f7fb;
    }


    /* ==========================================================
       APPLICATION WRAPPER
       ========================================================== */

    .app {
        display: flex !important;

        width: 100% !important;
        max-width: 100% !important;

        height: 100vh;
        min-height: 100vh;

        margin: 0 !important;
        padding: 0 !important;

        overflow: hidden;

        background: #f4f7fb;
    }


    /* ==========================================================
       SIDEBAR
       ========================================================== */

    .sidebar {
        width: 250px !important;
        min-width: 250px !important;
        max-width: 250px !important;

        height: 100vh !important;
        min-height: 100vh !important;
        max-height: 100vh !important;

        flex: 0 0 250px !important;

        position: relative !important;
        top: 0 !important;
        left: 0 !important;

        margin: 0 !important;

        display: flex;
        flex-direction: column;

        overflow: hidden !important;

        background:
            linear-gradient(
                180deg,
                #111a2b 0%,
                #0d1626 55%,
                #0b1423 100%
            );

        color: #ffffff;

        border-right: 1px solid rgba(255, 255, 255, 0.06);

        box-shadow:
            4px 0 18px rgba(15, 23, 42, 0.10);

        z-index: 1000;
    }


    /* ==========================================================
       SIDEBAR BRAND
       ========================================================== */

    .sidebar-brand {
        width: 100%;

        height: 74px;
        min-height: 74px;

        flex: 0 0 74px;

        display: flex;
        align-items: center;

        padding: 12px 14px;

        border-bottom: 1px solid rgba(255, 255, 255, 0.08);

        background:
            linear-gradient(
                180deg,
                rgba(255, 255, 255, 0.035),
                rgba(255, 255, 255, 0)
            );
    }


    /* Logo box */

    .sidebar-brand-icon {
        width: 42px;
        height: 42px;

        min-width: 42px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 11px;

        font-size: 22px;

        background:
            linear-gradient(
                135deg,
                #2563eb 0%,
                #3b82f6 100%
            );

        box-shadow:
            0 5px 14px rgba(37, 99, 235, 0.28);

        border: 1px solid rgba(255, 255, 255, 0.10);
    }


    /* Application title */

    .sidebar-brand-text {
        margin-left: 11px;

        font-size: 16px;
        line-height: 20px;

        font-weight: 750;

        letter-spacing: -0.2px;

        color: #ffffff;

        white-space: normal;
    }


    /* ==========================================================
       SIDEBAR NAVIGATION
       ========================================================== */

    .sidebar-nav {
        width: 100% !important;

        height: calc(100vh - 74px);

        min-height: 0;

        flex: 1 1 auto;

        display: flex !important;
        flex-direction: column !important;

        padding: 8px 9px 10px 9px !important;

        margin: 0 !important;

        overflow: hidden !important;
    }


    /* ==========================================================
       NAVIGATION SECTION HEADINGS
       MAIN / OPERATIONS / FINANCE / REPORTS / ...
       ========================================================== */

    .nav-section {
        width: 100%;

        flex: 0 0 auto;

        margin: 0 !important;

        padding: 9px 11px 5px 11px !important;

        font-size: 11px !important;
        line-height: 14px !important;

        font-weight: 750 !important;

        color: #91a0b8;

        text-transform: uppercase;

        letter-spacing: 0.95px;

        user-select: none;
    }


    /* ==========================================================
       MENU ITEM
       ========================================================== */

    .sidebar-nav .nav-item {
        width: 100% !important;

        height: 40px;
        min-height: 40px;

        flex: 0 0 40px;

        display: flex !important;
        align-items: center !important;

        margin: 1px 0 !important;

        padding: 8px 11px !important;

        border: 1px solid transparent;
        border-radius: 8px;

        background: transparent;

        color: #d6deea;

        text-decoration: none;

        font-size: 15px !important;
        line-height: 20px !important;

        font-weight: 500;

        letter-spacing: -0.05px;

        cursor: pointer;

        transition:
            background-color 0.16s ease,
            color 0.16s ease,
            border-color 0.16s ease,
            transform 0.16s ease,
            box-shadow 0.16s ease;
    }


    /* ==========================================================
       MENU HOVER
       ========================================================== */

    .sidebar-nav .nav-item:hover {
        color: #ffffff;

        background:
            rgba(255, 255, 255, 0.075);

        border-color:
            rgba(255, 255, 255, 0.055);

        transform: translateX(1px);
    }


    /* ==========================================================
       ACTIVE MENU ITEM
       ========================================================== */

    .sidebar-nav .nav-item.active {
        color: #ffffff !important;

        background:
            linear-gradient(
                135deg,
                #2563eb 0%,
                #3267df 100%
            ) !important;

        border-color:
            rgba(255, 255, 255, 0.08) !important;

        box-shadow:
            0 5px 12px rgba(37, 99, 235, 0.25);

        font-weight: 650;
    }


    /* Active hover */

    .sidebar-nav .nav-item.active:hover {
        background:
            linear-gradient(
                135deg,
                #2b6df3 0%,
                #3970e9 100%
            ) !important;

        transform: translateX(0);
    }


    /* ==========================================================
       NAVIGATION ICON
       ========================================================== */

    .sidebar-nav .nav-icon {
        width: 30px !important;
        min-width: 30px !important;

        height: 22px;

        margin-right: 7px !important;

        display: inline-flex;

        align-items: center;
        justify-content: center;

        font-size: 17px !important;
        line-height: 1;

        color: #b8c5d8;

        transition:
            color 0.16s ease,
            transform 0.16s ease;
    }


    /* Icon on hover */

    .sidebar-nav .nav-item:hover .nav-icon {
        color: #ffffff;

        transform: scale(1.04);
    }


    /* Icon when active */

    .sidebar-nav .nav-item.active .nav-icon {
        color: #ffffff !important;
    }


    /* ==========================================================
       LOGOUT FORM
       ========================================================== */

    .sidebar-nav form {
        width: 100%;

        margin: 0 !important;
        padding: 0 !important;
    }

    .sidebar-nav form .nav-item {
        width: 100% !important;

        font-family: inherit;

        font-size: 15px !important;
        line-height: 20px !important;

        font-weight: 500;

        text-align: left;

        border: 1px solid transparent;

        outline: none;

        appearance: none;

        -webkit-appearance: none;
    }

    .sidebar-nav form .nav-item:focus-visible {
        outline: 2px solid rgba(96, 165, 250, 0.7);
        outline-offset: 2px;
    }


    /* ==========================================================
       SIDEBAR SCROLLBAR
       Completely hidden
       ========================================================== */

    .sidebar,
    .sidebar *,
    .sidebar-nav,
    .sidebar-nav * {
        scrollbar-width: none !important;
    }

    .sidebar::-webkit-scrollbar,
    .sidebar *::-webkit-scrollbar,
    .sidebar-nav::-webkit-scrollbar,
    .sidebar-nav *::-webkit-scrollbar {
        width: 0 !important;
        height: 0 !important;

        display: none !important;
    }


    /* ==========================================================
       SIDEBAR OVERLAY
       ========================================================== */

    .sidebar-overlay {
        display: none;
    }


    /* ==========================================================
       MAIN AREA
       ========================================================== */

    .main {
        position: relative !important;

        width: calc(100vw - 250px) !important;
        max-width: calc(100vw - 250px) !important;

        min-width: 0 !important;

        height: 100vh;
        min-height: 100vh;

        margin: 0 !important;
        margin-left: 0 !important;

        padding: 0 !important;
        padding-left: 0 !important;

        flex: 1 1 auto !important;

        display: flex;

        flex-direction: column;

        overflow: hidden;

        background: #f4f7fb;
    }


    /* ==========================================================
       TOPBAR
       ========================================================== */

    .topbar {
        width: 100% !important;

        min-height: 64px;

        flex: 0 0 64px;

        display: flex;

        align-items: center;
        justify-content: space-between;

        margin: 0 !important;

        padding: 0 24px;

        background: rgba(255, 255, 255, 0.97);

        border-bottom: 1px solid #e5eaf1;

        box-shadow:
            0 1px 5px rgba(15, 23, 42, 0.04);

        box-sizing: border-box;

        z-index: 20;
    }


    /* ==========================================================
       TOPBAR LEFT
       ========================================================== */

    .topbar-left {
        display: flex;

        align-items: center;

        min-width: 0;
    }


    /* ==========================================================
       SIDEBAR TOGGLE
       ========================================================== */

    .sidebar-toggle {
        width: 38px;
        height: 38px;

        display: none;

        align-items: center;
        justify-content: center;

        margin-right: 12px;

        border: 1px solid #e2e8f0;

        border-radius: 8px;

        background: #ffffff;

        color: #334155;

        font-size: 18px;

        cursor: pointer;

        transition:
            background-color 0.15s ease,
            border-color 0.15s ease,
            color 0.15s ease;
    }

    .sidebar-toggle:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #1e3a8a;
    }


    /* ==========================================================
       PAGE TITLE IN TOPBAR
       ========================================================== */

    .page-title {
        margin: 0 !important;

        padding: 0 !important;

        font-size: 18px !important;
        line-height: 24px !important;

        font-weight: 700 !important;

        color: #1e293b;

        letter-spacing: -0.25px;
    }


    /* ==========================================================
       TOPBAR RIGHT
       ========================================================== */

    .topbar-right {
        display: flex;

        align-items: center;

        margin-left: auto;
    }


    /* ==========================================================
       USER CHIP
       ========================================================== */

    .user-chip {
        display: flex;

        align-items: center;

        gap: 9px;

        padding: 5px 10px 5px 5px;

        border: 1px solid #e5eaf1;

        border-radius: 999px;

        background: #ffffff;

        color: #334155;

        font-size: 13px;

        font-weight: 600;

        box-shadow:
            0 2px 6px rgba(15, 23, 42, 0.04);
    }


    /* ==========================================================
       USER AVATAR
       ========================================================== */

    .user-avatar {
        width: 31px;
        height: 31px;

        min-width: 31px;

        display: flex;

        align-items: center;
        justify-content: center;

        border-radius: 50%;

        background:
            linear-gradient(
                135deg,
                #2563eb,
                #3b82f6
            );

        color: #ffffff;

        font-size: 13px;

        font-weight: 700;

        box-shadow:
            0 2px 6px rgba(37, 99, 235, 0.22);
    }


    /* ==========================================================
       CONTENT AREA
       ========================================================== */

    .content {
        width: 100% !important;
        max-width: 100% !important;

        min-width: 0;

        flex: 1 1 auto;

        min-height: 0;

        margin: 0 !important;

        padding: 26px 24px 30px 24px;

        box-sizing: border-box;

        overflow-y: auto;
        overflow-x: hidden;

        background:
            linear-gradient(
                180deg,
                #f7f9fc 0%,
                #f4f7fb 100%
            );
    }


    /* ==========================================================
       CONTENT SCROLLBAR
       Keep main page scrollbar clean
       ========================================================== */

    .content {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }

    .content::-webkit-scrollbar {
        width: 8px;
    }

    .content::-webkit-scrollbar-track {
        background: transparent;
    }

    .content::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 20px;
    }

    .content::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }


    /* ==========================================================
       REMOVE OLD WIDTH / MARGIN LIMITS
       ========================================================== */

    .main > * {
        max-width: 100%;
    }

    .main > .content {
        width: 100% !important;
    }


    /* ==========================================================
       COMMON CARD POLISH
       Makes existing dashboard cards/forms look cleaner
       ========================================================== */

    .content .card,
    .content .panel,
    .content .table-card,
    .content .stat-card {
        border-radius: 10px;
    }


    /* ==========================================================
       BUTTON POLISH
       Does not override color aggressively
       ========================================================== */

    .content button,
    .content .btn {
        border-radius: 7px;
        transition:
            transform 0.15s ease,
            box-shadow 0.15s ease,
            background-color 0.15s ease;
    }

    .content button:hover,
    .content .btn:hover {
        transform: translateY(-1px);
    }


    /* ==========================================================
       FORM INPUT POLISH
       ========================================================== */

    .content input,
    .content select,
    .content textarea {
        border-radius: 7px;
    }

    .content input:focus,
    .content select:focus,
    .content textarea:focus {
        outline: none;
    }


    /* ==========================================================
       DESKTOP
       ========================================================== */

    @media (min-width: 769px) {

        .sidebar {
            position: relative !important;

            margin-left: 0 !important;
        }

        .main {
            margin-left: 0 !important;

            padding-left: 0 !important;

            width: calc(100vw - 250px) !important;
            max-width: calc(100vw - 250px) !important;
        }

        .sidebar-toggle {
            display: none;
        }
    }


    /* ==========================================================
       SHORT DESKTOP SCREEN
       800px height or less
       ========================================================== */

    @media (min-width: 769px) and (max-height: 800px) {

        .sidebar-brand {
            height: 66px;
            min-height: 66px;

            flex-basis: 66px;

            padding: 9px 13px;
        }

        .sidebar-brand-icon {
            width: 38px;
            height: 38px;

            min-width: 38px;

            font-size: 20px;
        }

        .sidebar-brand-text {
            font-size: 15px;
            line-height: 19px;
        }

        .sidebar-nav {
            height: calc(100vh - 66px);

            padding-top: 5px !important;
            padding-bottom: 7px !important;
        }

        .nav-section {
            padding-top: 6px !important;
            padding-bottom: 3px !important;

            font-size: 10px !important;
            line-height: 13px !important;
        }

        .sidebar-nav .nav-item {
            height: 37px;
            min-height: 37px;

            flex-basis: 37px;

            padding-top: 7px !important;
            padding-bottom: 7px !important;

            font-size: 14px !important;
            line-height: 19px !important;
        }

        .sidebar-nav .nav-icon {
            font-size: 16px !important;
        }

        .topbar {
            min-height: 60px;
            flex-basis: 60px;

            padding-left: 20px;
            padding-right: 20px;
        }

        .content {
            padding: 22px 20px 26px 20px;
        }
    }


    /* ==========================================================
       VERY SHORT DESKTOP SCREEN
       ========================================================== */

    @media (min-width: 769px) and (max-height: 650px) {

        .sidebar-brand {
            height: 58px;
            min-height: 58px;

            flex-basis: 58px;

            padding: 7px 12px;
        }

        .sidebar-brand-icon {
            width: 34px;
            height: 34px;

            min-width: 34px;

            border-radius: 9px;

            font-size: 18px;
        }

        .sidebar-brand-text {
            font-size: 14px;
            line-height: 17px;
        }

        .sidebar-nav {
            height: calc(100vh - 58px);

            padding-top: 3px !important;
            padding-bottom: 5px !important;
        }

        .nav-section {
            padding-top: 3px !important;
            padding-bottom: 2px !important;

            font-size: 9px !important;
            line-height: 11px !important;

            letter-spacing: 0.7px;
        }

        .sidebar-nav .nav-item {
            height: 33px;
            min-height: 33px;

            flex-basis: 33px;

            padding-top: 5px !important;
            padding-bottom: 5px !important;

            border-radius: 7px;

            font-size: 13px !important;
            line-height: 17px !important;
        }

        .sidebar-nav .nav-icon {
            width: 27px !important;
            min-width: 27px !important;

            font-size: 15px !important;
        }

        .topbar {
            min-height: 56px;
            flex-basis: 56px;

            padding-left: 18px;
            padding-right: 18px;
        }

        .page-title {
            font-size: 17px !important;
        }

        .content {
            padding: 18px 18px 22px 18px;
        }
    }


    /* ==========================================================
       TABLET / MOBILE
       ========================================================== */

    @media (max-width: 768px) {

        html,
        body {
            width: 100%;
            min-width: 100%;
        }

        body {
            overflow-x: hidden;
        }

        .app {
            display: block !important;

            width: 100% !important;

            min-height: 100vh;

            height: auto;

            overflow: visible;
        }


        /* Mobile sidebar */

        .sidebar {
            width: 250px !important;
            min-width: 250px !important;
            max-width: 250px !important;

            height: 100vh !important;
            max-height: 100vh !important;

            position: fixed !important;

            top: 0;
            left: 0;

            transform: translateX(-100%);

            transition:
                transform 0.22s ease;

            overflow: hidden !important;

            z-index: 1000;
        }


        /* When JS adds open class */

        .sidebar.open {
            transform: translateX(0);
        }


        /* Overlay */

        .sidebar-overlay {
            display: block;

            position: fixed;

            inset: 0;

            background: rgba(15, 23, 42, 0.42);

            opacity: 0;
            visibility: hidden;

            transition:
                opacity 0.22s ease,
                visibility 0.22s ease;

            z-index: 999;
        }

        .sidebar-overlay.active {
            opacity: 1;

            visibility: visible;
        }


        /* Main */

        .main {
            width: 100% !important;
            max-width: 100% !important;

            height: auto;
            min-height: 100vh;

            margin: 0 !important;

            padding: 0 !important;

            overflow: visible;
        }


        /* Topbar */

        .topbar {
            width: 100% !important;

            min-height: 60px;
            height: 60px;

            padding:
                0 14px;

            position: sticky;

            top: 0;

            z-index: 50;
        }

        .sidebar-toggle {
            display: flex;

            width: 36px;
            height: 36px;

            margin-right: 10px;
        }

        .page-title {
            font-size: 17px !important;
        }


        /* User chip */

        .user-chip {
            padding-right: 5px;

            border: 0;

            box-shadow: none;

            background: transparent;
        }

        .user-chip > span {
            display: none;
        }


        /* Content */

        .content {
            width: 100% !important;

            padding:
                18px 14px 24px 14px;

            min-height: calc(100vh - 60px);

            overflow-y: visible;

            background: #f4f7fb;
        }
    }


    /* ==========================================================
       SMALL MOBILE
       ========================================================== */

    @media (max-width: 480px) {

        .sidebar {
            width: 235px !important;
            min-width: 235px !important;
            max-width: 235px !important;
        }

        .topbar {
            padding-left: 10px;
            padding-right: 10px;
        }

        .content {
            padding-left: 10px;
            padding-right: 10px;
        }

        .page-title {
            font-size: 16px !important;
        }
    }


    /* ==========================================================
       ACCESSIBILITY
       ========================================================== */

    @media (prefers-reduced-motion: reduce) {

        *,
        *::before,
        *::after {
            scroll-behavior: auto !important;

            transition-duration: 0.01ms !important;
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
        }
    }


    /* ==========================================================
       PRINT
       ========================================================== */

    @media print {

        .sidebar {
            display: none !important;
        }

        .main {
            width: 100% !important;
            max-width: 100% !important;

            margin: 0 !important;
        }

        .topbar {
            box-shadow: none !important;
        }

        .content {
            overflow: visible !important;

            padding: 0 !important;

            background: #ffffff !important;
        }
    }

</style>

</head>


<body>

<div class="app">

    {{-- ==========================================================
         SIDEBAR OVERLAY
         ========================================================== --}}
    <div class="sidebar-overlay"></div>


    {{-- ==========================================================
         SIDEBAR
         ========================================================== --}}
    <aside class="sidebar">

        {{-- BRAND --}}
        <div class="sidebar-brand">

            <div class="sidebar-brand-icon">
                🚍
            </div>

            <div class="sidebar-brand-text">
                Transport Management
            </div>

        </div>


        {{-- NAVIGATION --}}
        <nav class="sidebar-nav">


            {{-- ==================================================
                 MAIN
                 ================================================== --}}
            <div class="nav-section">
                Main
            </div>

            <a href="{{ route('dashboard') }}"
               class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">

                <span class="nav-icon">▦</span>

                <span>Dashboard</span>

            </a>


            {{-- ==================================================
                 OPERATIONS
                 ================================================== --}}
            <div class="nav-section">
                Operations
            </div>

            <a href="{{ route('buses.index') }}"
               class="nav-item {{ request()->routeIs('buses.*') ? 'active' : '' }}">

                <span class="nav-icon">🚌</span>

                <span>Buses</span>

            </a>

            <a href="{{ route('students.index') }}"
               class="nav-item {{ request()->routeIs('students.*') ? 'active' : '' }}">

                <span class="nav-icon">👨‍🎓</span>

                <span>Students</span>

            </a>


            {{-- ==================================================
                 FINANCE
                 ================================================== --}}
            <div class="nav-section">
                Finance
            </div>

            <a href="{{ route('fees.generate') }}"
               class="nav-item {{ request()->routeIs('fees.*') ? 'active' : '' }}">

                <span class="nav-icon">₹</span>

                <span>Fees</span>

            </a>

            <a href="{{ route('payments.index') }}"
               class="nav-item {{ request()->routeIs('payments.*') ? 'active' : '' }}">

                <span class="nav-icon">✓</span>

                <span>Payments</span>

            </a>

            <a href="{{ route('expenses.index') }}"
               class="nav-item {{ request()->routeIs('expenses.*') ? 'active' : '' }}">

                <span class="nav-icon">↘</span>

                <span>Expenses</span>

            </a>


            {{-- ==================================================
                 REPORTS
                 ================================================== --}}
            <div class="nav-section">
                Reports
            </div>

            <a href="{{ route('reports') }}"
               class="nav-item {{ request()->routeIs('reports') ? 'active' : '' }}">

                <span class="nav-icon">▤</span>

                <span>Reports</span>

            </a>


            {{-- ==================================================
                 COMMUNICATION
                 ================================================== --}}
            <div class="nav-section">
                Communication
            </div>

            <a href="{{ route('whatsapp.index') }}"
               class="nav-item {{ request()->routeIs('whatsapp.*') ? 'active' : '' }}">

                <span class="nav-icon">💬</span>

                <span>WhatsApp</span>

            </a>


            {{-- ==================================================
                 ACCOUNT
                 ================================================== --}}
            <div class="nav-section">
                Account
            </div>

            <form method="POST"
                  action="{{ route('logout') }}">

                @csrf

                <button type="submit"
                        class="nav-item"
                        style="
                            width:100%;
                            background:none;
                            border:0;
                            text-align:left;
                        ">

                    <span class="nav-icon">↪</span>

                    <span>Logout</span>

                </button>

            </form>


        </nav>

    </aside>


    {{-- ==========================================================
         MAIN
         ========================================================== --}}
    <main class="main">


        {{-- ======================================================
             TOPBAR
             ====================================================== --}}
        <header class="topbar">

            <div class="topbar-left">

                <button type="button"
                        class="sidebar-toggle"
                        aria-label="Open navigation">

                    ☰

                </button>

                <h1 class="page-title">

                    @yield('page_heading', 'Dashboard')

                </h1>

            </div>


            <div class="topbar-right">

                @auth

                    <div class="user-chip">

                        <div class="user-avatar">

                            {{
                                strtoupper(
                                    substr(
                                        auth()->user()->name
                                        ?? auth()->user()->username,
                                        0,
                                        1
                                    )
                                )
                            }}

                        </div>

                        <span>

                            {{
                                auth()->user()->name
                                ?? auth()->user()->username
                            }}

                        </span>

                    </div>

                @endauth

            </div>

        </header>


        {{-- ======================================================
             CONTENT
             ====================================================== --}}
        <section class="content">


            {{-- SUCCESS --}}
            @if(session('success'))

                <div class="alert alert-success"
                     data-auto-dismiss="5000">

                    <span>✓</span>

                    <div>
                        {{ session('success') }}
                    </div>

                </div>

            @endif


            {{-- ERROR --}}
            @if(session('error'))

                <div class="alert alert-danger">

                    <span>!</span>

                    <div>
                        {{ session('error') }}
                    </div>

                </div>

            @endif


            {{-- WARNING --}}
            @if(session('warning'))

                <div class="alert alert-warning">

                    <span>!</span>

                    <div>
                        {{ session('warning') }}
                    </div>

                </div>

            @endif


            {{-- VALIDATION ERRORS --}}
            @if($errors->any())

                <div class="alert alert-danger">

                    <span>!</span>

                    <div>

                        <strong>
                            Please correct the following:
                        </strong>

                        <ul style="margin:6px 0 0 18px;">

                            @foreach($errors->all() as $error)

                                <li>
                                    {{ $error }}
                                </li>

                            @endforeach

                        </ul>

                    </div>

                </div>

            @endif


            {{-- PAGE CONTENT --}}
            @yield('content')


        </section>

    </main>

</div>


{{-- =============================================================
     JAVASCRIPT
     ============================================================= --}}
<script src="{{ asset('assets/js/app.js') }}"></script>

@stack('scripts')

</body>
</html>