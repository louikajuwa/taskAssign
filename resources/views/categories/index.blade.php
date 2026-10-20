@extends('layouts.app')

@section('title', 'Categories - TaskAssign')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Categories</h1>
            <p class="text-sm text-gray-500">Manage categories and the testers auto-assigned when a test plan uses them.</p>
        </div>
        <a href="/" class="text-sm text-gray-500 hover:text-gray-800">&larr; Back to test plans</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Categories panel --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex gap-2 mb-4">
                <label for="newCat" class="sr-only">New category name</label>
                <input id="newCat" type="text" placeholder="New category name..."
                       class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm">
                <button type="button" id="addCatBtn" class="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-semibold hover:bg-brand-700 focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">Add</button>
            </div>
            <label for="searchCat" class="sr-only">Search categories</label>
            <input id="searchCat" type="text" placeholder="Search categories..."
                   class="w-full mb-3 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg outline-none focus:bg-white focus:border-brand-400 focus:ring-2 focus:ring-brand-500 text-sm">
            <div class="border rounded-lg overflow-x-auto">
                <table class="w-full text-sm min-w-[320px]">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr><th class="px-4 py-2 text-left">Category</th><th class="px-4 py-2 text-center">Testers</th><th class="px-4 py-2 text-right">Actions</th></tr>
                    </thead>
                    <tbody id="catBody" class="divide-y divide-gray-100">
                        <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Testers panel --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div id="testerEmpty" class="text-center text-gray-400 py-16">
                Select a category to manage its testers.
            </div>
            <div id="testerPanel" class="hidden">
                <h2 class="text-base font-bold text-gray-900 mb-1">Testers — <span id="testerCatName" class="text-brand-600"></span></h2>
                <p class="text-xs text-gray-400 mb-4">These users are auto-assigned to their role when a test plan uses this category.</p>

                <div class="flex flex-wrap gap-2 mb-4">
                    <div class="relative flex-1 min-w-[10rem]">
                        <label for="testerSearch" class="sr-only">Search tester by email</label>
                        <input id="testerSearch" type="text" placeholder="Search tester by email..." autocomplete="off"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-500 text-sm">
                        <div id="testerAutocomplete" data-dropdown class="absolute left-0 right-0 z-50 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-44 overflow-y-auto hidden"></div>
                    </div>
                    <label for="testerRole" class="sr-only">Tester role</label>
                    <select id="testerRole" class="px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="1">Developer</option>
                        <option value="2">Generic Tester</option>
                        <option value="3">Sandbox Tester</option>
                        <option value="4">Alpha Tester</option>
                        <option value="5">Beta Tester</option>
                        <option value="6">Live Tester</option>
                    </select>
                    <button type="button" id="addTesterBtn" class="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-semibold hover:bg-brand-700 whitespace-nowrap focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">Assign</button>
                </div>

                <div class="border rounded-lg overflow-x-auto">
                    <table class="w-full text-sm min-w-[320px]">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                            <tr><th class="px-4 py-2 text-left">Email</th><th class="px-4 py-2 text-left">Role</th><th class="px-4 py-2 text-right">Action</th></tr>
                        </thead>
                        <tbody id="testerBody" class="divide-y divide-gray-100"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let categories = [];
let activeCat = null;            // {id, category_name}
let selectedTesterUserId = null;
let testerSearchTimer = null;
let catSearchTimer = null;

function apiError(xhr) {
    if (xhr.status === 401 || xhr.status === 419) return null; // handled globally
    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) return Object.values(xhr.responseJSON.errors).flat().join(' ');
    return (xhr.responseJSON && xhr.responseJSON.message) || 'Request failed.';
}
function showApiError(xhr) {
    const m = apiError(xhr);
    if (m) Swal.fire({ icon: 'error', title: 'Error', text: m });
}
const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2200 });

// Wrap a jqXHR so withBusy waits for completion (resolves either way).
function busy(btn, makeXhr) {
    return withBusy(btn, () => new Promise(resolve => { makeXhr().always(resolve); }));
}

