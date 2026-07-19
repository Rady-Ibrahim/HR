@extends('layouts.app')
@section('title', 'البدلات')
@section('page-title', 'البدلات')

@section('content')
<div class="page-header">
    <div><h1><i class="fas fa-plus-circle me-2 text-success"></i> البدلات</h1><div class="breadcrumb">إدارة بدلات الموظفين</div></div>
    <button class="btn-primary-custom" onclick="openAddModal()"><i class="fas fa-plus me-1"></i> إضافة بدل</button>
</div>

<!-- FILTERS -->
<div class="section-card mb-4">
    <div class="section-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">الموظف</label><input type="text" id="allEmp" class="form-control" placeholder="اسم الموظف..."></div>
            <div class="col-md-2"><label class="form-label">النوع</label>
                <input type="text" id="allType" class="form-control" list="allowanceTypeSuggestions" placeholder="الكل / ابحث بنوع">
            </div>
            <div class="col-md-2"><label class="form-label">الشهر</label><input type="number" id="allMonth" class="form-control" min="1" max="12" value="{{ date('n') }}"></div>
            <div class="col-md-2"><label class="form-label">السنة</label><input type="number" id="allYear" class="form-control" value="{{ date('Y') }}"></div>
            <div class="col-md-2"><button class="btn-primary-custom w-100 mt-4" onclick="loadAllowances()"><i class="fas fa-search me-1"></i> بحث</button></div>
            <div class="col-md-1"><button class="btn btn-outline-secondary w-100 mt-4" onclick="resetAllFilter()"><i class="fas fa-undo"></i></button></div>
        </div>
    </div>
</div>

<!-- TABLE -->
<div class="section-card">
    <div class="section-header"><i class="fas fa-plus-circle text-success"></i><h5 class="section-title">قائمة البدلات</h5>
        <span class="ms-auto text-muted" style="font-size:.8rem" id="allCount"></span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>الموظف</th><th>النوع</th><th>المبلغ</th><th>الشهر/السنة</th><th>متكرر</th><th>ملاحظات</th><th>الحالة</th><th>إجراءات</th></tr></thead>
            <tbody id="allowancesTable"><tr><td colspan="8" class="text-center py-4"><div class="spinner mx-auto" style="width:30px;height:30px;border-width:3px"></div></td></tr></tbody>
        </table>
    </div>
    <div class="section-body d-flex justify-content-between">
        <div id="allPagInfo" class="text-muted" style="font-size:.8rem"></div>
        <div id="allPagination"></div>
    </div>
</div>

<!-- ADD/EDIT MODAL -->
<div class="modal fade" id="allModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="allModalTitle"><i class="fas fa-plus-circle me-2 text-success"></i> إضافة بدل</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="allForm">
                    <input type="hidden" id="allId">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">الموظف *</label><select name="employee_id" id="alf_emp" class="form-select" data-lookup="employees" data-placeholder="اختر الموظف" required></select></div>
                        <div class="col-md-6"><label class="form-label">نوع البدل / اسم الخدمة *</label>
                            <input type="text" name="allowance_type" id="alf_type" class="form-control" list="allowanceTypeSuggestions" required placeholder="مثال: بدل إجازات، ساعات إضافية، مواصلات">
                            <small class="text-muted">اكتب نوعًا جديدًا أو اختر من المقترحات</small>
                        </div>
                        <datalist id="allowanceTypeSuggestions"></datalist>
                        <div class="col-md-6"><label class="form-label">المبلغ *</label><div class="input-group"><input type="number" name="amount" id="alf_amount" class="form-control" required><span class="input-group-text">ج.م</span></div></div>
                        <div class="col-md-6"><label class="form-label">الشهر *</label><input type="number" name="month" id="alf_month" class="form-control" min="1" max="12" required value="{{ date('n') }}"></div>
                        <div class="col-md-6"><label class="form-label">السنة *</label><input type="number" name="year" id="alf_year" class="form-control" required value="{{ date('Y') }}"></div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="is_recurring" id="alf_recurring" class="form-check-input" value="1">
                                <label class="form-check-label" for="alf_recurring">بدل متكرر شهرياً</label>
                            </div>
                        </div>
                        <div class="col-12"><label class="form-label">ملاحظات</label><textarea name="notes" id="alf_notes" class="form-control" rows="2"></textarea></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-success" onclick="saveAllowance()"><i class="fas fa-save me-1"></i> حفظ</button>
            </div>
        </div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="allDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm"><div class="modal-content">
        <div class="modal-header border-0"><h5 class="modal-title text-danger"><i class="fas fa-trash me-2"></i>تأكيد الحذف</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body text-center"><i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i><p>حذف هذا البدل نهائياً؟</p></div>
        <div class="modal-footer border-0 justify-content-center">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
            <button type="button" class="btn btn-danger" id="allDeleteBtn">حذف</button>
        </div>
    </div></div>
</div>
@endsection

@push('scripts')
<script>
let allDeleteId=null, allPage=1;
const defaultAllowanceTypes = ['مواصلات', 'سكن', 'وجبات', 'هاتف', 'بدل إجازات', 'ساعات إضافية', 'بدل انتقال', 'أخرى'];

async function loadAllowanceTypeSuggestions() {
    const list = document.getElementById('allowanceTypeSuggestions');
    const types = new Set(defaultAllowanceTypes);
    try {
        const r = await apiFetch('/allowances/types');
        if (r.success && Array.isArray(r.data)) {
            r.data.forEach(t => { if (t) types.add(t); });
        }
    } catch (_) {}
    list.innerHTML = [...types].map(t => `<option value="${t}"></option>`).join('');
}

