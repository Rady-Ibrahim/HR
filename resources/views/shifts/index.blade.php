@extends('layouts.app')
@section('title', 'إدارة الورديات')
@section('page-title', 'الورديات')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-clock me-2 text-primary"></i> الورديات وخصومات الحضور</h1>
        <div class="breadcrumb">إدارة مواعيد الورديات وقواعد الخصم</div>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" onclick="openAssignModal()"><i class="fas fa-user-clock me-1"></i> تعيين وردية</button>
        <button class="btn-primary-custom" onclick="openAddShiftModal()"><i class="fas fa-plus me-1"></i> إضافة وردية</button>
    </div>
</div>

<!-- SHIFTS LIST -->
<div class="section-card mb-4">
    <div class="section-header">
        <i class="fas fa-list text-primary"></i>
        <h5 class="section-title">الورديات</h5>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>بداية العمل</th>
                    <th>نهاية العمل</th>
                    <th>سماح التأخير</th>
                    <th>قواعد التأخير</th>
                    <th>قواعد الانصراف المبكر</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody id="shiftsTable">
                <tr><td colspan="8" class="text-center py-4"><div class="spinner mx-auto" style="width:30px;height:30px;border-width:3px"></div></td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ASSIGNMENTS LIST -->
<div class="section-card">
    <div class="section-header">
        <i class="fas fa-users text-primary"></i>
        <h5 class="section-title">تعيينات الورديات</h5>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>الموظف</th><th>الوردية</th><th>من تاريخ</th><th>إلى تاريخ</th><th>إجراءات</th></tr>
            </thead>
            <tbody id="assignmentsTable">
                <tr><td colspan="5" class="text-center py-4"><div class="spinner mx-auto" style="width:30px;height:30px;border-width:3px"></div></td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ─── ADD/EDIT SHIFT MODAL ─── -->
<div class="modal fade" id="shiftModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="shiftModalTitle"><i class="fas fa-clock me-2"></i> إضافة وردية</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="shiftForm">
                    <input type="hidden" id="sf_id">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">اسم الوردية *</label>
                            <input type="text" name="name" id="sf_name" class="form-control" required maxlength="100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">بداية العمل *</label>
                            <input type="time" name="start_time" id="sf_start" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">نهاية العمل</label>
                            <input type="time" name="end_time" id="sf_end" class="form-control" placeholder="بدون = مفتوح">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">سماح التأخير (دقيقة)</label>
                            <input type="number" name="grace_period_minutes" id="sf_grace" class="form-control" value="15" min="0" max="180">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">حالة الوردية</label>
                            <select name="is_active" id="sf_active" class="form-select">
                                <option value="1">نشطة</option>
                                <option value="0">غير نشطة</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="text-primary fw-bold mb-2"><i class="fas fa-gavel me-1"></i> قواعد خصم التأخير</h6>
                    <div class="table-responsive mb-3">
                        <table class="data-table" id="lateRulesTable">
                            <thead>
                                <tr><th>من (دقيقة)</th><th>إلى (دقيقة)</th><th>نوع الخصم</th><th>القيمة</th><th></th></tr>
                            </thead>
                            <tbody id="lateRulesBody">
                                <tr class="late-rule-row">
                                    <td><input type="number" class="form-control form-control-sm lr-min" min="0" value="1"></td>
                                    <td><input type="number" class="form-control form-control-sm lr-max" min="0" placeholder="بدون حد"></td>
                                    <td>
                                        <select class="form-select form-select-sm lr-type">
                                            <option value="minutes">دقائق</option>
                                            <option value="quarter_day">ربع يوم</option>
                                            <option value="half_day">نصف يوم</option>
                                            <option value="full_day">يوم كامل</option>
                                            <option value="percentage">نسبة مئوية</option>
                                            <option value="fixed_amount">مبلغ ثابت</option>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm lr-value" min="0" step="0.01" placeholder="لنسبة/مبلغ"></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mb-3" onclick="addLateRuleRow()"><i class="fas fa-plus me-1"></i> إضافة قاعدة تأخير</button>

                    <h6 class="text-primary fw-bold mb-2"><i class="fas fa-door-open me-1"></i> قواعد خصم الانصراف المبكر</h6>
                    <div class="table-responsive mb-3">
                        <table class="data-table" id="earlyRulesTable">
                            <thead>
                                <tr><th>من (دقيقة)</th><th>إلى (دقيقة)</th><th>نوع الخصم</th><th>القيمة</th><th></th></tr>
                            </thead>
                            <tbody id="earlyRulesBody">
                                <tr class="early-rule-row">
                                    <td><input type="number" class="form-control form-control-sm er-min" min="0" value="1"></td>
                                    <td><input type="number" class="form-control form-control-sm er-max" min="0" placeholder="بدون حد"></td>
                                    <td>
                                        <select class="form-select form-select-sm er-type">
                                            <option value="quarter_day">ربع يوم</option>
                                            <option value="half_day">نصف يوم</option>
                                            <option value="full_day">يوم كامل</option>
                                            <option value="percentage">نسبة مئوية</option>
                                            <option value="fixed_amount">مبلغ ثابت</option>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm er-value" min="0" step="0.01" placeholder="لنسبة/مبلغ"></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mb-2" onclick="addEarlyRuleRow()"><i class="fas fa-plus me-1"></i> إضافة قاعدة انصراف مبكر</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn-primary-custom" onclick="saveShift()"><i class="fas fa-save me-1"></i> حفظ</button>
            </div>
        </div>
    </div>
