@extends('layouts.app')
@section('title', 'إدارة الرواتب')
@section('page-title', 'الرواتب')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-money-bill-wave me-2 text-primary"></i> الرواتب</h1>
        <div class="breadcrumb">حساب واعتماد رواتب الموظفين</div>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" onclick="calculateSalaries()"><i class="fas fa-calculator me-1"></i> حساب الرواتب</button>
        <button class="btn-primary-custom" onclick="bulkApprove()"><i class="fas fa-check-double me-1"></i> اعتماد جماعي</button>
    </div>
</div>

<!-- MONTHLY SUMMARY -->
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card"><div class="stat-icon" style="background:#e3f2fd;color:#1565c0"><i class="fas fa-users"></i></div><div class="stat-value" id="salEmpCount">-</div><div class="stat-label">موظفين محسوبة رواتبهم</div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-icon" style="background:#e8f5e9;color:#2e7d32"><i class="fas fa-money-bill"></i></div><div class="stat-value" id="salGross" style="font-size:1.3rem">-</div><div class="stat-label">إجمالي الرواتب</div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-icon" style="background:#e8f5e9;color:#388e3c"><i class="fas fa-hand-holding-usd"></i></div><div class="stat-value" id="salNet" style="font-size:1.3rem">-</div><div class="stat-label">صافي الرواتب</div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-icon" style="background:#fff3e0;color:#e65100"><i class="fas fa-hourglass-half"></i></div><div class="stat-value" id="salPending">-</div><div class="stat-label">معلقة</div></div></div>
</div>

<!-- FILTERS -->
<div class="section-card mb-4">
    <div class="section-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">الشهر</label>
                <input type="number" id="salMonth" class="form-control" min="1" max="12" value="{{ date('n') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">السنة</label>
                <input type="number" id="salYear" class="form-control" value="{{ date('Y') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">الحالة</label>
                <select id="salStatus" class="form-select">
                    <option value="">الكل</option>
                    <option value="draft">مسودة</option>
                    <option value="approved">معتمدة</option>
                    <option value="paid">مدفوعة</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">الموظف</label>
                <input type="text" id="salEmpSearch" class="form-control" placeholder="بحث باسم الموظف">
            </div>
            <div class="col-md-2">
                <button class="btn-primary-custom w-100" onclick="loadSalaries()"><i class="fas fa-search me-1"></i> بحث</button>
            </div>
        </div>
    </div>
</div>

<!-- SALARIES TABLE -->
<div class="section-card">
    <div class="section-header">
        <i class="fas fa-table text-primary"></i>
        <h5 class="section-title">كشف الرواتب</h5>
        <div class="ms-auto">
            <button class="btn btn-sm btn-outline-secondary" onclick="exportSalaries()"><i class="fas fa-download me-1"></i> تصدير</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll" onchange="toggleAll(this)"></th>
                    <th>الموظف</th>
                    <th>الأساسي</th>
                    <th>الحوافز</th>
                    <th>البدلات</th>
                    <th>العمولات</th>
                    <th>خصومات</th>
                    <th>سلف</th>
                    <th>صافي</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody id="salariesTable">
                <tr><td colspan="11" class="text-center py-4"><div class="spinner mx-auto" style="width:30px;height:30px;border-width:3px"></div></td></tr>
            </tbody>
        </table>
    </div>
    <div class="section-body d-flex justify-content-between">
        <div id="salPagInfo" class="text-muted" style="font-size:.8rem"></div>
        <div id="salPagination"></div>
    </div>
</div>

<!-- SALARY DETAIL MODAL -->
<div class="modal fade" id="salaryDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-invoice-dollar me-2"></i> تفاصيل الراتب</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="salaryDetailBody">
                <div class="text-center py-4"><div class="spinner mx-auto"></div></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let selectedSalaries = new Set();
const salBadge = { draft:'badge-draft', approved:'badge-approved', paid:'badge-active' };
const salLabel = { draft:'مسودة', approved:'معتمدة', paid:'مدفوعة' };

async function loadSummary() {
    const month = document.getElementById('salMonth').value;
    const year  = document.getElementById('salYear').value;
    const r = await apiFetch(`/salaries/monthly-summary?month=${month}&year=${year}`);
    if (!r.success) return;
    const d = r.data;
    document.getElementById('salEmpCount').textContent = d.employee_count ?? '-';
    document.getElementById('salGross').textContent    = Number(d.total_gross ?? 0).toLocaleString('ar-EG') + ' ج.م';
    document.getElementById('salNet').textContent      = Number(d.total_net ?? 0).toLocaleString('ar-EG') + ' ج.م';
    document.getElementById('salPending').textContent  = d.pending_count ?? '-';
}

