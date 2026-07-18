@extends('layouts.app')
@section('title', 'الخصومات')
@section('page-title', 'الخصومات')

@section('content')
<div class="page-header">
    <div><h1><i class="fas fa-minus-circle me-2 text-danger"></i> الخصومات</h1><div class="breadcrumb">إدارة خصومات الموظفين</div></div>
    <button class="btn-primary-custom" onclick="openAddModal()"><i class="fas fa-plus me-1"></i> إضافة خصم</button>
</div>

<!-- FILTERS -->
<div class="section-card mb-4">
    <div class="section-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">الموظف</label><input type="text" id="dedEmp" class="form-control" placeholder="اسم الموظف..."></div>
            <div class="col-md-2"><label class="form-label">النوع</label>
                <select id="dedType" class="form-select"><option value="">الكل</option><option value="absence">غياب</option><option value="late">تأخر</option><option value="penalty">جزاء</option><option value="other">أخرى</option></select>
            </div>
            <div class="col-md-2"><label class="form-label">الشهر</label><input type="number" id="dedMonth" class="form-control" min="1" max="12" value="{{ date('n') }}"></div>
            <div class="col-md-2"><label class="form-label">السنة</label><input type="number" id="dedYear" class="form-control" value="{{ date('Y') }}"></div>
            <div class="col-md-2"><button class="btn-primary-custom w-100 mt-4" onclick="loadDeductions()"><i class="fas fa-search me-1"></i> بحث</button></div>
            <div class="col-md-1"><button class="btn btn-outline-secondary w-100 mt-4" onclick="resetDedFilter()"><i class="fas fa-undo"></i></button></div>
        </div>
    </div>
</div>

<!-- TABLE -->
<div class="section-card">
    <div class="section-header"><i class="fas fa-minus-circle text-danger"></i><h5 class="section-title">قائمة الخصومات</h5>
        <span class="ms-auto text-muted" style="font-size:.8rem" id="dedCount"></span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>الموظف</th><th>النوع</th><th>المبلغ</th><th>الشهر/السنة</th><th>السبب</th><th>الحالة</th><th>إجراءات</th></tr></thead>
            <tbody id="deductionsTable"><tr><td colspan="7" class="text-center py-4"><div class="spinner mx-auto" style="width:30px;height:30px;border-width:3px"></div></td></tr></tbody>
        </table>
    </div>
    <div class="section-body d-flex justify-content-between">
        <div id="dedPagInfo" class="text-muted" style="font-size:.8rem"></div>
        <div id="dedPagination"></div>
    </div>
</div>

<!-- ADD/EDIT MODAL -->
<div class="modal fade" id="dedModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="dedModalTitle"><i class="fas fa-minus-circle me-2 text-danger"></i> إضافة خصم</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="dedForm">
                    <input type="hidden" id="dedId">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">الموظف *</label><select name="employee_id" id="df_emp" class="form-select" data-lookup="employees" data-placeholder="اختر الموظف" required></select></div>
                        <div class="col-md-6"><label class="form-label">نوع الخصم *</label>
                            <select name="deduction_type" id="df_type" class="form-select" required>
                                <option value="absence">غياب</option><option value="late">تأخر</option>
                                <option value="penalty">جزاء</option><option value="other">أخرى</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">المبلغ *</label><div class="input-group"><input type="number" name="amount" id="df_amount" class="form-control" required><span class="input-group-text">ج.م</span></div></div>
                        <div class="col-md-6"><label class="form-label">الشهر *</label><input type="number" name="month" id="df_month" class="form-control" min="1" max="12" required value="{{ date('n') }}"></div>
                        <div class="col-md-6"><label class="form-label">السنة *</label><input type="number" name="year" id="df_year" class="form-control" required value="{{ date('Y') }}"></div>
                        <div class="col-12"><label class="form-label">السبب</label><textarea name="reason" id="df_reason" class="form-control" rows="2"></textarea></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger" onclick="saveDeduction()"><i class="fas fa-save me-1"></i> حفظ</button>
            </div>
        </div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="dedDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm"><div class="modal-content">
        <div class="modal-header border-0"><h5 class="modal-title text-danger"><i class="fas fa-trash me-2"></i>تأكيد الحذف</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body text-center"><i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i><p>حذف هذا الخصم نهائياً؟</p></div>
        <div class="modal-footer border-0 justify-content-center">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
            <button type="button" class="btn btn-danger" id="dedDeleteBtn">حذف</button>
        </div>
    </div></div>
</div>
@endsection

@push('scripts')
<script>
let dedDeleteId=null, dedPage=1;
const dedTypes = { absence:'غياب', late:'تأخر', penalty:'جزاء', other:'أخرى' };

