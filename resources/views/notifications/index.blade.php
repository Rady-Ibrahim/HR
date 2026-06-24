@extends('layouts.app')
@section('title', 'الإشعارات')
@section('page-title', 'الإشعارات')

@section('content')
<div class="page-header">
    <div><h1><i class="fas fa-bell me-2 text-primary"></i> الإشعارات</h1></div>
    <button class="btn btn-outline-secondary" onclick="markAllRead()"><i class="fas fa-check-double me-1"></i> تحديد الكل كمقروء</button>
</div>

<div class="section-card">
    <div class="section-header">
        <i class="fas fa-bell text-primary"></i>
        <h5 class="section-title">الإشعارات</h5>
        <span class="ms-2 badge bg-danger" id="notifCountBadge" style="display:none">0</span>
    </div>
    <div id="notifList">
        <div class="text-center py-5"><div class="spinner mx-auto"></div></div>
    </div>
    <div class="section-body d-flex justify-content-center" id="notifPagination"></div>
</div>
@endsection

@push('scripts')
<script>
async function loadNotifications(page = 1) {
    const r = await apiFetch(`/notifications?per_page=20&page=${page}`);
    if (!r.success) return;
    const data = r.data;
    const unread = r.unread_count;

    if (unread > 0) {
        document.getElementById('notifCountBadge').textContent = unread;
        document.getElementById('notifCountBadge').style.display = '';
    }

    const all = data.data;
    if (!all.length) {
        document.getElementById('notifList').innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-bell-slash fa-3x mb-3"></i><br>لا توجد إشعارات</div>';
        return;
    }

    document.getElementById('notifList').innerHTML = all.map(n => `
        <div class="d-flex align-items-start p-3 ${!n.is_read ? 'bg-light' : ''}" style="border-bottom:1px solid #f4f6fb">
            <div class="me-3" style="width:42px;height:42px;border-radius:12px;background:${!n.is_read ? '#e3f2fd' : '#f4f6fb'};display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="fas fa-bell" style="color:${!n.is_read ? '#1565c0' : '#a0aec0'}"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold ${!n.is_read ? 'text-primary' : 'text-muted'}">${n.title ?? 'إشعار'}</div>
                <div style="font-size:.875rem">${n.body ?? n.message ?? ''}</div>
                <div style="font-size:.75rem;color:#a0aec0;margin-top:4px">${n.created_at ? new Date(n.created_at).toLocaleString('ar-EG') : ''}</div>
            </div>
            <div class="d-flex gap-1 me-2">
                ${!n.is_read ? `<button class="btn btn-sm btn-outline-primary" onclick="markRead(${n.id})"><i class="fas fa-check"></i></button>` : ''}
                <button class="btn btn-sm btn-outline-danger" onclick="deleteNotif(${n.id})"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    `).join('');

    const pages = [];
    for (let i = 1; i <= Math.min(data.last_page, 10); i++) {
        pages.push(`<button class="btn btn-sm ${i === data.current_page ? 'btn-primary' : 'btn-outline-primary'} mx-1" onclick="loadNotifications(${i})">${i}</button>`);
    }
    document.getElementById('notifPagination').innerHTML = pages.join('');
}

async function markRead(id) {
    await apiFetch(`/notifications/${id}/read`, { method: 'POST' });
    loadNotifications();
}

async function markAllRead() {
    const r = await apiFetch('/notifications/read-all', { method: 'POST' });
    if (r.success) { showAlert('تم تحديد الكل كمقروء'); loadNotifications(); }
}

async function deleteNotif(id) {
    await apiFetch(`/notifications/${id}`, { method: 'DELETE' });
    loadNotifications();
}

document.addEventListener('DOMContentLoaded', () => loadNotifications());
</script>
@endpush
