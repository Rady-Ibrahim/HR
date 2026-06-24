<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'نظام إدارة الموارد البشرية')</title>

    <!-- Bootstrap RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Google Fonts Arabic -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        :root {
            --primary:   #2c3e8c;
            --secondary: #1abc9c;
            --accent:    #e74c3c;
            --sidebar-w: 260px;
            --header-h:  64px;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            background: #f4f6fb;
            color: #2d3748;
        }

        /* SIDEBAR */
        .sidebar {
            position: fixed;
            top: 0; right: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: linear-gradient(180deg, #1a237e 0%, #283593 60%, #3949ab 100%);
            overflow-y: auto;
            z-index: 1000;
            transition: width .3s ease;
            box-shadow: -4px 0 20px rgba(0,0,0,.18);
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.2); border-radius: 4px; }

        .sidebar .brand {
            height: var(--header-h);
            display: flex; align-items: center; gap: 12px;
            padding: 0 20px;
            border-bottom: 1px solid rgba(255,255,255,.12);
        }
        .sidebar .brand-logo {
            width: 36px; height: 36px;
            background: var(--secondary);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: #fff;
        }
        .sidebar .brand-name { color: #fff; font-weight: 700; font-size: 1rem; }
        .sidebar .brand-sub  { color: rgba(255,255,255,.55); font-size: .7rem; }

        .sidebar .nav-section { padding: 16px 12px 4px; }
        .sidebar .nav-section-title {
            color: rgba(255,255,255,.4);
            font-size: .65rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 0 8px;
            margin-bottom: 6px;
        }

        .sidebar .nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            color: rgba(255,255,255,.75);
            font-size: .875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all .2s;
            margin-bottom: 2px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,.12);
            color: #fff;
        }
        .sidebar .nav-link .icon { width: 22px; text-align: center; font-size: .95rem; }
        .sidebar .nav-link .badge-count {
            margin-right: auto;
            background: var(--accent);
            color: #fff;
            font-size: .65rem;
            padding: 2px 6px;
            border-radius: 10px;
        }

        /* TOPBAR */
        .topbar {
            position: fixed;
            top: 0; right: var(--sidebar-w); left: 0;
            height: var(--header-h);
            background: #fff;
            display: flex; align-items: center;
            padding: 0 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
            z-index: 999;
            gap: 16px;
        }
        .topbar .page-title { font-weight: 700; font-size: 1.1rem; color: #1a237e; }
        .topbar .spacer { flex: 1; }
        .topbar .topbar-btn {
            width: 38px; height: 38px;
            border-radius: 10px;
            border: none;
            background: #f4f6fb;
            color: #5a6782;
            font-size: 1rem;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: all .2s;
        }
        .topbar .topbar-btn:hover { background: #e8ebf5; color: var(--primary); }
        .topbar .topbar-btn .badge {
            position: absolute; top: -4px; right: -4px;
            min-width: 16px; height: 16px;
            background: var(--accent);
            border-radius: 8px;
            font-size: .6rem;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
        }
        .topbar .user-info {
            display: flex; align-items: center; gap: 10px;
            padding: 6px 12px;
            border-radius: 10px;
            cursor: pointer;
            transition: background .2s;
        }
        .topbar .user-info:hover { background: #f4f6fb; }
        .topbar .user-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .85rem;
        }
        .topbar .user-name  { font-weight: 600; font-size: .85rem; color: #2d3748; }
        .topbar .user-role  { font-size: .7rem; color: #8892a4; }

        /* MAIN */
        .main-wrapper {
            margin-right: var(--sidebar-w);
            padding-top: var(--header-h);
            min-height: 100vh;
        }
        .page-content { padding: 28px 28px 40px; }

        /* CARDS */
        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            border: 1px solid rgba(0,0,0,.04);
            transition: transform .2s, box-shadow .2s;
            height: 100%;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.1); }
        .stat-card .stat-icon {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 14px;
        }
        .stat-card .stat-value  { font-size: 1.9rem; font-weight: 800; color: #1a237e; line-height: 1; }
        .stat-card .stat-label  { font-size: .8rem; color: #8892a4; margin-top: 4px; }
        .stat-card .stat-change { font-size: .75rem; margin-top: 8px; }
        .stat-card .stat-change.up   { color: #27ae60; }
        .stat-card .stat-change.down { color: #e74c3c; }

        /* SECTION CARD */
        .section-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            border: 1px solid rgba(0,0,0,.04);
            overflow: hidden;
        }
        .section-card .section-header {
            padding: 18px 22px;
            border-bottom: 1px solid #f0f2f8;
            display: flex; align-items: center; gap: 12px;
        }
        .section-card .section-title { font-weight: 700; font-size: 1rem; color: #1a237e; margin: 0; }
        .section-card .section-body  { padding: 20px 22px; }

        /* TABLE */
        .data-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .data-table thead th {
            background: #f8f9fe;
            padding: 11px 14px;
            font-size: .78rem;
            font-weight: 600;
            color: #5a6782;
            text-transform: uppercase;
            letter-spacing: .5px;
            border-bottom: 2px solid #edf0f9;
        }
        .data-table tbody td {
            padding: 12px 14px;
            font-size: .875rem;
            border-bottom: 1px solid #f4f6fb;
            vertical-align: middle;
        }
        .data-table tbody tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover td { background: #fafbff; }

        /* BADGES */
        .badge-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 600;
        }
        .badge-active   { background: #e8f5e9; color: #2e7d32; }
        .badge-inactive { background: #fff3e0; color: #e65100; }
        .badge-pending  { background: #fff8e1; color: #f57f17; }
        .badge-approved { background: #e3f2fd; color: #1565c0; }
        .badge-rejected { background: #fce4ec; color: #c62828; }
        .badge-paid     { background: #e8f5e9; color: #2e7d32; }
        .badge-draft    { background: #f3e5f5; color: #6a1b9a; }

        /* BUTTONS */
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary), #3949ab);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 9px 20px;
            font-weight: 600;
            font-size: .875rem;
            transition: all .2s;
        }
        .btn-primary-custom:hover { box-shadow: 0 4px 14px rgba(44,62,140,.35); transform: translateY(-1px); color: #fff; }

        /* PAGE HEADER */
        .page-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px;
        }
        .page-header h1 { font-weight: 800; font-size: 1.6rem; color: #1a237e; margin: 0; }
        .page-header .breadcrumb { font-size: .8rem; color: #8892a4; margin: 2px 0 0; }

        /* ALERT */
        .alert-float {
            position: fixed;
            bottom: 24px; left: 24px;
            z-index: 9999;
            min-width: 300px;
        }

        /* FORM */
        .form-control, .form-select {
            border-radius: 10px;
            border: 1.5px solid #e8ebf5;
            font-size: .875rem;
            padding: 9px 14px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44,62,140,.1);
        }
        .form-label { font-weight: 600; font-size: .83rem; color: #4a5568; }

        /* MOBILE */
        @media (max-width: 768px) {
            .sidebar { width: 0; overflow: hidden; }
            .sidebar.open { width: var(--sidebar-w); }
            .topbar, .main-wrapper { margin-right: 0; right: 0; }
        }

        /* LOADER */
        .loading-overlay {
            position: fixed; inset: 0;
            background: rgba(255,255,255,.8);
            display: flex; align-items: center; justify-content: center;
            z-index: 9999;
        }
        .spinner { width: 50px; height: 50px; border: 4px solid #e8ebf5; border-top-color: var(--primary); border-radius: 50%; animation: spin .8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .chart-container { position: relative; height: 280px; }
    </style>
    @stack('styles')
</head>
<body>

<!-- SIDEBAR -->
<nav class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-logo"><i class="fas fa-users-cog"></i></div>
        <div>
            <div class="brand-name">HR System</div>
            <div class="brand-sub">نظام إدارة الموارد البشرية</div>
        </div>
    </div>

    <!-- Main Menu -->
    <div class="nav-section">
        <div class="nav-section-title">القائمة الرئيسية</div>
        <a href="/dashboard" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-tachometer-alt"></i></span> لوحة التحكم
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-section-title">الموارد البشرية</div>
        <a href="/employees" class="nav-link {{ request()->is('employees*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-user-tie"></i></span> الموظفين
        </a>
        <a href="/attendance" class="nav-link {{ request()->is('attendance*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-fingerprint"></i></span> الحضور والانصراف
        </a>
        <a href="/salaries" class="nav-link {{ request()->is('salaries*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-money-bill-wave"></i></span> الرواتب
        </a>
        <a href="/incentives" class="nav-link {{ request()->is('incentives*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-star"></i></span> الحوافز
        </a>
        <a href="/deductions" class="nav-link {{ request()->is('deductions*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-minus-circle"></i></span> الخصومات
        </a>
        <a href="/advances" class="nav-link {{ request()->is('advances*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-hand-holding-usd"></i></span> السلف
        </a>
        <a href="/allowances" class="nav-link {{ request()->is('allowances*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-gift"></i></span> البدلات
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-section-title">التشغيل</div>
        <a href="/requests" class="nav-link {{ request()->is('requests*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-clipboard-list"></i></span> الطلبات
        </a>
        <a href="/routes" class="nav-link {{ request()->is('routes*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-route"></i></span> خطوط السير
        </a>
        <a href="/deliveries" class="nav-link {{ request()->is('deliveries*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-truck"></i></span> التسليمات
        </a>
        <a href="/collections" class="nav-link {{ request()->is('collections*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-coins"></i></span> التحصيلات
        </a>
        <a href="/commissions" class="nav-link {{ request()->is('commissions*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-percent"></i></span> العمولات
        </a>
        <a href="/car-violations" class="nav-link {{ request()->is('car-violations*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-car-crash"></i></span> مخالفات السيارات
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-section-title">الإدارة</div>
        <a href="/customers" class="nav-link {{ request()->is('customers*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-store"></i></span> العملاء
        </a>
        <a href="/warehouses" class="nav-link {{ request()->is('warehouses*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-warehouse"></i></span> المخازن
        </a>
        <a href="/items" class="nav-link {{ request()->is('items*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-boxes"></i></span> الأصناف
        </a>
        <a href="/approvals" class="nav-link {{ request()->is('approvals*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-check-double"></i></span> الموافقات
            <span class="badge-count" id="pendingCount">-</span>
        </a>
        <a href="/reports" class="nav-link {{ request()->is('reports*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-chart-bar"></i></span> التقارير
        </a>
    </div>
</nav>

<!-- TOPBAR -->
<header class="topbar">
    <button class="topbar-btn d-md-none" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
    <div class="page-title">@yield('page-title', 'الرئيسية')</div>
    <div class="spacer"></div>
    <div class="position-relative">
        <button class="topbar-btn" onclick="window.location='/notifications'" title="الإشعارات">
            <i class="fas fa-bell"></i>
            <span class="badge" id="notifBadge" style="display:none">0</span>
        </button>
    </div>
    <div class="dropdown">
        <div class="user-info dropdown-toggle" data-bs-toggle="dropdown">
            <div class="user-avatar">م</div>
            <div>
                <div class="user-name">المدير</div>
                <div class="user-role">مدير النظام</div>
            </div>
        </div>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> الملف الشخصي</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="/logout"><i class="fas fa-sign-out-alt me-2"></i> تسجيل الخروج</a></li>
        </ul>
    </div>
</header>

<!-- MAIN CONTENT -->
<div class="main-wrapper">
    <div class="page-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>
</div>

<!-- ALERT FLOAT -->
<div class="alert-float" id="alertFloat" style="display:none"></div>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
const API_BASE = '/api';
@if(session('api_token'))
localStorage.setItem('api_token', '{{ session("api_token") }}');
@endif
const TOKEN = localStorage.getItem('api_token') || '';

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

function showAlert(msg, type = 'success') {
    const el = document.getElementById('alertFloat');
    el.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show shadow">
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i> ${msg}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`;
    el.style.display = 'block';
    setTimeout(() => { el.style.display = 'none'; }, 4000);
}

async function apiFetch(url, options = {}) {
    const defaults = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': 'Bearer ' + TOKEN,
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        }
    };
    const res = await fetch(API_BASE + url, { ...defaults, ...options, headers: { ...defaults.headers, ...(options.headers || {}) } });
    return res.json();
}

// Load pending approvals count
async function loadPendingCount() {
    try {
        const r = await apiFetch('/approvals/pending');
        if (r.success) {
            document.getElementById('pendingCount').textContent = r.summary?.total_pending || 0;
        }
    } catch(e) {}
}

async function loadNotifCount() {
    try {
        const r = await apiFetch('/notifications/unread-count');
        if (r.success && r.count > 0) {
            const b = document.getElementById('notifBadge');
            b.textContent = r.count;
            b.style.display = 'flex';
        }
    } catch(e) {}
}

document.addEventListener('DOMContentLoaded', () => {
    if (TOKEN) {
        loadPendingCount();
        loadNotifCount();
    }
});
</script>
@stack('scripts')
</body>
</html>