</div>

<!-- ─── ASSIGN SHIFT MODAL ─── -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-clock me-2"></i> تعيين وردية لموظفين</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="assignForm">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">الموظفون *</label>
                            <div class="d-flex gap-2 mb-2">
                                <input type="text" id="af_search" class="form-control form-control-sm" placeholder="بحث..." style="width:200px" oninput="filterEmpChecks()">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleEmpChecks(true)">تحديد الكل</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleEmpChecks(false)">إلغاء</button>
                            </div>
                            <div id="af_employees" class="border rounded p-2" style="max-height:220px;overflow:auto">
                                <div class="text-center text-muted py-3">جاري تحميل الموظفين...</div>
                            </div>
                            <small class="text-muted" id="af_selectedCount">0 محدد</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">الوردية *</label>
                            <select name="shift_id" id="af_shift" class="form-select" required>
                                <option value="">اختر الوردية</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">من تاريخ *</label>
                            <input type="date" name="effective_from" id="af_from" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">إلى تاريخ</label>
                            <input type="date" name="effective_to" id="af_to" class="form-control" placeholder="بدون تاريخ = مستمر">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn-primary-custom" onclick="saveAssignment()"><i class="fas fa-save me-1"></i> حفظ</button>
            </div>
        </div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="shiftDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm"><div class="modal-content">
        <div class="modal-header border-0"><h5 class="modal-title text-danger"><i class="fas fa-trash me-2"></i>تأكيد الحذف</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body text-center"><i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i><p id="shiftDeleteMsg">حذف هذا العنصر؟</p></div>
        <div class="modal-footer border-0 justify-content-center">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
            <button type="button" class="btn btn-danger" id="shiftDeleteBtn">حذف</button>
        </div>
    </div></div>
</div>
@endsection

@push('scripts')
<script>
let shiftDeleteId = null;
let shiftDeleteType = null;
const deductionLabels = {
    minutes: 'دقائق', quarter_day: 'ربع يوم', half_day: 'نصف يوم',
    full_day: 'يوم كامل', percentage: 'نسبة مئوية', fixed_amount: 'مبلغ ثابت'
};

// ─── SHIFTS ──────────────────────────────────────────────
async function loadShifts() {
    const r = await apiFetch('/shifts');
    if (!r.success) return;
    const all = r.data?.data ?? r.data ?? [];
    if (!all.length) {
        document.getElementById('shiftsTable').innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">لا توجد ورديات</td></tr>';
        return;
    }
    document.getElementById('shiftsTable').innerHTML = all.map(s => `
        <tr>
            <td><strong>${s.name}</strong></td>
            <td>${s.start_time?.substring(0,5)}</td>
            <td>${s.end_time?.substring(0,5)}</td>
            <td>${s.grace_period_minutes} د</td>
            <td>${(s.late_rules||[]).map(r => `${r.min_delay_minutes}-${r.max_delay_minutes??'∞'} ${r.deduction_type}`).join('<br>') || '-'}</td>
            <td>${(s.early_exit_rules||[]).map(r => `${r.min_early_minutes}-${r.max_early_minutes??'∞'} ${r.deduction_type}`).join('<br>') || '-'}</td>
            <td><span class="badge-status ${s.is_active ? 'badge-active' : 'badge-rejected'}">${s.is_active ? 'نشطة' : 'غير نشطة'}</span></td>
            <td>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-warning" onclick="openEditShift(${s.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteShift(${s.id})"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>
    `).join('');
}

