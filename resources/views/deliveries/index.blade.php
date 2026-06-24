@extends('layouts.app')
@section('title', 'التسليمات')
@section('page-title', 'التسليمات')

@section('content')
<div class="page-header">
    <div><h1><i class="fas fa-truck me-2 text-primary"></i> التسليمات</h1><div class="breadcrumb">إدارة عمليات التسليم</div></div>
    <button class="btn-primary-custom" onclick="openAddModal()"><i class="fas fa-plus me-1"></i> إضافة تسليمة</button>
</div>

<!-- STATS -->
<div class="row g-3 mb-4" id="statsRow">
    <div class="col-6 col-md-3"><div class="stat-card text-center"><div class="stat-value" id="stTotal">-</div><div class="stat-label">إجمالي</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card text-center"><div class="stat-value text-success" id="stDelivered">-</div><div class="stat-label">مسلّم</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card text-center"><div class="stat-value text-warning" id="stPartial">-</div><div class="stat-label">جزئي</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card text-center"><div class="stat-value text-danger" id="stFailed">-</div><div class="stat-label">فاشل</div></div></div>
</div>

<!-- FILTERS -->
<div class="section-card mb-4">
    <div class="section-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">بحث</label><input type="text" id="delSearch" class="form-control" placeholder="رقم الطلب، اسم العميل..."></div>
            <div class="col-md-2"><label class="form-label">الحالة</label>
                <select id="delStatus" class="form-select"><option value="">الكل</option><option value="pending">معلق</option><option value="in_progress">جاري</option><option value="delivered">مسلّم</option><option value="partial">جزئي</option><option value="failed">فاشل</option><option value="returned">مرتجع</option></select>
            </div>
            <div class="col-md-2"><label class="form-label">التاريخ</label><input type="date" id="delDate" class="form-control"></div>
            <div class="col-md-2"><button class="btn-primary-custom w-100 mt-4" onclick="loadDeliveries()"><i class="fas fa-search me-1"></i> بحث</button></div>
            <div class="col-md-2"><button class="btn btn-outline-secondary w-100 mt-4" onclick="resetDelFilter()"><i class="fas fa-undo me-1"></i> إعادة</button></div>
        </div>
    </div>
</div>

<!-- TABLE -->
<div class="section-card">
    <div class="section-header"><i class="fas fa-truck text-primary"></i><h5 class="section-title">قائمة التسليمات</h5>
        <span class="ms-auto text-muted" style="font-size:.8rem" id="delCount"></span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>رقم التسليمة</th><th>العميل</th><th>المندوب</th><th>الخط</th><th>التاريخ</th><th>المبلغ</th><th>الحالة</th><th>إجراءات</th></tr></thead>
            <tbody id="delTable"><tr><td colspan="8" class="text-center py-4"><div class="spinner mx-auto" style="width:30px;height:30px;border-width:3px"></div></td></tr></tbody>
        </table>
    </div>
    <div class="section-body d-flex justify-content-between">
        <div id="delPagInfo" class="text-muted" style="font-size:.8rem"></div>
        <div id="delPagination"></div>
    </div>
</div>

<!-- ADD/EDIT MODAL -->
<div class="modal fade" id="delModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="delModalTitle"><i class="fas fa-truck me-2"></i> إضافة تسليمة</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="delForm">
                    <input type="hidden" id="delId">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">طلب البيع (ID) *</label><input type="number" name="request_id" id="df2_request" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">المندوب (ID) *</label><input type="number" name="employee_id" id="df2_emp" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">خط السير (ID)</label><input type="number" name="route_id" id="df2_route" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">تاريخ التسليم المخطط</label><input type="date" name="scheduled_date" id="df2_sched" class="form-control" value="{{ date('Y-m-d') }}"></div>
                        <div class="col-md-6"><label class="form-label">الحالة</label>
                            <select name="status" id="df2_status" class="form-select">
                                <option value="pending">معلق</option><option value="in_progress">جاري</option>
                                <option value="delivered">مسلّم</option><option value="partial">جزئي</option>
                                <option value="failed">فاشل</option><option value="returned">مرتجع</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">المبلغ المحصّل</label><div class="input-group"><input type="number" name="collected_amount" id="df2_collected" class="form-control" placeholder="0.00"><span class="input-group-text">ج.م</span></div></div>
                        <div class="col-12"><label class="form-label">ملاحظات</label><textarea name="notes" id="df2_notes" class="form-control" rows="2"></textarea></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn-primary-custom" onclick="saveDelivery()"><i class="fas fa-save me-1"></i> حفظ</button>
            </div>
        </div>
    </div>
</div>