async function loadAllowances(page=1) {
    allPage=page;
    const params = new URLSearchParams({ per_page:15, page });
    const t=document.getElementById('allType').value.trim(); if(t) params.append('allowance_type',t);
    const m=document.getElementById('allMonth').value; if(m) params.append('month',m);
    const y=document.getElementById('allYear').value; if(y) params.append('year',y);
    const e=document.getElementById('allEmp').value; if(e) params.append('search',e);
    const r = await apiFetch('/allowances?'+params);
    if (!r.success) return;
    const { data, total, current_page, last_page } = r.data;
    document.getElementById('allCount').textContent=`إجمالي: ${total}`;
    document.getElementById('allPagInfo').textContent=`صفحة ${current_page} من ${last_page}`;
    if (!data.length) { document.getElementById('allowancesTable').innerHTML='<tr><td colspan="8" class="text-center py-4 text-muted">لا توجد بدلات</td></tr>'; return; }
    document.getElementById('allowancesTable').innerHTML = data.map(a=>`
        <tr>
            <td>${a.employee?.name??'-'}</td>
            <td><span class="badge-status badge-approved">${a.allowance_type||'-'}</span></td>
            <td class="fw-bold text-success">${Number(a.amount).toLocaleString()} ج.م</td>
            <td>${a.month}/${a.year}</td>
            <td>${a.is_recurring?'<i class="fas fa-check text-success"></i>':'-'}</td>
            <td>${a.notes??'-'}</td>
            <td><span class="badge-status ${a.status==='active'?'badge-active':a.status==='inactive'?'badge-inactive':'badge-pending'}">${a.status==='active'?'نشط':a.status==='inactive'?'غير نشط':(a.status??'-')}</span></td>
            <td>
                <div class="d-flex gap-1 flex-wrap">
                    <button class="btn btn-sm btn-outline-warning" onclick="openEditModal(${a.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(${a.id})"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>`).join('');
    const pages=[];
    for(let i=1;i<=last_page;i++) pages.push(`<button class="btn btn-sm ${i===current_page?'btn-primary':'btn-outline-primary'} mx-1" onclick="loadAllowances(${i})">${i}</button>`);
    document.getElementById('allPagination').innerHTML=pages.join('');
}

function openAddModal() {
    document.getElementById('allId').value=''; document.getElementById('allForm').reset();
    document.getElementById('allModalTitle').innerHTML='<i class="fas fa-plus-circle me-2 text-success"></i> إضافة بدل جديد';
    document.getElementById('alf_month').value='{{ date("n") }}';
    document.getElementById('alf_year').value='{{ date("Y") }}';
    document.getElementById('alf_type').value='';
    loadAllowanceTypeSuggestions();
    new bootstrap.Modal(document.getElementById('allModal')).show();
}
async function openEditModal(id) {
    document.getElementById('allModalTitle').innerHTML='<i class="fas fa-edit me-2"></i> تعديل البدل';
    await loadAllowanceTypeSuggestions();
    new bootstrap.Modal(document.getElementById('allModal')).show();
    const r=await apiFetch('/allowances/'+id); if(!r.success) return; const a=r.data;
    document.getElementById('allId').value=a.id;
    document.getElementById('alf_emp').value=a.employee_id;
    document.getElementById('alf_type').value=a.allowance_type??'';
    document.getElementById('alf_amount').value=a.amount;
    document.getElementById('alf_month').value=a.month;
    document.getElementById('alf_year').value=a.year;
    document.getElementById('alf_recurring').checked=!!a.is_recurring;
    document.getElementById('alf_notes').value=a.notes??'';
}
async function saveAllowance() {
    const id=document.getElementById('allId').value;
    const fd=new FormData(document.getElementById('allForm'));
    const data=Object.fromEntries(fd);
    data.allowance_type=(data.allowance_type||'').trim();
    if(!data.allowance_type){ showAlert('اكتب نوع البدل / اسم الخدمة','danger'); return; }
    data.amount=parseFloat(data.amount); data.month=parseInt(data.month); data.year=parseInt(data.year); data.employee_id=parseInt(data.employee_id);
    data.is_recurring=document.getElementById('alf_recurring').checked;
    const r=await apiFetch(id?`/allowances/${id}`:'/allowances',{method:id?'PUT':'POST',body:JSON.stringify(data)});
    if(r.success){bootstrap.Modal.getInstance(document.getElementById('allModal')).hide();showAlert(id?'تم التحديث':'تم الإضافة');loadAllowanceTypeSuggestions();loadAllowances(allPage);}
    else showAlert(r.message||'فشل الحفظ','danger');
}
function confirmDelete(id) { allDeleteId=id; new bootstrap.Modal(document.getElementById('allDeleteModal')).show(); }
document.getElementById('allDeleteBtn').addEventListener('click', async()=>{
    if(!allDeleteId) return;
    const r=await apiFetch(`/allowances/${allDeleteId}`,{method:'DELETE'});
    bootstrap.Modal.getInstance(document.getElementById('allDeleteModal')).hide();
    if(r.success){showAlert('تم الحذف');loadAllowances(allPage);}else showAlert(r.message,'danger');
    allDeleteId=null;
});
function resetAllFilter() {
    document.getElementById('allEmp').value=''; document.getElementById('allType').value='';
    document.getElementById('allMonth').value='{{ date("n") }}'; document.getElementById('allYear').value='{{ date("Y") }}';
    loadAllowances();
}
document.addEventListener('DOMContentLoaded', () => { loadAllowanceTypeSuggestions(); loadAllowances(); });
</script>
@endpush