// ---- Categories ----
function loadCategories() {
    const search = $('#searchCat').val() || '';
    $.get('/api/categories', { search }, function(res) {
        categories = res.data || [];
        renderCategories();
    }).fail(function(xhr) {
        if (xhr.status === 401 || xhr.status === 419) return;
        $('#catBody').html('<tr><td colspan="3" class="px-4 py-6 text-center text-red-500">Could not load categories. <button type="button" class="retry-cats underline text-brand-600">Retry</button></td></tr>');
    });
}

function renderCategories() {
    const body = $('#catBody');
    if (!categories.length) {
        body.html('<tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">No categories yet.</td></tr>');
        return;
    }
    body.html(categories.map(c => `
        <tr class="hover:bg-brand-50 ${activeCat && activeCat.id === c.id ? 'bg-brand-50' : ''}">
            <td class="px-4 py-2"><button type="button" class="cat-select text-left font-medium text-gray-800 hover:text-brand-700" data-id="${escAttr(c.id)}">${escAttr(c.category_name)}</button></td>
            <td class="px-4 py-2 text-center text-gray-500">${escAttr(c.tester_count ?? 0)}</td>
            <td class="px-4 py-2 text-right whitespace-nowrap">
                <button type="button" class="cat-select text-brand-600 hover:text-brand-800 text-xs font-semibold" data-id="${escAttr(c.id)}">Manage</button>
                <button type="button" class="cat-delete ml-3 text-red-600 hover:text-red-800 text-xs font-semibold" data-id="${escAttr(c.id)}">Delete</button>
            </td>
        </tr>`).join(''));
}

$(document).on('click', '.retry-cats', loadCategories);
$('#catBody').on('click', '.cat-select', function() { selectCategory(parseInt($(this).data('id'), 10)); });
$('#catBody').on('click', '.cat-delete', function() {
    const c = categories.find(x => x.id === parseInt($(this).data('id'), 10));
    if (c) deleteCategory(c.id, c.category_name, this);
});

$('#addCatBtn').click(function() {
    const name = $('#newCat').val().trim();
    if (!name) return;
    busy(this, () =>
        $.ajax({ url: '/api/categories', method: 'POST', contentType: 'application/json', dataType: 'json', data: JSON.stringify({ categoryName: name }) })
            .done(function() { $('#newCat').val(''); Toast.fire({ icon: 'success', title: 'Category added' }); loadCategories(); })
            .fail(showApiError));
});
$('#newCat').on('keypress', e => { if (e.key === 'Enter') $('#addCatBtn').click(); });
$('#searchCat').on('input', () => { clearTimeout(catSearchTimer); catSearchTimer = setTimeout(loadCategories, 250); });

