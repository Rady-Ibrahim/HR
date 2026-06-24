@extends('layouts.app')
@section('title', 'الحضور والانصراف')
@section('page-title', 'الحضور والانصراف')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-fingerprint me-2 text-primary"></i> الحضور والانصراف</h1>
        <div class="breadcrumb">تتبع حضور الموظفين</div>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" onclick="openLeaveModal()"><i class="fas fa-calendar-plus me-1"></i> طلب إجازة</button>
        <button class="btn-primary-custom" onclick="openAddAttModal()"><i class="fas fa-plus me-1"></i> إدخال حضور</button>
    </div>
</div>

<!-- TODAY SUMMARY -->
<div class="row g-3 mb-4" id="todaySummary">
    <div class="col-6 col-md-3"><div class="stat-card text-center"><div class="stat-icon mx-auto" style="background:#e8f5e9;color:#2e7d32"><i class="fas fa-user-check"></i></div><div class="stat-value" id="attPresent">-</div><div class="stat-label">حاضر</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card text-center"><div class="stat-icon mx-auto" style="background:#fce4ec;color:#c62828"><i class="fas fa-user-times"></i></div><div class="stat-value" id="attAbsent">-</div><div class="stat-label">غائب</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card text-center"><div class="stat-icon mx-auto" style="background:#fff3e0;color:#e65100"><i class="fas fa-clock"></i></div><div class="stat-value" id="attLate">-</div><div class="stat-label">متأخر</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card text-center"><div class="stat-icon mx-auto" style="background:#e3f2fd;color:#1565c0"><i class="fas fa-umbrella-beach"></i></div><div class="stat-value" id="attLeave">-</div><div class="stat-label">إجازة</div></div></div>
</div>

<!-- FILTERS -->
<div class="section-card mb-4">
    <div class="section-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">الموظف</label>
                <input type="text" id="empSearch" class="form-control" placeholder="بحث باسم الموظف">
            </div>
            <div class="col-md-2">
                <label class="form-label">الحالة</label>
                <select id="attStatus" class="form-select">
                    <option value="">الكل</option>
                    <option value="present">حاضر</option>
                    <option value="absent">غائب</option>
                    <option value="late">متأخر</option>
                    <option value="on_leave">إجازة</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">التاريخ من</label>
                <input type="date" id="dateFrom" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">إلى</label>
                <input type="date" id="dateTo" class="form-control">
            </div>
            <div class="col-md-2">
                <button class="btn-primary-custom w-100" onclick="loadAttendance()"><i class="fas fa-search me-1"></i> بحث</button>
            </div>
            <div class="col-md-1">
                <button class="btn btn-outline-secondary w-100" onclick="resetAttFilters()"><i class="fas fa-undo"></i></button>
            </div>
        </div>
    </div>
</div>

<!-- LEAVE REQUESTS -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><button class="nav-link active" onclick="showTab('attendance', this)"><i class="fas fa-list me-1"></i> سجل الحضور</button></li>
    <li class="nav-item"><button class="nav-link" onclick="showTab('leaves', this)"><i class="fas fa-calendar-times me-1"></i> طلبات الإجازات</button></li>
</ul>

<div id="tab-attendance">
    <div class="section-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>الموظف</th><th>التاريخ</th><th>وقت الحضور</th><th>وقت الانصراف</th><th>دقائق التأخير</th><th>الحالة</th><th>الموقع</th><th>إجراءات</th></tr>
                </thead>
                <tbody id="attTable">
                    <tr><td colspan="7" class="text-center py-4"><div class="spinner mx-auto" style="width:30px;height:30px;border-width:3px"></div></td></tr>
                </tbody>
            </table>
        </div>
        <div class="section-body d-flex justify-content-between">
            <div id="attPagInfo" class="text-muted" style="font-size:.8rem"></div>
            <div id="attPagination"></div>
        </div>
    </div>
</div>

