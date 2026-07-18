@extends('layouts.app')
@section('title', 'المسبق الدفع')
@section('page-title', 'المسبق الدفع')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-receipt me-2 text-primary"></i> المسبق الدفع</h1>
        <div class="breadcrumb">ترحيل طلبات المسبق الدفع للمراجعة</div>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" onclick="loadPrepaidRequests()"><i class="fas fa-sync-alt me-1"></i> تحديث</button>
        <button class="btn-primary-custom" onclick="document.getElementById('customer_id').focus()"><i class="fas fa-plus me-1"></i> طلب جديد</button>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-paper-plane text-primary"></i>
                <h5 class="section-title">طلب جديد</h5>
            </div>
            <div class="section-body">
                <div class="alert alert-info py-2" style="font-size:.82rem">
                    الطلب يتحفظ كمسبق الدفع ويتحول مباشرة إلى موظف المراجعة المختار.
                </div>
                <form id="prepaidForm">
                    <div class="mb-3">
                        <label class="form-label">اسم العميل *</label>
                        <select name="customer_id" id="customer_id" class="form-select" required>
                            <option value="">اختر العميل</option>
                        </select>
                    </div>

                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label">عدد الأصناف *</label>
                            <input type="number" name="items_count" id="items_count" class="form-control" min="1" value="1" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">عدد الطلبيات *</label>
                            <input type="number" name="orders_count" id="orders_count" class="form-control" min="1" value="1" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">اسم موظف محضر *</label>
                        <select name="prepared_by_id" id="prepared_by_id" class="form-select" required>
                            <option value="">اختر الموظف</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">يرحل لموظف يراجع *</label>
                        <select name="reviewer_employee_id" id="reviewer_employee_id" class="form-select" required>
                            <option value="">اختر موظف المراجعة</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                    </div>

                    <button type="submit" class="btn-primary-custom w-100">
                        <i class="fas fa-share-square me-1"></i> ترحيل للمراجعة
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <div class="stat-card text-center">
                    <div class="stat-icon mx-auto" style="background:#e3f2fd;color:#1565c0"><i class="fas fa-clipboard-check"></i></div>
                    <div class="stat-value" id="underReviewCount">-</div>
                    <div class="stat-label">تحت المراجعة</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card text-center">
                    <div class="stat-icon mx-auto" style="background:#e8f5e9;color:#2e7d32"><i class="fas fa-boxes"></i></div>
                    <div class="stat-value" id="itemsTotal">-</div>
                    <div class="stat-label">إجمالي الأصناف</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card text-center">
                    <div class="stat-icon mx-auto" style="background:#fff3e0;color:#e65100"><i class="fas fa-layer-group"></i></div>
                    <div class="stat-value" id="ordersTotal">-</div>
                    <div class="stat-label">إجمالي الطلبيات</div>
                </div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-list text-primary"></i>
                <h5 class="section-title">طلبات مرحلة للمراجعة</h5>
                <button class="btn btn-sm btn-outline-primary ms-auto" onclick="loadPrepaidRequests()"><i class="fas fa-sync-alt"></i></button>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>رقم الطلب</th>
                            <th>العميل</th>
                            <th>الأصناف</th>
                            <th>الطلبيات</th>
                            <th>المحضر</th>
                            <th>المراجع</th>
                            <th>الحالة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="prepaidTable">
                        <tr><td colspan="8" class="text-center py-4"><div class="spinner mx-auto" style="width:30px;height:30px;border-width:3px"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const requestStatusLabel = {
    draft: 'مسودة',
    prepared: 'تم التحضير',
    under_review: 'تحت المراجعة',
    approved: 'معتمد',
    rejected: 'مرفوض',
    ready_for_delivery: 'جاهز للتسليم',
    in_delivery: 'في الطريق',
    delivered: 'تم التسليم',
    collected: 'تم التحصيل',
    closed: 'مغلق'
};

const requestStatusBadge = {
    draft: 'badge-draft',
    prepared: 'badge-approved',
    under_review: 'badge-pending',
    approved: 'badge-active',
    rejected: 'badge-rejected',
    ready_for_delivery: 'badge-approved',
    in_delivery: 'badge-approved',
    delivered: 'badge-active',
    collected: 'badge-active',
    closed: 'badge-active'
};