function deleteCategory(id, name, btn) {
    Swal.fire({ icon: 'warning', title: 'Delete category?', html: `Delete <b>${escapeHtml(name)}</b> and all its tester assignments?`, showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#dc2626' })
        .then(r => {
            if (!r.isConfirmed) return;
            busy(btn, () =>
                $.ajax({ url: '/api/categories/' + encodeURIComponent(id), method: 'DELETE', dataType: 'json' })
                    .done(function() {
                        Toast.fire({ icon: 'success', title: 'Category deleted' });
                        if (activeCat && activeCat.id === id) { activeCat = null; $('#testerPanel').addClass('hidden'); $('#testerEmpty').removeClass('hidden'); }
                        loadCategories();
                    })
                    .fail(showApiError));
        });
}

// ---- Testers ----
function selectCategory(id) {
    activeCat = categories.find(c => c.id === id) || null;
    if (!activeCat) return;
    renderCategories();
    $('#testerEmpty').addClass('hidden');
    $('#testerPanel').removeClass('hidden');
    $('#testerCatName').text(activeCat.category_name);
    $('#testerSearch').val(''); selectedTesterUserId = null;
    $('#testerAutocomplete').addClass('hidden').empty();
    loadTesters();
}

function loadTesters() {
    if (!activeCat) return;
    $.get('/api/category-testers/' + encodeURIComponent(activeCat.id), function(res) {
        const rows = res.data || [];
        const body = $('#testerBody');
        if (!rows.length) { body.html('<tr><td colspan="3" class="px-4 py-5 text-center text-gray-400">No testers assigned.</td></tr>'); return; }
        body.html(rows.map(t => `
            <tr>
                <td class="px-4 py-2 text-gray-700 break-all">${escAttr(t.email)}</td>
                <td class="px-4 py-2"><span class="px-2 py-0.5 bg-brand-100 text-brand-700 rounded text-xs">${escAttr(t.roleName)}</span></td>
                <td class="px-4 py-2 text-right"><button type="button" class="tester-remove text-red-600 hover:text-red-800 text-xs font-semibold" data-id="${escAttr(t.id)}" data-email="${escAttr(t.email)}">Remove</button></td>
            </tr>`).join(''));
    }).fail(function(xhr) {
        if (xhr.status === 401 || xhr.status === 419) return;
        $('#testerBody').html('<tr><td colspan="3" class="px-4 py-5 text-center text-red-500">Could not load testers. <button type="button" class="retry-testers underline text-brand-600">Retry</button></td></tr>');
    });
}
$(document).on('click', '.retry-testers', loadTesters);
$('#testerBody').on('click', '.tester-remove', function() {
    removeTester($(this).data('id'), String($(this).data('email') || ''), this);
});

$('#testerSearch').on('input', function() {
    const val = $(this).val().trim();
    clearTimeout(testerSearchTimer);
    selectedTesterUserId = null;
    if (!val) { $('#testerAutocomplete').addClass('hidden').empty(); return; }
    if (val.length < 2) {
        $('#testerAutocomplete').empty().append('<div class="px-3 py-2 text-xs text-gray-400">Type at least 2 characters to search</div>').removeClass('hidden');
        return;
    }
    testerSearchTimer = setTimeout(function() {
        $.get('/api/users/search', { q: val }, function(res) {
            const list = $('#testerAutocomplete'); list.empty();
            const users = (res.data || []);
            if (!users.length) { list.addClass('hidden'); return; }
            users.forEach(u => {
                $(`<button type="button" class="block w-full text-left px-3 py-2 text-sm hover:bg-brand-50">${escAttr(u.email)} <span class="text-[10px] text-gray-400">${escAttr(u.permissionLevel)}</span></button>`)
                    .on('click', function() { selectedTesterUserId = u.userId; $('#testerSearch').val(u.email); list.addClass('hidden'); })
                    .appendTo(list);
            });
            list.removeClass('hidden');
        });
    }, 250);
});

$('#addTesterBtn').click(function() {
    if (!activeCat) return;
    if (!selectedTesterUserId) { Swal.fire({ icon: 'info', title: 'Pick a user', text: 'Search and select a tester from the dropdown first.' }); return; }
    busy(this, () =>
        $.ajax({
            url: '/api/category-testers', method: 'POST', contentType: 'application/json', dataType: 'json',
            data: JSON.stringify({ categoryId: activeCat.id, targetUserId: selectedTesterUserId, roleId: parseInt($('#testerRole').val(), 10) })
        })
            .done(function() {
                Toast.fire({ icon: 'success', title: 'Tester assigned' });
                $('#testerSearch').val(''); selectedTesterUserId = null;
                loadTesters(); loadCategories();
            })
            .fail(showApiError));
});

function removeTester(id, email, btn) {
    Swal.fire({ icon: 'warning', title: 'Remove tester?', html: `Remove <b>${escapeHtml(email || 'this tester')}</b> from this category?`, showCancelButton: true, confirmButtonText: 'Remove', confirmButtonColor: '#dc2626' })
        .then(r => {
            if (!r.isConfirmed) return;
            busy(btn, () =>
                $.ajax({ url: '/api/category-testers/' + encodeURIComponent(id), method: 'DELETE', dataType: 'json' })
                    .done(function() { Toast.fire({ icon: 'success', title: 'Tester removed' }); loadTesters(); loadCategories(); })
                    .fail(showApiError));
        });
}

$(document).on('click', function(e) {
    if (!$(e.target).closest('#testerSearch, #testerAutocomplete').length) $('#testerAutocomplete').addClass('hidden');
});

loadCategories();
</script>
@endpush
@endsection
