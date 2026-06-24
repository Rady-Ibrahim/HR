@extends('layouts.app')
@section('title', 'التقارير')
@section('page-title', 'التقارير')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-chart-bar me-2 text-primary"></i> التقارير</h1>
        <div class="breadcrumb">التقارير والإحصاءات الشاملة</div>
    </div>
</div>

<!-- REPORT CARDS -->
<div class="row g-3 mb-4">
    @php
    $reports = [
        ['key'=>'employees',   'title'=>'تقرير الموظفين',       'icon'=>'fas fa-users',         'color'=>'#e3f2fd','iconColor'=>'#1565c0', 'desc'=>'بيانات جميع الموظفين وحالاتهم'],
        ['key'=>'attendance',  'title'=>'تقرير الحضور',         'icon'=>'fas fa-fingerprint',   'color'=>'#e8f5e9','iconColor'=>'#2e7d32', 'desc'=>'سجل حضور وانصراف الموظفين'],
        ['key'=>'requests',    'title'=>'تقرير الطلبات',        'icon'=>'fas fa-clipboard-list', 'color'=>'#fff8e1','iconColor'=>'#f57f17', 'desc'=>'جميع الطلبات وحالاتها'],
        ['key'=>'collections', 'title'=>'تقرير التحصيلات',     'icon'=>'fas fa-coins',         'color'=>'#e0f7fa','iconColor'=>'#00838f', 'desc'=>'التحصيلات النقدية والإجماليات'],
        ['key'=>'salaries',    'title'=>'تقرير الرواتب',        'icon'=>'fas fa-money-bill-wave','color'=>'#e8f5e9','iconColor'=>'#388e3c', 'desc'=>'كشف رواتب الموظفين'],
        ['key'=>'performance', 'title'=>'تقرير الأداء',        'icon'=>'fas fa-trophy',        'color'=>'#fce4ec','iconColor'=>'#c62828', 'desc'=>'أداء المندوبين والموظفين'],
        ['key'=>'incentives',  'title'=>'تقرير الحوافز',       'icon'=>'fas fa-star',          'color'=>'#f3e5f5','iconColor'=>'#6a1b9a', 'desc'=>'الحوافز والخصومات والبدلات'],
        ['key'=>'monthly',     'title'=>'الملخص الشهري',       'icon'=>'fas fa-calendar-alt',  'color'=>'#e8eaf6','iconColor'=>'#3949ab', 'desc'=>'ملخص شامل للشهر الحالي'],
    ];
    @endphp
    @foreach($reports as $r)
    <div class="col-md-3">
        <div class="stat-card" onclick="loadReport('{{ $r['key'] }}')" style="cursor:pointer">
            <div class="stat-icon" style="background:{{ $r['color'] }}; color:{{ $r['iconColor'] }}"><i class="{{ $r['icon'] }}"></i></div>
            <div class="fw-bold mb-1">{{ $r['title'] }}</div>
            <div class="stat-label">{{ $r['desc'] }}</div>
            <div class="mt-3"><button class="btn btn-sm btn-outline-secondary">عرض التقرير <i class="fas fa-arrow-left ms-1"></i></button></div>
        </div>
    </div>
    @endforeach
</div>

<!-- FILTERS -->
<div class="section-card mb-4" id="reportFilters" style="display:none">
    <div class="section-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">الشهر</label>
                <input type="number" id="rptMonth" class="form-control" min="1" max="12" value="{{ date('n') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">السنة</label>
                <input type="number" id="rptYear" class="form-control" value="{{ date('Y') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">من تاريخ</label>
                <input type="date" id="rptFrom" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">إلى تاريخ</label>
                <input type="date" id="rptTo" class="form-control">
            </div>
            <div class="col-md-2">
                <button class="btn-primary-custom w-100" onclick="reloadReport()"><i class="fas fa-search me-1"></i> تحديث</button>
            </div>
        </div>
    </div>
</div>

<!-- REPORT CONTENT -->
<div class="section-card" id="reportContent" style="display:none">
    <div class="section-header">
        <i class="fas fa-chart-bar text-primary"></i>
        <h5 class="section-title" id="reportTitle">التقرير</h5>
        <div class="ms-auto">
            <button class="btn btn-sm btn-outline-success me-2" onclick="printReport()"><i class="fas fa-print me-1"></i> طباعة</button>
        </div>
    </div>
    <div id="reportBody" class="section-body">
        <div class="text-center py-5"><div class="spinner mx-auto"></div></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentReport = '';
const reportTitles = {
    employees: 'تقرير الموظفين', attendance: 'تقرير الحضور', requests: 'تقرير الطلبات',
    collections: 'تقرير التحصيلات', salaries: 'تقرير الرواتب', performance: 'تقرير الأداء',
    incentives: 'تقرير الحوافز', monthly: 'الملخص الشهري'
};

async function loadReport(key) {
    currentReport = key;
    document.getElementById('reportFilters').style.display = '';
    document.getElementById('reportContent').style.display = '';
    document.getElementById('reportTitle').textContent = reportTitles[key] || key;
    document.getElementById('reportBody').innerHTML = '<div class="text-center py-5"><div class="spinner mx-auto"></div></div>';

    const month = document.getElementById('rptMonth').value;
    const year  = document.getElementById('rptYear').value;
    const from  = document.getElementById('rptFrom').value;
    const to    = document.getElementById('rptTo').value;
    const params = new URLSearchParams({ month, year });
    if (from) params.append('date_from', from);
    if (to)   params.append('date_to', to);

    let url;
    if (key === 'monthly') url = '/reports/monthly-summary';
    else if (key === 'incentives') url = '/reports/incentives';
    else url = '/reports/' + key;

    const r = await apiFetch(url + '?' + params);
    if (!r.success) { document.getElementById('reportBody').innerHTML = `<div class="alert alert-danger">${r.message}</div>`; return; }

    renderReport(key, r.data);
}

function renderReport(key, data) {
    if (!data || (Array.isArray(data) && !data.length)) {
        document.getElementById('reportBody').innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2"></i><br>لا توجد بيانات</div>';
        return;
    }
    const rows = (data.data ?? data);
    if (!Array.isArray(rows) || !rows.length) {
        // Summary stats
        let html = '<div class="row g-3">';
        Object.entries(rows).forEach(([k, v]) => {
            html += `<div class="col-md-3"><div class="stat-card text-center"><div class="fw-bold fs-4">${typeof v === 'number' ? Number(v).toLocaleString('ar-EG') : v}</div><div class="stat-label">${k.replace(/_/g,' ')}</div></div></div>`;
        });
        html += '</div>';
        document.getElementById('reportBody').innerHTML = html;
        return;
    }
    const cols = Object.keys(rows[0]);
    let html = `<div class="table-responsive"><table class="data-table"><thead><tr>${cols.map(c => `<th>${c}</th>`).join('')}</tr></thead><tbody>`;
    rows.forEach(row => {
        html += `<tr>${cols.map(c => `<td>${row[c] ?? '-'}</td>`).join('')}</tr>`;
    });
    html += '</tbody></table></div>';
    document.getElementById('reportBody').innerHTML = html;
}

function reloadReport() { if (currentReport) loadReport(currentReport); }
function printReport() { window.print(); }
</script>
@endpush