async function loadSalaries(page = 1) {
    const params = new URLSearchParams({ per_page: 15, page });
    params.append('month', document.getElementById('salMonth').value);
    params.append('year',  document.getElementById('salYear').value);
    const s = document.getElementById('salStatus').value;
    const e = document.getElementById('salEmpSearch').value;
    if (s) params.append('status', s);
    if (e) params.append('search', e);

    const r = await apiFetch('/salaries?' + params);
    if (!r.success) return;
    const data = r.data;
    document.getElementById('salPagInfo').textContent = `إجمالي: ${data.total}`;
    const all = data.data;
    if (!all.length) {
        document.getElementById('salariesTable').innerHTML = '<tr><td colspan="11" class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2"></i><br>لا توجد رواتب محسوبة لهذا الشهر</td></tr>';
        return;
    }
    document.getElementById('salariesTable').innerHTML = all.map(s => `
        <tr>
            <td><input type="checkbox" class="salary-check" value="${s.id}" ${selectedSalaries.has(s.id) ? 'checked' : ''} onchange="toggleSalary(${s.id})"></td>
            <td><strong>${s.employee?.name ?? '-'}</strong><br><small class="text-muted">${s.employee?.employee_code ?? '-'}</small></td>
            <td>${Number(s.base_salary).toLocaleString()}</td>
            <td class="text-success">+${Number(s.total_incentives ?? 0).toLocaleString()}</td>
            <td class="text-success">+${Number(s.total_allowances ?? 0).toLocaleString()}</td>
            <td class="text-success">+${Number(s.total_commissions ?? 0).toLocaleString()}</td>
            <td class="text-danger">-${Number(s.total_deductions ?? 0).toLocaleString()}</td>
            <td class="text-danger">-${Number(s.total_advances ?? 0).toLocaleString()}</td>
            <td class="fw-bold text-primary fs-6">${Number(s.net_salary).toLocaleString()} ج.م</td>
            <td><span class="badge-status ${salBadge[s.status] || 'badge-draft'}">${salLabel[s.status] || s.status}</span></td>
            <td>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-primary" onclick="viewSalary(${s.id})" title="التفاصيل"><i class="fas fa-eye"></i></button>
                    ${s.status === 'draft' ? `<button class="btn btn-sm btn-outline-success" onclick="approveSalary(${s.id})" title="اعتماد"><i class="fas fa-check"></i></button>` : ''}
                    ${s.status === 'approved' ? `<button class="btn btn-sm btn-outline-primary" onclick="paySalary(${s.id})" title="صرف"><i class="fas fa-money-bill"></i></button>` : ''}
                </div>
            </td>
        </tr>
    `).join('');
    const pages = [];
    for (let i = 1; i <= Math.min(data.last_page, 10); i++) {
        pages.push(`<button class="btn btn-sm ${i === data.current_page ? 'btn-primary' : 'btn-outline-primary'} mx-1" onclick="loadSalaries(${i})">${i}</button>`);
    }
    document.getElementById('salPagination').innerHTML = pages.join('');
}

function toggleSalary(id) { selectedSalaries.has(id) ? selectedSalaries.delete(id) : selectedSalaries.add(id); }
function toggleAll(cb) { document.querySelectorAll('.salary-check').forEach(c => { c.checked = cb.checked; toggleSalary(parseInt(c.value)); }); }

async function calculateSalaries() {
    if (!confirm('هل تريد حساب رواتب جميع الموظفين لهذا الشهر؟')) return;
    const month = document.getElementById('salMonth').value;
    const year  = document.getElementById('salYear').value;
    const r = await apiFetch('/salaries/calculate', { method: 'POST', body: JSON.stringify({ month: parseInt(month), year: parseInt(year) }) });
    if (r.success) { showAlert('تم حساب الرواتب بنجاح'); loadSalaries(); loadSummary(); }
    else showAlert(r.message || 'فشل الحساب', 'danger');
}

async function bulkApprove() {
    if (!selectedSalaries.size) { showAlert('اختر رواتب أولاً', 'warning'); return; }
    if (!confirm(`هل تريد اعتماد ${selectedSalaries.size} راتب؟`)) return;
    const r = await apiFetch('/salaries/bulk-approve', { method: 'POST', body: JSON.stringify({ salary_ids: [...selectedSalaries] }) });
    if (r.success) { showAlert('تم الاعتماد الجماعي بنجاح'); selectedSalaries.clear(); loadSalaries(); loadSummary(); }
    else showAlert(r.message, 'danger');
}

async function approveSalary(id) {
    const r = await apiFetch(`/salaries/${id}/approve`, { method: 'POST' });
    if (r.success) { showAlert('تم اعتماد الراتب'); loadSalaries(); }
    else showAlert(r.message, 'danger');
}

async function paySalary(id) {
    if (!confirm('هل تريد صرف هذا الراتب؟')) return;
    const r = await apiFetch(`/salaries/${id}/pay`, { method: 'POST' });
    if (r.success) { showAlert('تم صرف الراتب'); loadSalaries(); }
    else showAlert(r.message, 'danger');
}

