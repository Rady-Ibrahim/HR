@extends('layouts.app')
@section('title', 'التحصيلات')
@section('page-title', 'التحصيلات')

@section('content')
<div class="page-header">
    <div><h1><i class="fas fa-cash-register me-2 text-primary"></i> التحصيلات</h1><div class="breadcrumb">إدارة التحصيلات النقدية</div></div>
    <button class="btn-primary-custom" onclick="openAddModal()"><i class="fas fa-plus me-1"></i> إضافة تحصيل</button>
</div>

<!-- DAILY SUMMARY -->
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card text-center"><div class="stat-value text-primary" id="sumTotal">-</div><div class="stat-label">إجمالي اليوم</div></div></div>
    <div class="col-md-3"><div class="stat-card text-center"><div class="stat-value text-success" id="sumCash">-</div><div class="stat-label">نقدي</div></div></div>
    <div class="col-md-3"><div class="stat-card text-center"><div class="stat-value text-info" id="sumCheck">-</div><div class="stat-label">شيكات</div></div></div>
    <div class="col-md-3"><div class="stat-card text-center"><div class="stat-value text-warning" id="sumPending">-</div><div class="stat-label">في انتظار الموافقة</div></div></div>
</div>

<!-- FILTERS -->
<div class="section-card mb-4">
    <div class="section-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">بحث</label><input type="text" id="colSearch" class="form-control" placeholder="اسم المندوب، العميل..."></div>
            <div class="col-md-2"><label class="form-label">طريقة الدفع</label>
                <select id="colPayment" class="form-select"><option value="">الكل</option><option value="cash">نقدي</option><option value="check">شيك</option><option value="bank_transfer">تحويل</option><option value="other">أخرى</option></select>
            </div>
            <div class="col-md-2"><label class="form-label">الحالة</label>
                <select id="colStatus" class="form-select"><option value="">الكل</option><option value="pending">معلق</option><option value="approved">معتمد</option><option value="rejected">مرفوض</option></select>
            </div>
            <div class="col-md-2"><label class="form-label">التاريخ</label><input type="date" id="colDate" class="form-control"></div>
            <div class="col-md-1"><button class="btn-primary-custom w-100 mt-4" onclick="loadCollections()"><i class="fas fa-search"></i></button></div>
            <div class="col-md-1"><button class="btn btn-outline-secondary w-100 mt-4" onclick="resetColFilter()"><i class="fas fa-undo"></i></button></div>
        </div>
    </div>
</div>

<!-- TABLE -->
<div class="section-card">
    <div class="section-header"><i class="fas fa-cash-register text-primary"></i><h5 class="section-title">قائمة التحصيلات</h5>
        <span class="ms-auto text-muted" style="font-size:.8rem" id="colCount"></span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>#</th><th>المندوب</th><th>العميل</th><th>المبلغ</th><th>طريقة الدفع</th><th>التاريخ</th><th>الحالة</th><th>إجراءات</th></tr></thead>
            <tbody id="colTable"><tr><td colspan="8" class="text-center py-4"><div class="spinner mx-auto" style="width:30px;height:30px;border-width:3px"></div></td></tr></tbody>
        </table>
    </div>
    <div class="section-body d-flex justify-content-between">
        <div id="colPagInfo" class="text-muted" style="font-size:.8rem"></div>
        <div id="colPagination"></div>
    </div>
</div>

<!-- ADD/EDIT MODAL -->
<div class="modal fade" id="colModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="colModalTitle"><i class="fas fa-cash-register me-2"></i> إضافة تحصيل</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="colForm">
                    <input type="hidden" id="colId">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">المندوب *</label><select name="employee_id" id="clf_emp" class="form-select" data-lookup="employees" data-placeholder="اختر المندوب" required></select></div>
                        <div class="col-md-6"><label class="form-label">التسليمة</label><select name="delivery_id" id="clf_delivery" class="form-select" data-lookup="deliveries" data-placeholder="اختر التسليمة"></select></div>
                        <div class="col-md-6"><label class="form-label">العميل</label><select name="customer_id" id="clf_customer" class="form-select" data-lookup="customers" data-placeholder="اختر العميل"></select></div>
                        <div class="col-md-6"><label class="form-label">المبلغ *</label><div class="input-group"><input type="number" name="amount" id="clf_amount" class="form-control" required step="0.01"><span class="input-group-text">ج.م</span></div></div>
                        <div class="col-md-6"><label class="form-label">طريقة الدفع *</label>
                            <select name="payment_method" id="clf_method" class="form-select" required>
                                <option value="cash">نقدي</option><option value="check">شيك</option><option value="bank_transfer">تحويل بنكي</option><option value="other">أخرى</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">تاريخ التحصيل *</label><input type="date" name="collection_date" id="clf_date" class="form-control" required value="{{ date('Y-m-d') }}"></div>
                        <div class="col-md-6" id="checkFields" style="display:none"><label class="form-label">رقم الشيك</label><input type="text" name="check_number" id="clf_check_no" class="form-control"></div>
                        <div class="col-md-6" id="bankFields"  style="display:none"><label class="form-label">رقم المرجع</label><input type="text" name="reference_number" id="clf_ref" class="form-control"></div>
                        <div class="col-12"><label class="form-label">ملاحظات</label><textarea name="notes" id="clf_notes" class="form-control" rows="2"></textarea></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn-primary-custom" onclick="saveCollection()"><i class="fas fa-save me-1"></i> حفظ</button>
            </div>
        </div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="colDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm"><div class="modal-content">
        <div class="modal-header border-0"><h5 class="modal-title text-danger"><i class="fas fa-trash me-2"></i>تأكيد الحذف</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body text-center"><i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i><p>حذف هذا التحصيل نهائياً؟</p></div>
        <div class="modal-footer border-0 justify-content-center">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
            <button type="button" class="btn btn-danger" id="colDeleteBtn">حذف</button>
        </div>
    </div></div>