<div id="tab-leaves" style="display:none">
    <div class="section-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>الموظف</th><th>نوع الإجازة</th><th>من</th><th>إلى</th><th>السبب</th><th>الحالة</th><th>إجراءات</th></tr>
                </thead>
                <tbody id="leavesTable">
                    <tr><td colspan="7" class="text-center py-4"><div class="spinner mx-auto" style="width:30px;height:30px;border-width:3px"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ═══ ADD ATTENDANCE MODAL ═══ -->
<div class="modal fade" id="attAddModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="attAddTitle"><i class="fas fa-fingerprint me-2"></i> إدخال حضور</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="attForm">
                    <input type="hidden" id="attId">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">الموظف (ID) *</label><input type="number" name="employee_id" id="atf_emp" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">التاريخ *</label><input type="date" name="date" id="atf_date" class="form-control" required value="{{ date('Y-m-d') }}"></div>
                        <div class="col-md-6"><label class="form-label">الحالة *</label>
                            <select name="status" id="atf_status" class="form-select" required>
                                <option value="present">حاضر</option><option value="absent">غائب</option>
                                <option value="late">متأخر</option><option value="on_leave">إجازة</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">وقت الحضور</label><input type="time" name="check_in_time" id="atf_in" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">وقت الانصراف</label><input type="time" name="check_out_time" id="atf_out" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">دقائق التأخير</label><input type="number" name="late_minutes" id="atf_late" class="form-control" min="0" value="0"></div>
                        <div class="col-12"><label class="form-label">ملاحظات</label><textarea name="notes" id="atf_notes" class="form-control" rows="2"></textarea></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn-primary-custom" onclick="saveAttendance()"><i class="fas fa-save me-1"></i> حفظ</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ ADD LEAVE MODAL ═══ -->
<div class="modal fade" id="leaveAddModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i> طلب إجازة</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="leaveForm">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">الموظف (ID) *</label><input type="number" name="employee_id" id="lf_emp" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">نوع الإجازة *</label>
                            <select name="leave_type" id="lf_type" class="form-select" required>
                                <option value="annual">سنوية</option><option value="sick">مرضية</option>
                                <option value="emergency">طارئة</option><option value="unpaid">بدون أجر</option><option value="other">أخرى</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">من تاريخ *</label><input type="date" name="start_date" id="lf_start" class="form-control" required value="{{ date('Y-m-d') }}"></div>
                        <div class="col-md-6"><label class="form-label">إلى تاريخ *</label><input type="date" name="end_date" id="lf_end" class="form-control" required value="{{ date('Y-m-d') }}"></div>
                        <div class="col-12"><label class="form-label">السبب</label><textarea name="reason" id="lf_reason" class="form-control" rows="2"></textarea></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" onclick="saveLeave()"><i class="fas fa-save me-1"></i> إرسال الطلب</button>
            </div>
        </div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="attDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm"><div class="modal-content">
        <div class="modal-header border-0"><h5 class="modal-title text-danger"><i class="fas fa-trash me-2"></i>تأكيد الحذف</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body text-center"><i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i><p>حذف سجل الحضور هذا؟</p></div>
        <div class="modal-footer border-0 justify-content-center">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
            <button type="button" class="btn btn-danger" id="attDeleteBtn">حذف</button>
        </div>
    </div></div>
</div>
@endsection

@push('scripts')
<script>
const attBadge = { present:'badge-active', absent:'badge-rejected', late:'badge-pending', on_leave:'badge-approved' };
const attLabel = { present:'حاضر', absent:'غائب', late:'متأخر', on_leave:'إجازة' };

async function loadTodaySummary() {
    const r = await apiFetch('/attendance/today-summary');
    if (!r.success) return;
    const d = r.data;
    document.getElementById('attPresent').textContent = d.present ?? 0;
    document.getElementById('attAbsent').textContent  = d.absent ?? 0;
    document.getElementById('attLate').textContent    = d.late ?? 0;
    document.getElementById('attLeave').textContent   = d.on_leave ?? 0;
}