async function viewSalary(id) {
    const modal = new bootstrap.Modal(document.getElementById('salaryDetailModal'));
    document.getElementById('salaryDetailBody').innerHTML = '<div class="text-center py-4"><div class="spinner mx-auto"></div></div>';
    modal.show();
    const r = await apiFetch('/salaries/' + id);
    if (!r.success) return;
    const s = r.data;
    const components = s.components || [];
    const attendanceComponents = components.filter(c => c.component_type === 'attendance_deduction');
    document.getElementById('salaryDetailBody').innerHTML = `
    <h6 class="fw-bold text-primary">${s.employee?.name ?? '-'} - ${s.month}/${s.year}</h6>
    <hr>
    ${attendanceComponents.length ? `
        <div class="alert alert-warning py-2" style="font-size:.85rem">
            <i class="fas fa-business-time me-1"></i>
            يوجد خصم حضور تلقائي ضمن الراتب: ${attendanceComponents.map(c => `${c.component_name} (${Math.abs(Number(c.amount)).toLocaleString()} ج.م)`).join('، ')}
        </div>
    ` : ''}
    <div class="row g-2 mb-3">
        <div class="col-12"><h6 class="text-muted mb-2">المستحقات</h6></div>
        <div class="col-6 col-md-3"><div class="p-2 bg-light rounded text-center"><small class="text-muted">أساسي</small><div class="fw-bold">${Number(s.base_salary).toLocaleString()} ج.م</div></div></div>
        <div class="col-6 col-md-3"><div class="p-2 bg-light rounded text-center"><small class="text-muted">حوافز</small><div class="fw-bold text-success">+${Number(s.total_incentives ?? 0).toLocaleString()} ج.م</div></div></div>
        <div class="col-6 col-md-3"><div class="p-2 bg-light rounded text-center"><small class="text-muted">بدلات</small><div class="fw-bold text-success">+${Number(s.total_allowances ?? 0).toLocaleString()} ج.م</div></div></div>
        <div class="col-6 col-md-3"><div class="p-2 bg-light rounded text-center"><small class="text-muted">عمولات</small><div class="fw-bold text-success">+${Number(s.total_commissions ?? 0).toLocaleString()} ج.م</div></div></div>
        <div class="col-12 mt-2"><h6 class="text-muted mb-2">الخصومات</h6></div>
        <div class="col-6 col-md-3"><div class="p-2 bg-light rounded text-center"><small class="text-muted">خصومات</small><div class="fw-bold text-danger">-${Number(s.total_deductions ?? 0).toLocaleString()} ج.م</div></div></div>
        <div class="col-6 col-md-3"><div class="p-2 bg-light rounded text-center"><small class="text-muted">سلف</small><div class="fw-bold text-danger">-${Number(s.total_advances ?? 0).toLocaleString()} ج.م</div></div></div>
        <div class="col-6 col-md-3"><div class="p-2 bg-light rounded text-center"><small class="text-muted">مخالفات</small><div class="fw-bold text-danger">-${Number(s.total_violations ?? 0).toLocaleString()} ج.م</div></div></div>
        <div class="col-6 col-md-3"><div class="p-2 bg-light rounded text-center"><small class="text-muted">خصم حضور</small><div class="fw-bold text-danger">-${attendanceComponents.reduce((sum, c) => sum + Math.abs(Number(c.amount || 0)), 0).toLocaleString()} ج.م</div></div></div>
    </div>
    ${components.length ? `
        <h6 class="text-muted mb-2">تفاصيل المكونات</h6>
        <div class="table-responsive mb-3">
            <table class="data-table">
                <thead><tr><th>النوع</th><th>الوصف</th><th>المبلغ</th></tr></thead>
                <tbody>
                    ${components.map(c => `
                        <tr>
                            <td>${componentLabel(c.component_type)}</td>
                            <td>${c.component_name ?? '-'}</td>
                            <td class="${Number(c.amount) < 0 ? 'text-danger' : 'text-success'} fw-bold">${Number(c.amount).toLocaleString()} ج.م</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    ` : ''}
    <div class="d-flex justify-content-between p-3 bg-primary text-white rounded">
        <span class="fw-bold fs-5">صافي الراتب</span>
        <span class="fw-bold fs-4">${Number(s.net_salary).toLocaleString()} ج.م</span>
    </div>`;
}

function componentLabel(type) {
    return {
        incentive: 'حافز',
        allowance: 'بدل',
        commission: 'عمولة',
        deduction: 'خصم',
        attendance_deduction: 'خصم حضور',
        advance: 'سلفة',
        violation: 'مخالفة'
    }[type] || type;
}

function exportSalaries() { window.location = `/reports/salaries?month=${document.getElementById('salMonth').value}&year=${document.getElementById('salYear').value}`; }

document.addEventListener('DOMContentLoaded', () => { loadSummary(); loadSalaries(); });
</script>
@endpush