<!-- VIEW MODAL -->
<div class="modal fade" id="delViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-truck me-2"></i> تفاصيل التسليمة</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="delViewContent"><div class="text-center py-4"><div class="spinner mx-auto"></div></div></div>
        </div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="delDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm"><div class="modal-content">
        <div class="modal-header border-0"><h5 class="modal-title text-danger"><i class="fas fa-trash me-2"></i>تأكيد الحذف</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body text-center"><i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i><p>حذف هذه التسليمة نهائياً؟</p></div>
        <div class="modal-footer border-0 justify-content-center">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
            <button type="button" class="btn btn-danger" id="delDeleteBtn">حذف</button>
        </div>
    </div></div>
</div>
@endsection

@push('scripts')
<script>
let delDeleteId=null, delPage=1;
const delStatusLabels = { pending:'معلق', in_progress:'جاري', delivered:'مسلّم', partial:'جزئي', failed:'فاشل', returned:'مرتجع' };
const delStatusBadge  = { pending:'badge-pending', in_progress:'badge-approved', delivered:'badge-active', partial:'badge-pending', failed:'badge-rejected', returned:'badge-draft' };

async function loadDeliveries(page=1) {
    delPage=page;
    const params = new URLSearchParams({ per_page:15, page });
    const s=document.getElementById('delSearch').value; if(s) params.append('search',s);
    const st=document.getElementById('delStatus').value; if(st) params.append('status',st);
    const dt=document.getElementById('delDate').value; if(dt) params.append('date',dt);
    const r = await apiFetch('/deliveries?'+params);
    if (!r.success) return;
    const { data, total, current_page, last_page, summary } = r.data;
    document.getElementById('delCount').textContent=`إجمالي: ${total}`;
    document.getElementById('delPagInfo').textContent=`صفحة ${current_page} من ${last_page}`;
    if (summary) {
        document.getElementById('stTotal').textContent=summary.total||total;
        document.getElementById('stDelivered').textContent=summary.delivered||0;
        document.getElementById('stPartial').textContent=summary.partial||0;
        document.getElementById('stFailed').textContent=summary.failed||0;
    }
    if (!data.length) { document.getElementById('delTable').innerHTML='<tr><td colspan="8" class="text-center py-4 text-muted">لا توجد تسليمات</td></tr>'; return; }
    document.getElementById('delTable').innerHTML = data.map(d=>`
        <tr>
            <td><strong>#${d.id}</strong></td>
            <td>${d.request?.customer?.name??'-'}</td>
            <td>${d.employee?.name??'-'}</td>
            <td>${d.route?.name??'-'}</td>
            <td>${d.scheduled_date?new Date(d.scheduled_date).toLocaleDateString('ar-EG'):'-'}</td>
            <td>${d.collected_amount?Number(d.collected_amount).toLocaleString()+' ج.م':'-'}</td>
            <td><span class="badge-status ${delStatusBadge[d.status]||'badge-pending'}">${delStatusLabels[d.status]||d.status}</span></td>
            <td>
                <div class="d-flex gap-1 flex-wrap">
                    <button class="btn btn-sm btn-outline-info"    onclick="viewDelivery(${d.id})" title="عرض"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-outline-warning"  onclick="openEditModal(${d.id})" title="تعديل"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-outline-danger"   onclick="confirmDelete(${d.id})"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>`).join('');
    const pages=[];
    for(let i=1;i<=last_page;i++) pages.push(`<button class="btn btn-sm ${i===current_page?'btn-primary':'btn-outline-primary'} mx-1" onclick="loadDeliveries(${i})">${i}</button>`);
    document.getElementById('delPagination').innerHTML=pages.join('');
}