async function loadShiftSelect() {
    const r = await apiFetch('/shifts');
    if (!r.success) return;
    const all = r.data?.data ?? r.data ?? [];
    const sel = document.getElementById('af_shift');
    sel.innerHTML = '<option value="">اختر الوردية</option>' + all.filter(s => s.is_active).map(s =>
        `<option value="${s.id}">${s.name} (${s.start_time?.substring(0,5)} - ${s.end_time?.substring(0,5)})</option>`
    ).join('');
}

// ─── ADD/EDIT SHIFT ──────────────────────────────────────
function resetLateRules() {
    document.getElementById('lateRulesBody').innerHTML = `
        <tr class="late-rule-row">
            <td><input type="number" class="form-control form-control-sm lr-min" min="0" value="1"></td>
            <td><input type="number" class="form-control form-control-sm lr-max" min="0" placeholder="بدون حد"></td>
            <td><select class="form-select form-select-sm lr-type"><option value="minutes">دقائق</option><option value="quarter_day">ربع يوم</option><option value="half_day">نصف يوم</option><option value="full_day">يوم كامل</option><option value="percentage">نسبة مئوية</option><option value="fixed_amount">مبلغ ثابت</option></select></td>
            <td><input type="number" class="form-control form-control-sm lr-value" min="0" step="0.01" placeholder="لنسبة/مبلغ"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
        </tr>`;
}

function resetEarlyRules() {
    document.getElementById('earlyRulesBody').innerHTML = `
        <tr class="early-rule-row">
            <td><input type="number" class="form-control form-control-sm er-min" min="0" value="1"></td>
            <td><input type="number" class="form-control form-control-sm er-max" min="0" placeholder="بدون حد"></td>
            <td><select class="form-select form-select-sm er-type"><option value="quarter_day">ربع يوم</option><option value="half_day">نصف يوم</option><option value="full_day">يوم كامل</option><option value="percentage">نسبة مئوية</option><option value="fixed_amount">مبلغ ثابت</option></select></td>
            <td><input type="number" class="form-control form-control-sm er-value" min="0" step="0.01" placeholder="لنسبة/مبلغ"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
        </tr>`;
}

function addLateRuleRow() {
    const t = document.querySelector('#lateRulesBody .late-rule-row');
    const clone = t.cloneNode(true);
    clone.querySelectorAll('input').forEach(i => i.value = '');
    clone.querySelector('.lr-min').value = '1';
    clone.querySelector('.lr-type').value = 'minutes';
    document.getElementById('lateRulesBody').appendChild(clone);
}

function addEarlyRuleRow() {
    const t = document.querySelector('#earlyRulesBody .early-rule-row');
    const clone = t.cloneNode(true);
    clone.querySelectorAll('input').forEach(i => i.value = '');
    clone.querySelector('.er-min').value = '1';
    clone.querySelector('.er-type').value = 'quarter_day';
    document.getElementById('earlyRulesBody').appendChild(clone);
}

function collectRuleRows(containerSelector, prefix) {
    const rows = document.querySelectorAll(containerSelector + ' tbody tr');
    const minuteKey = prefix === 'er' ? 'early' : 'delay';
    return Array.from(rows).map(row => ({
        ['min_' + minuteKey + '_minutes']: parseInt(row.querySelector('.' + prefix + '-min')?.value) || 0,
        ['max_' + minuteKey + '_minutes']: row.querySelector('.' + prefix + '-max')?.value ? parseInt(row.querySelector('.' + prefix + '-max').value) : null,
        deduction_type: row.querySelector('.' + prefix + '-type')?.value || 'minutes',
        deduction_value: row.querySelector('.' + prefix + '-value')?.value ? parseFloat(row.querySelector('.' + prefix + '-value').value) : null,
    }));
}

function openAddShiftModal() {
    document.getElementById('sf_id').value = '';
    document.getElementById('shiftForm').reset();
    document.getElementById('sf_start').value = '08:00';
    document.getElementById('sf_end').value = '';
    document.getElementById('sf_grace').value = '15';
    document.getElementById('sf_active').value = '1';
    document.getElementById('shiftModalTitle').innerHTML = '<i class="fas fa-clock me-2"></i> إضافة وردية جديدة';
    resetLateRules();
    resetEarlyRules();
    new bootstrap.Modal(document.getElementById('shiftModal')).show();
}

