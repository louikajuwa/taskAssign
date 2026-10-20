@extends('layouts.app')

@section('title', 'Notifications - TaskAssign')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Notifications</h1>
        <div class="flex flex-wrap gap-2">
            <button type="button" id="filter-all" class="px-3 py-1.5 text-sm rounded-lg bg-brand-600 text-white" onclick="filterNotifications('all')">All</button>
            <button type="button" id="filter-unread" class="px-3 py-1.5 text-sm rounded-lg bg-gray-200 text-gray-700" onclick="filterNotifications('unread')">Unread</button>
            <button type="button" id="mark-all-page-btn" onclick="markAllAsRead(this)" class="px-3 py-1.5 text-sm rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300">Mark all as read</button>
        </div>
    </div>

    <div id="notifications-container" class="space-y-2" aria-live="polite">
        <div class="text-center text-gray-400 py-8">Loading...</div>
    </div>

    <nav id="pagination-container" class="mt-6 flex flex-wrap justify-center gap-2" aria-label="Notifications pagination"></nav>
</div>

@push('scripts')
<script>
let currentFilter = 'all';
let currentPage = 1;
const FILTER_ON = 'px-3 py-1.5 text-sm rounded-lg bg-brand-600 text-white';
const FILTER_OFF = 'px-3 py-1.5 text-sm rounded-lg bg-gray-200 text-gray-700';

function filterNotifications(filter) {
    currentFilter = filter;
    currentPage = 1;
    document.getElementById('filter-all').className = filter === 'all' ? FILTER_ON : FILTER_OFF;
    document.getElementById('filter-unread').className = filter === 'unread' ? FILTER_ON : FILTER_OFF;
    document.getElementById('filter-all').setAttribute('aria-pressed', filter === 'all');
    document.getElementById('filter-unread').setAttribute('aria-pressed', filter === 'unread');
    loadPage(1);
}

function loadPage(page) {
    currentPage = page;
    const container = document.getElementById('notifications-container');
    container.innerHTML = '<div class="text-center text-gray-400 py-8">Loading...</div>';
    $.get('/api/notifications', { filter: currentFilter, page: page, limit: 20 }, function(res) {
        if (!res.data || res.data.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 py-12 bg-white rounded-xl border border-gray-200"><p class="font-medium">No notifications</p><p class="text-sm text-gray-400 mt-1">You\'re all caught up.</p></div>';
            document.getElementById('pagination-container').innerHTML = '';
            return;
        }

        let html = '';
        res.data.forEach(function(n) {
            const isUnread = !n.read_at;
            const bg = isUnread ? 'bg-brand-50 border-brand-100' : 'bg-white border-gray-100';
            const time = escapeHtml(timeAgo(n.created_at));
            const tpId = (n.data && n.data.testplan_id) ? n.data.testplan_id : '';
            html += `<button type="button" class="notif-row w-full text-left flex items-start gap-4 p-4 rounded-lg border ${bg} hover:shadow-sm transition-shadow" data-id="${escAttr(n.id)}" data-testplan="${escAttr(tpId)}">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-800 break-words">${escapeHtml(n.title)}</p>
                    <p class="text-sm text-gray-500 mt-1 break-words">${escapeHtml(n.message)}</p>
                    <p class="text-xs text-gray-400 mt-2">${time}</p>
                </div>
                ${isUnread ? '<div class="w-3 h-3 bg-brand-500 rounded-full flex-shrink-0 mt-2" title="Unread"></div>' : ''}
            </button>`;
        });
        container.innerHTML = html;

        // Pagination
        const pag = res.pagination || { totalPages: 1, currentPage: 1 };
        let pagHtml = '';
        if (pag.totalPages > 1) {
            for (let i = 1; i <= pag.totalPages; i++) {
                const isCur = i === pag.currentPage;
                const cls = isCur ? 'bg-brand-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300';
                pagHtml += `<button type="button" data-page="${i}" ${isCur ? 'aria-current="page"' : ''} aria-label="Page ${i}" class="notif-page px-3 py-1 text-sm rounded ${cls}">${i}</button>`;
            }
        }
        document.getElementById('pagination-container').innerHTML = pagHtml;
    }).fail(function(xhr) {
        if (xhr.status === 401 || xhr.status === 419) return;
        container.innerHTML = '<div class="text-center py-10 bg-white rounded-xl border border-red-200"><p class="text-red-600 font-medium">Could not load notifications.</p><button type="button" id="notif-retry" class="mt-3 px-4 py-2 text-sm rounded-lg bg-brand-600 text-white hover:bg-brand-700">Retry</button></div>';
        document.getElementById('pagination-container').innerHTML = '';
    });
}

$(document).on('click', '#notif-retry', function() { loadPage(currentPage); });
$(document).on('click', '.notif-page', function() { loadPage(parseInt($(this).data('page'), 10)); });
$(document).on('click', '.notif-row', function() {
    markAndNavigate($(this).data('id'), $(this).data('testplan') || null);
});

// The test plan page has no deep-link param, so we always land on '/'.
function markAndNavigate(id, testplanId) {
    const go = function() { window.location.href = '/'; };
    $.ajax({ url: '/api/notifications/' + encodeURIComponent(id) + '/read', method: 'PUT' })
        .always(go);
}

function markAllAsRead(btn) {
    return withBusy(btn, function() {
        return new Promise(function(resolve) {
            $.ajax({ url: '/api/notifications/read-all', method: 'PUT' })
                .done(function() { loadPage(currentPage); fetchUnreadCount(); })
                .fail(function(xhr) { handleAjaxError(xhr, 'Could not mark notifications as read.'); })
                .always(resolve);
        });
    });
}

loadPage(1);
</script>
@endpush
@endsection