</div>
@endsection

@push('scripts')
<script>
let colDeleteId=null, colPage=1;
const paymentLabels = { cash:'نقدي', check:'شيك', bank_transfer:'تحويل بنكي', instapay:'إنستاباي', fawry:'فوري', other:'أخرى' };

document.getElementById('clf_method').addEventListener('change', function() {
    document.getElementById('checkFields').style.display = this.value==='check' ? '' : 'none';
    document.getElementById('bankFields').style.display  = this.value==='bank_transfer' ? '' : 'none';
});

async function loadDailySummary() {
    const r = await apiFetch('/collections/daily-summary?date={{ date("Y-m-d") }}');
    if (!r.success) return;
    const rows = r.data || [];
    const byMethod = rows.reduce((acc, row) => {
        acc[row.payment_method] = (acc[row.payment_method] || 0) + Number(row.total || 0);
        acc.pendingCount += row.collection_status === 'pending' ? Number(row.count || 0) : 0;
        return acc;
    }, { pendingCount: 0 });
    document.getElementById('sumTotal').textContent   = r.total ? Number(r.total).toLocaleString()+' ج.م' : '0 ج.م';
    document.getElementById('sumCash').textContent    = byMethod.cash ? Number(byMethod.cash).toLocaleString()+' ج.م' : '0 ج.م';
    document.getElementById('sumCheck').textContent   = byMethod.check ? Number(byMethod.check).toLocaleString()+' ج.م' : '0 ج.م';
    document.getElementById('sumPending').textContent = byMethod.pendingCount + ' طلب';
}

async function loadCollections(page=1) {
    colPage=page;
    const params = new URLSearchParams({ per_page:15, page });
    const s=document.getElementById('colSearch').value; if(s) params.append('search',s);
    const pm=document.getElementById('colPayment').value; if(pm) params.append('payment_method',pm);
    const st=document.getElementById('colStatus').value; if(st) params.append('status',st);
    const dt=document.getElementById('colDate').value; if(dt) params.append('date',dt);
    const r = await apiFetch('/collections?'+params);
    if (!r.success) return;
    const { data, total, current_page, last_page } = r.data;
    document.getElementById('colCount').textContent=`إجمالي: ${total}`;
    document.getElementById('colPagInfo').textContent=`صفحة ${current_page} من ${last_page}`;
    if (!data.length) { document.getElementById('colTable').innerHTML='<tr><td colspan="8" class="text-center py-4 text-muted">لا توجد تحصيلات</td></tr>'; return; }
    document.getElementById('colTable').innerHTML = data.map(c=>`
        <tr>
            <td>#${c.id}</td>
            <td>${c.driver?.name??'-'}</td>
            <td>${c.customer?.name??c.delivery?.request?.customer?.name??'-'}</td>
            <td class="fw-bold">${Number(c.total_amount ?? 0).toLocaleString()} ج.م</td>
            <td>${paymentLabels[c.payment_method]||c.payment_method}</td>
            <td>${c.collected_date?new Date(c.collected_date).toLocaleDateString('ar-EG'):'-'}</td>
            <td><span class="badge-status ${collectionBadge(c.collection_status)}">${collectionLabel(c.collection_status)}</span></td>
            <td>
                <div class="d-flex gap-1 flex-wrap">
                    <button class="btn btn-sm btn-outline-warning" onclick="openEditModal(${c.id})"><i class="fas fa-edit"></i></button>
                    ${c.collection_status==='pending'?`
                        <button class="btn btn-sm btn-success" onclick="approveCol(${c.id})"><i class="fas fa-check"></i></button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="rejectCol(${c.id})"><i class="fas fa-times"></i></button>
                    `:''}
                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(${c.id})"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>`).join('');
    const pages=[];
    for(let i=1;i<=last_page;i++) pages.push(`<button class="btn btn-sm ${i===current_page?'btn-primary':'btn-outline-primary'} mx-1" onclick="loadCollections(${i})">${i}</button>`);
    document.getElementById('colPagination').innerHTML=pages.join('');
}