async function openEditShift(id) {
    document.getElementById('shiftModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i> تعديل الوردية';
    const modal = new bootstrap.Modal(document.getElementById('shiftModal'));
    modal.show();
    const r = await apiFetch('/shifts/' + id);
    if (!r.success) return;
    const s = r.data;
    document.getElementById('sf_id').value = s.id;
    document.getElementById('sf_name').value = s.name;
    document.getElementById('sf_start').value = s.start_time?.substring(0,5);
    document.getElementById('sf_end').value = s.end_time?.substring(0,5);
    document.getElementById('sf_grace').value = s.grace_period_minutes;
    document.getElementById('sf_active').value = s.is_active ? '1' : '0';

    // Late rules
    document.getElementById('lateRulesBody').innerHTML = '';
    (s.late_rules || []).forEach(r => {
        const row = document.createElement('tr');
        row.className = 'late-rule-row';
        row.innerHTML = `
            <td><input type="number" class="form-control form-control-sm lr-min" min="0" value="${r.min_delay_minutes}"></td>
            <td><input type="number" class="form-control form-control-sm lr-max" min="0" value="${r.max_delay_minutes ?? ''}" placeholder="بدون حد"></td>
            <td><select class="form-select form-select-sm lr-type">${['minutes','quarter_day','half_day','full_day','percentage','fixed_amount'].map(t => `<option value="${t}" ${t === r.deduction_type ? 'selected' : ''}>${deductionLabels[t]}</option>`).join('')}</select></td>
            <td><input type="number" class="form-control form-control-sm lr-value" min="0" step="0.01" value="${r.deduction_value ?? ''}" placeholder="لنسبة/مبلغ"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>`;
        document.getElementById('lateRulesBody').appendChild(row);
    });
    if (!s.late_rules?.length) resetLateRules();

    // Early exit rules
    document.getElementById('earlyRulesBody').innerHTML = '';
    (s.early_exit_rules || []).forEach(r => {
        const row = document.createElement('tr');
        row.className = 'early-rule-row';
        row.innerHTML = `
            <td><input type="number" class="form-control form-control-sm er-min" min="0" value="${r.min_early_minutes}"></td>
            <td><input type="number" class="form-control form-control-sm er-max" min="0" value="${r.max_early_minutes ?? ''}" placeholder="بدون حد"></td>
            <td><select class="form-select form-select-sm er-type">${['quarter_day','half_day','full_day','percentage','fixed_amount'].map(t => `<option value="${t}" ${t === r.deduction_type ? 'selected' : ''}>${deductionLabels[t]}</option>`).join('')}</select></td>
            <td><input type="number" class="form-control form-control-sm er-value" min="0" step="0.01" value="${r.deduction_value ?? ''}" placeholder="لنسبة/مبلغ"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>`;
        document.getElementById('earlyRulesBody').appendChild(row);
    });
    if (!s.early_exit_rules?.length) resetEarlyRules();
}

async function saveShift() {
    const id = document.getElementById('sf_id').value;
    const data = {
        name: document.getElementById('sf_name').value,
        start_time: document.getElementById('sf_start').value,
        end_time: document.getElementById('sf_end').value,
        grace_period_minutes: parseInt(document.getElementById('sf_grace').value) || 0,
        is_active: document.getElementById('sf_active').value === '1',
        late_rules: collectRuleRows('#lateRulesTable', 'lr'),
        early_exit_rules: collectRuleRows('#earlyRulesTable', 'er'),
    };
    if (!data.name || !data.start_time) { showAlert('يرجى ملء الحقول المطلوبة', 'warning'); return; }
    if (!data.end_time) data.end_time = null;
    const r = await apiFetch(id ? `/shifts/${id}` : '/shifts', { method: id ? 'PUT' : 'POST', body: JSON.stringify(data) });
    if (r.success) {
        bootstrap.Modal.getInstance(document.getElementById('shiftModal')).hide();
        showAlert(id ? 'تم تحديث الوردية' : 'تم إنشاء الوردية');
        loadShifts();
        loadShiftSelect();
    } else showAlert(r.message || 'فشل الحفظ', 'danger');
}

function confirmDeleteShift(id) {
    shiftDeleteId = id;
    shiftDeleteType = 'shift';
    document.getElementById('shiftDeleteMsg').textContent = 'حذف هذه الوردية نهائياً؟ لا يمكن التراجع.';
    new bootstrap.Modal(document.getElementById('shiftDeleteModal')).show();
}

document.getElementById('shiftDeleteBtn').addEventListener('click', async () => {
    if (!shiftDeleteId) return;
    const r = await apiFetch(`/${shiftDeleteType === 'shift' ? 'shifts' : 'employee-shifts'}/${shiftDeleteId}`, { method: 'DELETE' });
    bootstrap.Modal.getInstance(document.getElementById('shiftDeleteModal')).hide();
    if (r.success) { showAlert('تم الحذف'); loadShifts(); loadAssignments(); loadShiftSelect(); }
    else showAlert(r.message || 'فشل الحذف', 'danger');
    shiftDeleteId = null;
});

// ─── ASSIGNMENTS ─────────────────────────────────────────
async function loadAssignments() {
    const r = await apiFetch('/employee-shifts');
    if (!r.success) return;
    const all = r.data?.data ?? r.data ?? [];
    if (!all.length) {
        document.getElementById('assignmentsTable').innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">لا توجد تعيينات</td></tr>';
        return;
    }
    document.getElementById('assignmentsTable').innerHTML = all.map(a => `
        <tr>
            <td>${a.employee?.name ?? '-'}</td>
            <td>${a.shift?.name ?? '-'}</td>
            <td>${a.effective_from}</td>
            <td>${a.effective_to ?? 'مستمر'}</td>
            <td>
                <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteAssignment(${a.id})"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

function confirmDeleteAssignment(id) {
    shiftDeleteId = id;
    shiftDeleteType = 'assignment';
    document.getElementById('shiftDeleteMsg').textContent = 'إلغاء تعيين الوردية لهذا الموظف؟';
    new bootstrap.Modal(document.getElementById('shiftDeleteModal')).show();
}

let allEmployeesForAssign = [];

function openAssignModal() {
    document.getElementById('assignForm').reset();
    document.getElementById('af_from').value = '{{ date("Y-m-d") }}';
    document.getElementById('af_selectedCount').textContent = '0 محدد';
    loadShiftSelect();
    new bootstrap.Modal(document.getElementById('assignModal')).show();
    loadEmployeesForAssign();
}

async function loadEmployeesForAssign() {
    const box = document.getElementById('af_employees');
    box.innerHTML = '<div class="text-center text-muted py-3">جاري التحميل...</div>';
    const r = await apiFetch('/employees?per_page=1000&status=active');
    if (!r.success) {
        box.innerHTML = '<div class="text-danger p-2">فشل تحميل الموظفين</div>';
        return;
    }
    allEmployeesForAssign = r.data?.data ?? r.data ?? [];
    renderEmpChecks(allEmployeesForAssign);
}

function renderEmpChecks(list) {
    const box = document.getElementById('af_employees');
    if (!list.length) {
        box.innerHTML = '<div class="text-muted p-2">لا يوجد موظفون</div>';
        return;
    }
    box.innerHTML = list.map(e => `
        <label class="d-flex align-items-center gap-2 py-1 px-1 emp-check-row" data-name="${(e.name || '').toLowerCase()}" style="cursor:pointer;border-bottom:1px solid #f0f0f0">
            <input type="checkbox" class="af-emp-check" value="${e.id}" onchange="updateAssignCount()">
            <span class="fw-semibold">${e.name}</span>
            <small class="text-muted ms-auto">${e.employee_code ?? ''} · ${e.employee_type_label || e.employee_type || ''}</small>
        </label>
    `).join('');
    updateAssignCount();
}

function filterEmpChecks() {
    const q = (document.getElementById('af_search').value || '').toLowerCase().trim();
    document.querySelectorAll('#af_employees .emp-check-row').forEach(row => {
        row.style.display = !q || row.dataset.name.includes(q) ? '' : 'none';
    });
}

function toggleEmpChecks(checked) {
    document.querySelectorAll('#af_employees .emp-check-row').forEach(row => {
        if (row.style.display === 'none') return;
        const cb = row.querySelector('.af-emp-check');
        if (cb) cb.checked = checked;
    });
    updateAssignCount();
}

function updateAssignCount() {
    const n = document.querySelectorAll('.af-emp-check:checked').length;
    document.getElementById('af_selectedCount').textContent = n + ' محدد';
}

async function saveAssignment() {
    const employee_ids = [...document.querySelectorAll('.af-emp-check:checked')].map(cb => parseInt(cb.value));
    const shift_id = parseInt(document.getElementById('af_shift').value);
    const effective_from = document.getElementById('af_from').value;
    const effective_to = document.getElementById('af_to').value || null;

    if (!employee_ids.length) { showAlert('اختر موظف واحد على الأقل', 'warning'); return; }
    if (!shift_id) { showAlert('اختر الوردية', 'warning'); return; }

    const r = await apiFetch('/employee-shifts/bulk', {
        method: 'POST',
        body: JSON.stringify({ employee_ids, shift_id, effective_from, effective_to }),
    });
    if (r.success) {
        bootstrap.Modal.getInstance(document.getElementById('assignModal')).hide();
        showAlert(r.message || 'تم تعيين الوردية');
        loadAssignments();
    } else showAlert(r.message || 'فشل التعيين', 'danger');
}

// ─── INIT ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadShifts();
    loadAssignments();
    loadShiftSelect();
});
</script>
@endpush
