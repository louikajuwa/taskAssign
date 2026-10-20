@extends('layouts.app')

@section('title', 'Manage Users - TaskAssign')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Manage Users</h1>
        <button type="button" onclick="openCreateUser()" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 text-sm font-medium focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">Create User</button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Name</th>
                    <th class="px-4 py-3 text-left">Email</th>
                    <th class="px-4 py-3 text-left">Role</th>
                    <th class="px-4 py-3 text-left">Can Create</th>
                    <th class="px-4 py-3 text-left">Created</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($users as $u)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">
                        {{ $u->name }}
                        @if($u->id === auth()->id())
                        <span class="ml-1 px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded text-[10px] uppercase tracking-wide">You</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $u->email }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 bg-brand-100 text-brand-700 rounded text-xs">{{ $u->permission_level }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($u->allow_creation)
                        <span class="text-green-600">Yes</span>
                        @else
                        <span class="text-gray-400">No</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $u->created_at->format('M d, Y') }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <button type="button" class="text-brand-600 hover:text-brand-800 text-sm font-medium rounded focus-visible:ring-2 focus-visible:ring-brand-500"
                                data-id="{{ $u->id }}"
                                data-name="{{ $u->name }}"
                                data-email="{{ $u->email }}"
                                data-role="{{ $u->permission_level }}"
                                data-allow="{{ $u->allow_creation ? '1' : '0' }}"
                                onclick="openEditUser(this.dataset)">Edit</button>
                        @if($u->id !== auth()->id())
                        <button type="button" class="ml-3 text-red-600 hover:text-red-800 text-sm font-medium rounded focus-visible:ring-2 focus-visible:ring-red-500"
                                data-id="{{ $u->id }}" data-email="{{ $u->email }}"
                                onclick="deleteUser(this.dataset.id, this.dataset.email, this)">Delete</button>
                        @else
                        <span class="ml-3 text-gray-300 text-sm" title="You cannot delete your own account">Delete</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</div>

@push('scripts')
<script>
const ROLES = @json($roles);

function userFormHtml(mode, u) {
    u = u || {};
    const roleOpts = ROLES.map(r =>
        `<option value="${escAttr(r)}" ${u.permission_level === r ? 'selected' : ''}>${escAttr(r)}</option>`
    ).join('');
    const inputCls = 'w-full px-3 py-2 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm';
    const lblCls = 'block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1';
    const pwHint = mode === 'edit' ? ' (leave blank to keep)' : '';
    return `
        <div class="text-left space-y-3">
            <div><label for="f-name" class="${lblCls}">Name</label><input id="f-name" autocomplete="off" class="${inputCls}" value="${escAttr(u.name)}"></div>
            <div><label for="f-email" class="${lblCls}">Email</label><input id="f-email" autocomplete="off" type="email" class="${inputCls}" value="${escAttr(u.email)}"></div>
            <div><label for="f-password" class="${lblCls}">Password${pwHint}</label><input id="f-password" type="password" class="${inputCls}" autocomplete="new-password"></div>
            <div><label for="f-password2" class="${lblCls}">Confirm Password</label><input id="f-password2" type="password" class="${inputCls}" autocomplete="new-password"></div>
            <div><label for="f-role" class="${lblCls}">Role</label><select id="f-role" class="${inputCls}">${roleOpts}</select></div>
            <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" id="f-allow" ${u.allow_creation ? 'checked' : ''}> Allow test plan creation</label>
        </div>`;
}

function collectForm() {
    return {
        name: document.getElementById('f-name').value.trim(),
        email: document.getElementById('f-email').value.trim(),
        password: document.getElementById('f-password').value,
        password_confirmation: document.getElementById('f-password2').value,
        permission_level: document.getElementById('f-role').value,
        allow_creation: document.getElementById('f-allow').checked ? 1 : 0,
    };
}

function ajaxError(xhr) {
    if (xhr.status === 401 || xhr.status === 419) { handleAjaxError(xhr); return 'Session expired.'; }
    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
        return Object.values(xhr.responseJSON.errors).flat().join(' ');
    }
    return (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.';
}

function openCreateUser() {
    Swal.fire({
        title: 'Create User',
        html: userFormHtml('create'),
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Create',
        confirmButtonColor: '#0083bb',
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading(),
        width: 'min(32rem, 95vw)',
        customClass: { popup: 'rounded-2xl', htmlContainer: 'm-0' },
        preConfirm: () => {
            const payload = collectForm();
            return $.ajax({
                url: '{{ route('admin.users.store') }}',
                method: 'POST', contentType: 'application/json', dataType: 'json',
                data: JSON.stringify(payload),
            }).then(res => res, xhr => { Swal.showValidationMessage(escapeHtml(ajaxError(xhr))); });
        }
    }).then(result => {
        if (result.isConfirmed && result.value && result.value.result === 'success') {
            Swal.fire({ icon: 'success', title: 'User created', timer: 1400, showConfirmButton: false })
                .then(() => location.reload());
        }
    });
}

function openEditUser(ds) {
    // ds is the button's dataset (strings): {id, name, email, role, allow}
    const u = { id: ds.id, name: ds.name, email: ds.email, permission_level: ds.role, allow_creation: ds.allow === '1' };
    Swal.fire({
        title: 'Edit User',
        html: userFormHtml('edit', u),
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Save Changes',
        confirmButtonColor: '#0083bb',
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading(),
        width: 'min(32rem, 95vw)',
        customClass: { popup: 'rounded-2xl', htmlContainer: 'm-0' },
        preConfirm: () => {
            const payload = collectForm();
            return $.ajax({
                url: '/admin/users/' + encodeURIComponent(u.id),
                method: 'PUT', contentType: 'application/json', dataType: 'json',
                data: JSON.stringify(payload),
            }).then(res => res, xhr => { Swal.showValidationMessage(escapeHtml(ajaxError(xhr))); });
        }
    }).then(result => {
        if (result.isConfirmed && result.value && result.value.result === 'success') {
            Swal.fire({ icon: 'success', title: 'User updated', timer: 1400, showConfirmButton: false })
                .then(() => location.reload());
        }
    });
}

function deleteUser(id, email, btn) {
    Swal.fire({
        icon: 'warning',
        title: 'Delete user?',
        html: `Permanently delete <b>${escapeHtml(email)}</b>? This cannot be undone.`,
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#dc2626',
        customClass: { popup: 'rounded-2xl' },
    }).then(result => {
        if (!result.isConfirmed) return;
        withBusy(btn, () => new Promise(resolve => {
            $.ajax({ url: '/admin/users/' + encodeURIComponent(id), method: 'DELETE', dataType: 'json' })
                .done(() => Swal.fire({ icon: 'success', title: 'User deleted', timer: 1400, showConfirmButton: false }).then(() => location.reload()))
                .fail(xhr => { if (xhr.status === 401 || xhr.status === 419) return; Swal.fire({ icon: 'error', title: 'Cannot delete', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed.' }); })
                .always(resolve);
        }));
    });
}
</script>
@endpush
@endsection