function openAddModal() {
    document.getElementById('delId').value=''; document.getElementById('delForm').reset();
    document.getElementById('delModalTitle').innerHTML='<i class="fas fa-truck me-2"></i> إضافة تسليمة جديدة';
    document.getElementById('df2_status').value='pending'; document.getElementById('df2_sched').value='{{ date("Y-m-d") }}';
    new bootstrap.Modal(document.getElementById('delModal')).show();
}
async function openEditModal(id) {
    document.getElementById('delModalTitle').innerHTML='<i class="fas fa-edit me-2"></i> تعديل التسليمة';
    new bootstrap.Modal(document.getElementById('delModal')).show();
    const r=await apiFetch('/deliveries/'+id); if(!r.success) return; const d=r.data;
    document.getElementById('delId').value=d.id;
    document.getElementById('df2_request').value=d.request_id;
    document.getElementById('df2_emp').value=d.employee_id;
    document.getElementById('df2_route').value=d.route_id??'';
    document.getElementById('df2_sched').value=d.scheduled_date?d.scheduled_date.substring(0,10):'';
    document.getElementById('df2_status').value=d.status;
    document.getElementById('df2_collected').value=d.collected_amount??'';
    document.getElementById('df2_notes').value=d.notes??'';
}
async function saveDelivery() {
    const id=document.getElementById('delId').value;
    const data=Object.fromEntries(new FormData(document.getElementById('delForm')));
    data.request_id=parseInt(data.request_id); data.employee_id=parseInt(data.employee_id);
    if(data.route_id) data.route_id=parseInt(data.route_id); else delete data.route_id;
    if(data.collected_amount) data.collected_amount=parseFloat(data.collected_amount); else delete data.collected_amount;
    const r=await apiFetch(id?`/deliveries/${id}`:'/deliveries',{method:id?'PUT':'POST',body:JSON.stringify(data)});
    if(r.success){bootstrap.Modal.getInstance(document.getElementById('delModal')).hide();showAlert(id?'تم التحديث':'تم الإضافة');loadDeliveries(delPage);}
    else showAlert(r.message||'فشل الحفظ','danger');
}

async function viewDelivery(id) {
    const modal = new bootstrap.Modal(document.getElementById('delViewModal'));
    document.getElementById('delViewContent').innerHTML='<div class="text-center py-4"><div class="spinner mx-auto"></div></div>';
    modal.show();
    const r=await apiFetch('/deliveries/'+id); if(!r.success) return; const d=r.data;
    document.getElementById('delViewContent').innerHTML=`
    <div class="row g-3">
        <div class="col-md-6">
            <div class="section-card p-3">
                <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>معلومات التسليمة</h6>
                <p class="mb-1"><strong>رقم:</strong> #${d.id}</p>
                <p class="mb-1"><strong>العميل:</strong> ${d.request?.customer?.name??'-'}</p>
                <p class="mb-1"><strong>المندوب:</strong> ${d.employee?.name??'-'}</p>
                <p class="mb-1"><strong>الخط:</strong> ${d.route?.name??'-'}</p>
                <p class="mb-1"><strong>تاريخ التسليم:</strong> ${d.actual_date?new Date(d.actual_date).toLocaleDateString('ar-EG'):'-'}</p>
                <p class="mb-0"><strong>الحالة:</strong> <span class="badge-status ${delStatusBadge[d.status]||'badge-pending'}">${delStatusLabels[d.status]||d.status}</span></p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="section-card p-3">
                <h6 class="fw-bold mb-3"><i class="fas fa-money-bill me-2 text-success"></i>التحصيل</h6>
                <p class="mb-1"><strong>المحصّل:</strong> ${d.collected_amount?Number(d.collected_amount).toLocaleString()+' ج.م':'غير محصّل'}</p>
                <p class="mb-1"><strong>ملاحظات:</strong> ${d.notes??'-'}</p>
                ${d.proof_image?`<img src="${d.proof_image}" class="img-fluid rounded mt-2" style="max-height:200px" alt="إثبات التسليم">`:'' }
            </div>
        </div>
        ${d.tracking_data?.length?`
        <div class="col-12">
            <div class="section-card p-3">
                <h6 class="fw-bold mb-3"><i class="fas fa-map-marker-alt me-2 text-danger"></i>تتبع GPS</h6>
                <div class="table-responsive"><table class="data-table"><thead><tr><th>التوقيت</th><th>خط العرض</th><th>خط الطول</th></tr></thead>
                <tbody>${d.tracking_data.map(t=>`<tr><td>${new Date(t.timestamp).toLocaleString('ar-EG')}</td><td>${t.lat}</td><td>${t.lng}</td></tr>`).join('')}</tbody></table></div>
            </div>
        </div>`:''}
    </div>`;
}

function confirmDelete(id) { delDeleteId=id; new bootstrap.Modal(document.getElementById('delDeleteModal')).show(); }
document.getElementById('delDeleteBtn').addEventListener('click', async()=>{
    if(!delDeleteId) return;
    const r=await apiFetch(`/deliveries/${delDeleteId}`,{method:'DELETE'});
    bootstrap.Modal.getInstance(document.getElementById('delDeleteModal')).hide();
    if(r.success){showAlert('تم الحذف');loadDeliveries(delPage);}else showAlert(r.message,'danger');
    delDeleteId=null;
});
function resetDelFilter() { ['delSearch','delStatus','delDate'].forEach(id=>document.getElementById(id).value=''); loadDeliveries(); }
document.getElementById('delSearch').addEventListener('keypress', e=>{ if(e.key==='Enter') loadDeliveries(); });
document.addEventListener('DOMContentLoaded', loadDeliveries);
</script>
@endpush