async function loadAttendance(page = 1) {
    const params = new URLSearchParams({ per_page: 20, page });
    const s = document.getElementById('attStatus').value;
    const e = document.getElementById('empSearch').value;
    const f = document.getElementById('dateFrom').value;
    const t = document.getElementById('dateTo').value;
    if (s) params.append('status', s);
    if (e) params.append('search', e);
    if (f) params.append('date_from', f);
    if (t) params.append('date_to', t);

    const r = await apiFetch('/attendance?' + params);
    if (!r.success) return;
    const data = r.data;
    document.getElementById('attPagInfo').textContent = `إجمالي: ${data.total}`;
    const all = data.data;
    if (!all.length) {
        document.getElementById('attTable').innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">لا توجد سجلات</td></tr>';
        return;
    }
    document.getElementById('attTable').innerHTML = all.map(a => `
        <tr>
            <td>${a.employee?.name ?? '-'}</td>
            <td>${a.date}</td>
            <td>${a.check_in_time ?? '-'}</td>
            <td>${a.check_out_time ?? '-'}</td>
            <td>${a.late_minutes ?? 0} دقيقة</td>
            <td><span class="badge-status ${attBadge[a.status] || 'badge-draft'}">${attLabel[a.status] || a.status}</span></td>
            <td>${a.check_in_latitude ? `<span class="badge bg-info"><i class="fas fa-map-marker-alt"></i> GPS</span>` : '-'}</td>
            <td>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-warning" onclick="openEditAttModal(${a.id})" title="تعديل"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-outline-danger"  onclick="confirmDeleteAtt(${a.id})" title="حذف"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>
    `).join('');
    const pages = [];
    for (let i = 1; i <= Math.min(data.last_page, 10); i++) {
        pages.push(`<button class="btn btn-sm ${i === data.current_page ? 'btn-primary' : 'btn-outline-primary'} mx-1" onclick="loadAttendance(${i})">${i}</button>`);
    }
    document.getElementById('attPagination').innerHTML = pages.join('');
}

async function loadLeaves() {
    const r = await apiFetch('/attendance/leave-requests');
    if (!r.success) return;
    const all = r.data?.data ?? r.data ?? [];
    if (!all.length) {
        document.getElementById('leavesTable').innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">لا توجد طلبات إجازة</td></tr>';
        return;
    }
    document.getElementById('leavesTable').innerHTML = all.map(l => `
        <tr>
            <td>${l.employee?.name ?? '-'}</td>
            <td>${l.leave_type ?? '-'}</td>
            <td>${l.start_date}</td>
            <td>${l.end_date}</td>
            <td>${l.reason ?? '-'}</td>
            <td><span class="badge-status ${l.status === 'approved' ? 'badge-active' : l.status === 'rejected' ? 'badge-rejected' : 'badge-pending'}">${l.status === 'approved' ? 'معتمد' : l.status === 'rejected' ? 'مرفوض' : 'معلق'}</span></td>
            <td>${l.status === 'pending' ? `
                <button class="btn btn-sm btn-outline-success" onclick="approveLeave(${l.id})"><i class="fas fa-check"></i></button>
                <button class="btn btn-sm btn-outline-danger" onclick="rejectLeave(${l.id})"><i class="fas fa-times"></i></button>
            ` : '-'}</td>
        </tr>
    `).join('');
}

async function approveLeave(id) {
    const r = await apiFetch(`/attendance/leave-requests/${id}/approve`, { method: 'POST', body: JSON.stringify({ status: 'approved' }) });
    if (r.success) { showAlert('تم اعتماد الإجازة'); loadLeaves(); }
    else showAlert(r.message, 'danger');
}

async function rejectLeave(id) {
    const reason=prompt('سبب رفض الإجازة:'); if(!reason) return;
    const r = await apiFetch(`/attendance/leave-requests/${id}/reject`, { method: 'POST', body: JSON.stringify({ reason }) });
    if (r.success) { showAlert('تم رفض الإجازة','warning'); loadLeaves(); }
    else showAlert(r.message, 'danger');
}

// ─── ADD/EDIT ATTENDANCE ────────────────────────────────
let attDeleteId=null;