async function loadDeductions(page=1) {
    dedPage=page;
    const params = new URLSearchParams({ per_page:15, page });
    const t=document.getElementById('dedType').value; if(t) params.append('deduction_type',t);
    const m=document.getElementById('dedMonth').value; if(m) params.append('month',m);
    const y=document.getElementById('dedYear').value; if(y) params.append('year',y);
    const e=document.getElementById('dedEmp').value; if(e) params.append('search',e);
    const r = await apiFetch('/deductions?'+params);
    if (!r.success) return;
    const { data, total, current_page, last_page } = r.data;
    document.getElementById('dedCount').textContent=`إجمالي: ${total}`;
    document.getElementById('dedPagInfo').textContent=`صفحة ${current_page} من ${last_page}`;
    if (!data.length) { document.getElementById('deductionsTable').innerHTML='<tr><td colspan="7" class="text-center py-4 text-muted">لا توجد خصومات</td></tr>'; return; }
    document.getElementById('deductionsTable').innerHTML = data.map(d=>`
        <tr>
            <td>${d.employee?.name??'-'}</td>
            <td>${dedTypes[d.deduction_type]||d.deduction_type}</td>
            <td class="fw-bold text-danger">${Number(d.amount).toLocaleString()} ج.م</td>
            <td>${d.month}/${d.year}</td>
            <td>${d.reason??'-'}</td>
            <td><span class="badge-status ${d.status==='approved'?'badge-active':d.status==='rejected'?'badge-rejected':'badge-pending'}">${d.status==='approved'?'معتمد':d.status==='rejected'?'مرفوض':'معلق'}</span></td>
            <td>
                <div class="d-flex gap-1 flex-wrap">
                    ${d.status==='pending'?`<button class="btn btn-sm btn-outline-warning" onclick="openEditModal(${d.id})"><i class="fas fa-edit"></i></button>`:''}
                    ${d.status==='pending'?`
                        <button class="btn btn-sm btn-success" onclick="approveDed(${d.id})"><i class="fas fa-check"></i></button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="rejectDed(${d.id})"><i class="fas fa-times"></i></button>
                    `:''}
                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(${d.id})"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>`).join('');
    const pages=[];
    for(let i=1;i<=last_page;i++) pages.push(`<button class="btn btn-sm ${i===current_page?'btn-primary':'btn-outline-primary'} mx-1" onclick="loadDeductions(${i})">${i}</button>`);
    document.getElementById('dedPagination').innerHTML=pages.join('');
}

function openAddModal() {
    document.getElementById('dedId').value=''; document.getElementById('dedForm').reset();
    document.getElementById('dedModalTitle').innerHTML='<i class="fas fa-minus-circle me-2 text-danger"></i> إضافة خصم جديد';
    document.getElementById('df_month').value='{{ date("n") }}';
    document.getElementById('df_year').value='{{ date("Y") }}';
    new bootstrap.Modal(document.getElementById('dedModal')).show();
}
async function openEditModal(id) {
    document.getElementById('dedModalTitle').innerHTML='<i class="fas fa-edit me-2"></i> تعديل الخصم';
    new bootstrap.Modal(document.getElementById('dedModal')).show();
    const r=await apiFetch('/deductions/'+id); if(!r.success) return; const d=r.data;
    document.getElementById('dedId').value=d.id;
    document.getElementById('df_emp').value=d.employee_id;
    document.getElementById('df_type').value=d.deduction_type;
    document.getElementById('df_amount').value=d.amount;
    document.getElementById('df_month').value=d.month;
    document.getElementById('df_year').value=d.year;
    document.getElementById('df_reason').value=d.reason??'';
}
async function saveDeduction() {
    const id=document.getElementById('dedId').value;
    const data=Object.fromEntries(new FormData(document.getElementById('dedForm')));
    data.amount=parseFloat(data.amount); data.month=parseInt(data.month); data.year=parseInt(data.year); data.employee_id=parseInt(data.employee_id);
    const r=await apiFetch(id?`/deductions/${id}`:'/deductions',{method:id?'PUT':'POST',body:JSON.stringify(data)});
    if(r.success){bootstrap.Modal.getInstance(document.getElementById('dedModal')).hide();showAlert(id?'تم التحديث':'تم الإضافة');loadDeductions(dedPage);}
    else showAlert(r.message||'فشل الحفظ','danger');
}
async function approveDed(id) {
    const r=await apiFetch(`/deductions/${id}/approve`,{method:'POST'});
    if(r.success){showAlert('تم الاعتماد');loadDeductions(dedPage);}else showAlert(r.message,'danger');
}
async function rejectDed(id) {
    const reason=prompt('سبب الرفض:'); if(!reason) return;
    const r=await apiFetch(`/deductions/${id}/reject`,{method:'POST',body:JSON.stringify({reason})});
    if(r.success){showAlert('تم الرفض','warning');loadDeductions(dedPage);}else showAlert(r.message,'danger');
}
function confirmDelete(id) { dedDeleteId=id; new bootstrap.Modal(document.getElementById('dedDeleteModal')).show(); }
document.getElementById('dedDeleteBtn').addEventListener('click', async()=>{
    if(!dedDeleteId) return;
    const r=await apiFetch(`/deductions/${dedDeleteId}`,{method:'DELETE'});
    bootstrap.Modal.getInstance(document.getElementById('dedDeleteModal')).hide();
    if(r.success){showAlert('تم الحذف');loadDeductions(dedPage);}else showAlert(r.message,'danger');
    dedDeleteId=null;
});
function resetDedFilter() {
    document.getElementById('dedEmp').value=''; document.getElementById('dedType').value='';
    document.getElementById('dedMonth').value='{{ date("n") }}'; document.getElementById('dedYear').value='{{ date("Y") }}';
    loadDeductions();
}
document.addEventListener('DOMContentLoaded', loadDeductions);
</script>
@endpush
