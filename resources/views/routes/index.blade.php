@extends('layouts.app')
@section('title', 'خطوط السير')
@section('page-title', 'خطوط السير')

@section('content')
<div class="page-header">
    <div><h1><i class="fas fa-route me-2 text-primary"></i> خطوط السير</h1><div class="breadcrumb">إدارة خطوط التوزيع</div></div>
    <button class="btn-primary-custom" onclick="openAddModal()"><i class="fas fa-plus me-1"></i> إضافة خط</button>
</div>

<!-- TABLE -->
<div class="section-card">
    <div class="section-header"><i class="fas fa-route text-primary"></i><h5 class="section-title">قائمة خطوط السير</h5>
        <div class="ms-auto d-flex gap-2">
            <input type="text" id="routeSearch" class="form-control" style="width:200px" placeholder="بحث...">
            <button class="btn btn-sm btn-outline-primary" onclick="loadRoutes()"><i class="fas fa-search"></i></button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>#</th><th>اسم الخط</th><th>نقطة البداية</th><th>نقطة النهاية</th><th>الوصف</th><th>الحالة</th><th>إجراءات</th></tr></thead>
            <tbody id="routeTable"><tr><td colspan="7" class="text-center py-4"><div class="spinner mx-auto" style="width:30px;height:30px;border-width:3px"></div></td></tr></tbody>
        </table>
    </div>
    <div class="section-body d-flex justify-content-between">
        <div id="routePagInfo" class="text-muted" style="font-size:.8rem"></div>
        <div id="routePagination"></div>
    </div>
</div>

<!-- ADD/EDIT MODAL -->
<div class="modal fade" id="routeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="routeModalTitle"><i class="fas fa-route me-2"></i> إضافة خط سير</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="routeForm">
                    <input type="hidden" id="routeId">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">اسم الخط *</label><input type="text" name="name" id="rf_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">منطقة البداية</label><input type="text" name="start_area" id="rf_start" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">منطقة النهاية</label><input type="text" name="end_area" id="rf_end" class="form-control"></div>
                        <div class="col-12"><label class="form-label">الحالة</label>
                            <select name="is_active" id="rf_is_active" class="form-select"><option value="1">نشط</option><option value="0">غير نشط</option></select>
                        </div>
                        <div class="col-12"><label class="form-label">وصف</label><textarea name="description" id="rf_description" class="form-control" rows="2"></textarea></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn-primary-custom" onclick="saveRoute()"><i class="fas fa-save me-1"></i> حفظ</button>
            </div>
        </div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="routeDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0"><h5 class="modal-title text-danger"><i class="fas fa-trash me-2"></i>تأكيد الحذف</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center"><i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i><p>حذف الخط <strong id="routeDeleteName"></strong>؟</p></div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger" id="routeDeleteBtn">حذف</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let routeDeleteId=null, routePage=1;

async function loadRoutes(page=1) {
    routePage=page;
    const params = new URLSearchParams({ per_page:15, page, search: document.getElementById('routeSearch').value });
    const r = await apiFetch('/routes?'+params);
    if (!r.success) return;
    const { data, total, current_page, last_page } = r.data;
    document.getElementById('routePagInfo').textContent=`إجمالي: ${total}`;
    if (!data.length) { document.getElementById('routeTable').innerHTML='<tr><td colspan="7" class="text-center py-4 text-muted">لا توجد خطوط سير</td></tr>'; return; }
    document.getElementById('routeTable').innerHTML = data.map((route,i)=>`
        <tr>
            <td>${(page-1)*15+i+1}</td>
            <td><strong>${route.name}</strong></td>
            <td>${route.start_area??'-'}</td>
            <td>${route.end_area??'-'}</td>
            <td>${route.description?route.description.substring(0,40)+(route.description.length>40?'…':''):'-'}</td>
            <td><span class="badge-status ${route.is_active!==false?'badge-active':'badge-inactive'}">${route.is_active!==false?'نشط':'غير نشط'}</span></td>
            <td>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-info"    onclick="window.location='/deliveries?route_id=${route.id}'" title="التسليمات"><i class="fas fa-truck"></i></button>
                    <button class="btn btn-sm btn-outline-warning"  onclick="openEditModal(${route.id})" title="تعديل"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-outline-danger"   onclick="confirmDelete(${route.id},'${route.name.replace(/'/g,"\\'")}')"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>`).join('');
    const pages=[];
    for(let i=1;i<=last_page;i++) pages.push(`<button class="btn btn-sm ${i===current_page?'btn-primary':'btn-outline-primary'} mx-1" onclick="loadRoutes(${i})">${i}</button>`);
    document.getElementById('routePagination').innerHTML=pages.join('');
}

function openAddModal() {
    document.getElementById('routeId').value='';
    document.getElementById('routeForm').reset();
    document.getElementById('rf_is_active').value='1';
    document.getElementById('routeModalTitle').innerHTML='<i class="fas fa-route me-2"></i> إضافة خط سير جديد';
    new bootstrap.Modal(document.getElementById('routeModal')).show();
}

async function openEditModal(id) {
    document.getElementById('routeModalTitle').innerHTML='<i class="fas fa-edit me-2"></i> تعديل خط السير';
    new bootstrap.Modal(document.getElementById('routeModal')).show();
    const r = await apiFetch('/routes/'+id);
    if (!r.success) return;
    const route = r.data;
    document.getElementById('routeId').value        = route.id;
    document.getElementById('rf_name').value        = route.name??'';
    document.getElementById('rf_start').value       = route.start_area??'';
    document.getElementById('rf_end').value         = route.end_area??'';
    document.getElementById('rf_is_active').value   = route.is_active!==false?'1':'0';
    document.getElementById('rf_description').value = route.description??'';
}

async function saveRoute() {
    const id   = document.getElementById('routeId').value;
    const data = Object.fromEntries(new FormData(document.getElementById('routeForm')));
    data.is_active = data.is_active==='1';
    const r = await apiFetch(id?`/routes/${id}`:'/routes', { method:id?'PUT':'POST', body:JSON.stringify(data) });
    if (r.success) { bootstrap.Modal.getInstance(document.getElementById('routeModal')).hide(); showAlert(id?'تم التحديث':'تم الإضافة'); loadRoutes(routePage); }
    else showAlert(r.message||'فشل الحفظ','danger');
}

function confirmDelete(id,name) { routeDeleteId=id; document.getElementById('routeDeleteName').textContent=name; new bootstrap.Modal(document.getElementById('routeDeleteModal')).show(); }
document.getElementById('routeDeleteBtn').addEventListener('click', async()=>{
    if (!routeDeleteId) return;
    const r = await apiFetch(`/routes/${routeDeleteId}`, { method:'DELETE' });
    bootstrap.Modal.getInstance(document.getElementById('routeDeleteModal')).hide();
    if (r.success) { showAlert('تم الحذف'); loadRoutes(routePage); } else showAlert(r.message,'danger');
    routeDeleteId=null;
});

document.getElementById('routeSearch').addEventListener('keypress', e=>{ if(e.key==='Enter') loadRoutes(); });
document.addEventListener('DOMContentLoaded', loadRoutes);
</script>
@endpush