function openAddAttModal() {
    document.getElementById('attId').value=''; document.getElementById('attForm').reset();
    document.getElementById('attAddTitle').innerHTML='<i class="fas fa-fingerprint me-2"></i> إدخال حضور يدوي';
    document.getElementById('atf_date').value='{{ date("Y-m-d") }}';
    document.getElementById('atf_status').value='present';
    document.getElementById('atf_late').value='0';
    new bootstrap.Modal(document.getElementById('attAddModal')).show();
}

async function openEditAttModal(id) {
    document.getElementById('attAddTitle').innerHTML='<i class="fas fa-edit me-2"></i> تعديل سجل الحضور';
    new bootstrap.Modal(document.getElementById('attAddModal')).show();
    const r=await apiFetch('/attendance/'+id); if(!r.success) return; const a=r.data;
    document.getElementById('attId').value=a.id;
    document.getElementById('atf_emp').value=a.employee_id;
    document.getElementById('atf_date').value=a.date?a.date.substring(0,10):'';
    document.getElementById('atf_status').value=a.status;
    document.getElementById('atf_in').value=a.check_in_time??'';
    document.getElementById('atf_out').value=a.check_out_time??'';
    document.getElementById('atf_late').value=a.late_minutes??0;
    document.getElementById('atf_notes').value=a.notes??'';
}

async function saveAttendance() {
    const id=document.getElementById('attId').value;
    const data=Object.fromEntries(new FormData(document.getElementById('attForm')));
    data.employee_id=parseInt(data.employee_id);
    data.late_minutes=parseInt(data.late_minutes||0);
    if(!data.check_in_time) delete data.check_in_time;
    if(!data.check_out_time) delete data.check_out_time;
    if(!data.notes) delete data.notes;
    const r=await apiFetch(id?`/attendance/${id}`:'/attendance',{method:id?'PUT':'POST',body:JSON.stringify(data)});
    if(r.success){bootstrap.Modal.getInstance(document.getElementById('attAddModal')).hide();showAlert(id?'تم التحديث':'تم الإضافة');loadAttendance();}
    else showAlert(r.message||'فشل الحفظ','danger');
}

function confirmDeleteAtt(id) { attDeleteId=id; new bootstrap.Modal(document.getElementById('attDeleteModal')).show(); }
document.getElementById('attDeleteBtn').addEventListener('click', async()=>{
    if(!attDeleteId) return;
    const r=await apiFetch(`/attendance/${attDeleteId}`,{method:'DELETE'});
    bootstrap.Modal.getInstance(document.getElementById('attDeleteModal')).hide();
    if(r.success){showAlert('تم الحذف');loadAttendance();}else showAlert(r.message,'danger');
    attDeleteId=null;
});

// ─── ADD LEAVE ───────────────────────────────────────────
function openLeaveModal() {
    document.getElementById('leaveForm').reset();
    document.getElementById('lf_start').value='{{ date("Y-m-d") }}';
    document.getElementById('lf_end').value='{{ date("Y-m-d") }}';
    new bootstrap.Modal(document.getElementById('leaveAddModal')).show();
}
async function saveLeave() {
    const data=Object.fromEntries(new FormData(document.getElementById('leaveForm')));
    data.employee_id=parseInt(data.employee_id);
    const r=await apiFetch('/attendance/leave-requests',{method:'POST',body:JSON.stringify(data)});
    if(r.success){bootstrap.Modal.getInstance(document.getElementById('leaveAddModal')).hide();showAlert('تم إرسال طلب الإجازة');loadLeaves();}
    else showAlert(r.message||'فشل الإرسال','danger');
}

function showTab(tab, btn) {
    document.getElementById('tab-attendance').style.display = tab === 'attendance' ? '' : 'none';
    document.getElementById('tab-leaves').style.display    = tab === 'leaves' ? '' : 'none';
    document.querySelectorAll('.nav-tabs .nav-link').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    if (tab === 'leaves') loadLeaves();
}

function resetAttFilters() {
    ['empSearch','attStatus','dateFrom','dateTo'].forEach(id => document.getElementById(id).value = '');
    loadAttendance();
}

document.addEventListener('DOMContentLoaded', () => { loadTodaySummary(); loadAttendance(); });
</script>
@endpush