function openAddModal() {
    document.getElementById('colId').value=''; document.getElementById('colForm').reset();
    document.getElementById('colModalTitle').innerHTML='<i class="fas fa-cash-register me-2"></i> إضافة تحصيل جديد';
    document.getElementById('clf_date').value='{{ date("Y-m-d") }}';
    document.getElementById('clf_method').value='cash';
    document.getElementById('checkFields').style.display='none'; document.getElementById('bankFields').style.display='none';
    new bootstrap.Modal(document.getElementById('colModal')).show();
}
async function openEditModal(id) {
    document.getElementById('colModalTitle').innerHTML='<i class="fas fa-edit me-2"></i> تعديل التحصيل';
    new bootstrap.Modal(document.getElementById('colModal')).show();
    const r=await apiFetch('/collections/'+id); if(!r.success) return; const c=r.data;
    document.getElementById('colId').value=c.id;
    document.getElementById('clf_emp').value=c.driver_id??'';
    document.getElementById('clf_delivery').value=c.delivery_id??'';
    document.getElementById('clf_customer').value=c.customer_id??'';
    document.getElementById('clf_amount').value=c.total_amount;
    document.getElementById('clf_method').value=c.payment_method;
    document.getElementById('clf_date').value=c.collected_date?c.collected_date.substring(0,10):'';
    document.getElementById('clf_check_no').value=c.check_number??'';
    document.getElementById('clf_ref').value=c.reference_number??'';
    document.getElementById('clf_notes').value=c.notes??'';
    document.getElementById('checkFields').style.display=c.payment_method==='check'?'':'none';
    document.getElementById('bankFields').style.display=c.payment_method==='bank_transfer'?'':'none';
}
async function saveCollection() {
    const id=document.getElementById('colId').value;
    const data=Object.fromEntries(new FormData(document.getElementById('colForm')));
    data.total_amount=parseFloat(data.amount); delete data.amount;
    data.driver_id=parseInt(data.employee_id); delete data.employee_id;
    data.collected_date=data.collection_date; delete data.collection_date;
    if(data.delivery_id) data.delivery_id=parseInt(data.delivery_id); else delete data.delivery_id;
    delete data.customer_id;
    if(!data.check_number) delete data.check_number;
    if(!data.reference_number) delete data.reference_number;
    const r=await apiFetch(id?`/collections/${id}`:'/collections',{method:id?'PUT':'POST',body:JSON.stringify(data)});
    if(r.success){bootstrap.Modal.getInstance(document.getElementById('colModal')).hide();showAlert(id?'تم التحديث':'تم الإضافة');loadCollections(colPage);loadDailySummary();}
    else showAlert(r.message||'فشل الحفظ','danger');
}
async function approveCol(id) {
    const r=await apiFetch(`/collections/${id}/approve`,{method:'POST'});
    if(r.success){showAlert('تم الاعتماد');loadCollections(colPage);loadDailySummary();}else showAlert(r.message,'danger');
}
async function rejectCol(id) {
    const reason=prompt('سبب الرفض:'); if(!reason) return;
    const r=await apiFetch(`/collections/${id}/reject`,{method:'POST',body:JSON.stringify({reason})});
    if(r.success){showAlert('تم الرفض','warning');loadCollections(colPage);}else showAlert(r.message,'danger');
}
function confirmDelete(id) { colDeleteId=id; new bootstrap.Modal(document.getElementById('colDeleteModal')).show(); }
document.getElementById('colDeleteBtn').addEventListener('click', async()=>{
    if(!colDeleteId) return;
    const r=await apiFetch(`/collections/${colDeleteId}`,{method:'DELETE'});
    bootstrap.Modal.getInstance(document.getElementById('colDeleteModal')).hide();
    if(r.success){showAlert('تم الحذف');loadCollections(colPage);}else showAlert(r.message,'danger');
    colDeleteId=null;
});
function resetColFilter() { ['colSearch','colPayment','colStatus','colDate'].forEach(id=>document.getElementById(id).value=''); loadCollections(); }
function collectionLabel(status) { return { pending:'معلق', collected:'محصل تلقائي', deposited:'معتمد', rejected:'مرفوض' }[status] || status; }
function collectionBadge(status) { return { pending:'badge-pending', collected:'badge-approved', deposited:'badge-active', rejected:'badge-rejected' }[status] || 'badge-pending'; }
document.getElementById('colSearch').addEventListener('keypress', e=>{ if(e.key==='Enter') loadCollections(); });
document.addEventListener('DOMContentLoaded', ()=>{ loadDailySummary(); loadCollections(); });
</script>
@endpush