async function loadLookups() {
    const [customers, employees] = await Promise.all([
        apiFetch('/customers?per_page=100&status=active'),
        apiFetch('/employees?per_page=100&status=active')
    ]);

    if (customers.success) {
        document.getElementById('customer_id').innerHTML = '<option value="">اختر العميل</option>' +
            customers.data.data.map(c => `<option value="${c.id}">${c.name}${c.company_name ? ' - ' + c.company_name : ''}</option>`).join('');
    }

    if (employees.success) {
        const options = employees.data.data.map(e => `<option value="${e.id}">${e.name} - ${e.employee_code ?? e.id}</option>`).join('');
        document.getElementById('prepared_by_id').innerHTML = '<option value="">اختر الموظف</option>' + options;
        document.getElementById('reviewer_employee_id').innerHTML = '<option value="">اختر موظف المراجعة</option>' + options;
    }
}

async function loadPrepaidRequests() {
    const r = await apiFetch('/requests?payment_type=prepaid&status=under_review&per_page=50');
    if (!r.success) return;

    const rows = r.data.data ?? [];
    document.getElementById('underReviewCount').textContent = rows.length;
    document.getElementById('itemsTotal').textContent = rows.reduce((sum, req) => sum + Number(req.items_count || 0), 0);
    document.getElementById('ordersTotal').textContent = rows.reduce((sum, req) => sum + Number(req.orders_count || req.total_quantity || 0), 0);

    if (!rows.length) {
        document.getElementById('prepaidTable').innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">لا توجد طلبات مرحلة</td></tr>';
        return;
    }

    document.getElementById('prepaidTable').innerHTML = rows.map(req => `
        <tr>
            <td><strong>${req.request_number ?? '#' + req.id}</strong></td>
            <td>${req.customer?.name ?? req.customer_name ?? '-'}</td>
            <td>${req.items_count ?? 0}</td>
            <td>${req.orders_count ?? req.total_quantity ?? 0}</td>
            <td>${req.prepared_by?.name ?? '-'}</td>
            <td>${req.reviewer_employee?.name ?? req.assigned_employee?.name ?? '-'}</td>
            <td><span class="badge-status ${requestStatusBadge[req.status] || 'badge-draft'}">${requestStatusLabel[req.status] || req.status}</span></td>
            <td>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-info" onclick="viewPrepaid(${req.id})" title="عرض"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-outline-success" onclick="approvePrepaid(${req.id})" title="اعتماد"><i class="fas fa-check"></i></button>
                    <button class="btn btn-sm btn-outline-danger" onclick="rejectPrepaid(${req.id})" title="رفض"><i class="fas fa-times"></i></button>
                </div>
            </td>
        </tr>
    `).join('');
}

async function viewPrepaid(id) {
    const r = await apiFetch('/requests/' + id);
    if (!r.success) {
        showAlert(r.message || 'تعذر عرض الطلب', 'danger');
        return;
    }
    const req = r.data;
    showAlert(`الطلب ${req.request_number ?? id}: ${req.customer?.name ?? req.customer_name ?? '-'} - ${req.items_count ?? 0} أصناف / ${req.orders_count ?? req.total_quantity ?? 0} طلبيات`);
}

async function approvePrepaid(id) {
    const r = await apiFetch(`/requests/${id}/manager-approve`, { method: 'POST', body: JSON.stringify({ notes: 'اعتماد من صفحة المسبق الدفع' }) });
    if (r.success) {
        showAlert('تم اعتماد الطلب');
        loadPrepaidRequests();
        return;
    }
    showAlert(r.message || 'فشل اعتماد الطلب', 'danger');
}

async function rejectPrepaid(id) {
    const reason = prompt('سبب رفض الطلب:');
    if (!reason) return;
    const r = await apiFetch(`/requests/${id}/manager-reject`, { method: 'POST', body: JSON.stringify({ reason }) });
    if (r.success) {
        showAlert('تم رفض الطلب', 'warning');
        loadPrepaidRequests();
        return;
    }
    showAlert(r.message || 'فشل رفض الطلب', 'danger');
}

document.getElementById('prepaidForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const fd = new FormData(event.target);
    const data = {
        customer_id: Number(fd.get('customer_id')),
        items_count: Number(fd.get('items_count')),
        orders_count: Number(fd.get('orders_count')),
        prepared_by_id: Number(fd.get('prepared_by_id')),
        reviewer_employee_id: Number(fd.get('reviewer_employee_id')),
        notes: fd.get('notes') || null
    };

    const r = await apiFetch('/requests/prepaid', { method: 'POST', body: JSON.stringify(data) });
    if (r.success) {
        event.target.reset();
        document.getElementById('items_count').value = 1;
        document.getElementById('orders_count').value = 1;
        showAlert('تم ترحيل الطلب للمراجعة');
        loadPrepaidRequests();
    } else {
        showAlert(r.message || 'فشل ترحيل الطلب', 'danger');
    }
});

document.addEventListener('DOMContentLoaded', () => {
    loadLookups();
    loadPrepaidRequests();
});
</script>
@endpush
