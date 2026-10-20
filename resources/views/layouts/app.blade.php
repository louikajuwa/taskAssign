<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TaskAssign')</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    brand: {
                        50: '#e6f6fd', 100: '#ccedfa', 200: '#99dbf5', 300: '#66c9f0', 400: '#33b4ea',
                        500: '#009fe3', 600: '#0083bb', 700: '#006a98', 800: '#004f73', 900: '#00374f'
                    }
                }
            }
        }
    };
    </script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" integrity="sha384-YB/DdIkloKoRpclWB8bNcYXWakt57USgtQPDzvnIDHYU0lasD5eWlXVo1S4ODukY" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.2.4/dist/purify.min.js" integrity="sha384-eEu5CTj3qGvu9PdJuS+YlkNi7d2XxQROAFYOr59zgObtlcux1ae1Il3u7jvdCSWu" crossorigin="anonymous"></script>
    <script>
    // ---- Global helpers (available to every page) ----
    window.escapeHtml = function (s) {
        if (s === null || s === undefined) return '';
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/`/g, '&#96;');
    };
    window.escAttr = window.escapeHtml;
    window.sanitizeHtml = function (html) {
        if (html === null || html === undefined) return '';
        if (window.DOMPurify) return window.DOMPurify.sanitize(String(html));
        return window.escapeHtml(html);
    };
    // Only allow app-local storage paths or absolute http(s) URLs.
    window.safeUrl = function (u) {
        u = String(u === null || u === undefined ? '' : u).trim();
        return (/^\/storage\//.test(u) || /^https?:\/\//i.test(u)) ? u : '';
    };
    window.handleAjaxError = function (xhr, fallbackMsg) {
        var status = xhr && xhr.status;
        var msg;
        if (status === 401 || status === 419) {
            if (window.__sessionExpiredShown) return;
            window.__sessionExpiredShown = true;
            Swal.fire({ icon: 'warning', title: 'Session expired', text: 'Your session has expired. Please log in again.', allowOutsideClick: false, confirmButtonText: 'Log in' })
                .then(function () { window.location.href = '/login'; });
            return;
        } else if (status === 403) {
            msg = "You don't have permission to perform this action.";
        } else if (status === 422) {
            var errs = xhr.responseJSON && xhr.responseJSON.errors;
            msg = errs ? Object.values(errs).flat().join('\n') : ((xhr.responseJSON && xhr.responseJSON.message) || 'Validation failed.');
        } else if (!status || xhr.statusText === 'timeout') {
            msg = 'Network error. Please check your connection and try again.';
        } else {
            msg = (xhr.responseJSON && xhr.responseJSON.message) || fallbackMsg || 'Something went wrong. Please try again.';
        }
        Swal.fire({ icon: 'error', title: 'Error', text: msg });
    };
    // Disable a button while an async action runs; re-enable afterwards.
    window.withBusy = function (btn, promiseFn) {
        var $b = $(btn);
        if (!$b.length || $b.prop('disabled') || $b.data('busy')) return Promise.resolve();
        var orig = $b.html();
        $b.data('busy', true).prop('disabled', true).addClass('opacity-60 cursor-not-allowed')
          .html('<svg class="inline w-4 h-4 mr-1 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>Working…');
        var done = function () { $b.data('busy', false).prop('disabled', false).removeClass('opacity-60 cursor-not-allowed').html(orig); };
        var p;
        try { p = Promise.resolve(promiseFn()); } catch (e) { done(); return Promise.reject(e); }
        return p.then(function (r) { done(); return r; }, function (e) { done(); throw e; });
    };
    $(document).ajaxError(function (e, xhr) {
        if (xhr.status === 401 || xhr.status === 419) window.handleAjaxError(xhr);
    });
    </script>
    <style>
        :focus-visible { outline: 2px solid #009fe3; outline-offset: 2px; }
        .swal2-popup { max-width: 95vw; }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-100 min-h-screen">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[100] focus:bg-white focus:text-brand-700 focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg">Skip to main content</a>
    @auth
    <nav class="bg-white shadow-sm border-b border-gray-200" aria-label="Main">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap justify-between items-center gap-x-4 gap-y-1 py-2 min-h-[3.5rem]">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    <a href="/" class="text-lg font-bold text-gray-800 rounded focus-visible:ring-2 focus-visible:ring-brand-500">TaskAssign</a>
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-gray-900 rounded focus-visible:ring-2 focus-visible:ring-brand-500">Manage Users</a>
                    <a href="{{ route('admin.categories') }}" class="text-sm text-gray-600 hover:text-gray-900 rounded focus-visible:ring-2 focus-visible:ring-brand-500">Categories</a>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 sm:gap-x-4">
                    {{-- Notification Bell --}}
                    <div class="relative" id="notification-bell-container">
                        <button type="button" id="notification-bell" aria-label="Notifications" aria-haspopup="true" aria-expanded="false" aria-controls="notification-dropdown" class="relative p-2 text-gray-500 hover:text-gray-700 rounded-lg focus-visible:ring-2 focus-visible:ring-brand-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span id="notification-count" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold">0</span>
                        </button>
                        {{-- Dropdown --}}
                        <div id="notification-dropdown" class="hidden fixed sm:absolute right-4 sm:right-0 left-auto top-14 sm:top-auto mt-2 w-[min(24rem,calc(100vw-2rem))] bg-white rounded-lg shadow-xl border border-gray-200 z-50 max-h-96 overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                <h3 class="font-semibold text-gray-800">Notifications</h3>
                                <button type="button" id="mark-all-read-btn" class="text-xs text-brand-600 hover:text-brand-800 rounded focus-visible:ring-2 focus-visible:ring-brand-500">Mark all as read</button>
                            </div>
                            <div id="notification-list" class="overflow-y-auto max-h-72">
                                <div class="p-4 text-center text-gray-400 text-sm">No notifications</div>
                            </div>
                            <div class="border-t border-gray-100 px-4 py-2 text-center">
                                <a href="/notifications" class="text-sm text-brand-600 hover:text-brand-800">View all notifications</a>
                            </div>
                        </div>
                    </div>

                    {{-- Email toggle --}}
                    <div class="flex items-center gap-2">
                        <label class="relative inline-flex items-center cursor-pointer" title="Email notifications">
                            <input type="checkbox" id="email-toggle" aria-label="Email notifications" class="sr-only peer" {{ auth()->user()->email_notifications_enabled ? 'checked' : '' }}>
                            <div class="w-9 h-5 bg-gray-300 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-2 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-500"></div>
                        </label>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>

                    <span class="text-sm text-gray-600 max-w-[10rem] sm:max-w-none truncate">{{ auth()->user()->email }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800 rounded focus-visible:ring-2 focus-visible:ring-red-500">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    @endauth

    <main id="main">
        @yield('content')
    </main>

    @auth
    <script>
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    var pollTimer = null;
    function stopPolling() { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }

    // Notification bell
    function fetchUnreadCount() {
        $.get('/api/notifications/unread-count', function(res) {
            const badge = $('#notification-count');
            if (res.count > 0) {
                badge.text(res.count > 99 ? '99+' : res.count).removeClass('hidden');
            } else {
                badge.addClass('hidden');
            }
        }).fail(function(xhr) {
            if (xhr.status === 401 || xhr.status === 419) stopPolling();
        });
    }

    function loadNotifications() {
        $.get('/api/notifications?limit=10', function(res) {
            const list = $('#notification-list');
            if (!res.data || res.data.length === 0) {
                list.html('<div class="p-4 text-center text-gray-400 text-sm">No notifications</div>');
                return;
            }
            let html = '';
            res.data.forEach(function(n) {
                const isUnread = !n.read_at;
                const bg = isUnread ? 'bg-brand-50' : 'bg-white';
                const time = escapeHtml(timeAgo(n.created_at));
                const icon = getNotificationIcon(n.type);
                html += `<button type="button" class="notif-item w-full text-left flex items-start gap-3 px-4 py-3 hover:bg-gray-50 border-b border-gray-50 ${bg}" data-id="${escAttr(n.id)}">
                    <div class="flex-shrink-0 mt-1">${icon}</div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">${escapeHtml(n.title)}</p>
                        <p class="text-xs text-gray-500 truncate">${escapeHtml(n.message)}</p>
                        <p class="text-xs text-gray-400 mt-1">${time}</p>
                    </div>
                    ${isUnread ? '<div class="unread-dot w-2 h-2 bg-brand-500 rounded-full flex-shrink-0 mt-2"></div>' : ''}
                </button>`;
            });
            list.html(html);
        }).fail(function(xhr) {
            if (xhr.status === 401 || xhr.status === 419) return;
            $('#notification-list').html('<div class="p-4 text-center text-red-500 text-sm">Could not load notifications.</div>');
        });
    }

    function markNotificationRead(id, el) {
        $.ajax({ url: '/api/notifications/' + encodeURIComponent(id) + '/read', method: 'PUT' });
        $(el).removeClass('bg-brand-50').addClass('bg-white');
        $(el).find('.unread-dot').remove();
        fetchUnreadCount();
    }

    $('#notification-list').on('click', '.notif-item', function() {
        markNotificationRead($(this).data('id'), this);
    });

    $('#mark-all-read-btn').click(function() {
        const btn = this;
        withBusy(btn, function() {
            return new Promise(function(resolve) {
                $.ajax({ url: '/api/notifications/read-all', method: 'PUT' })
                    .done(function() { fetchUnreadCount(); loadNotifications(); })
                    .fail(function(xhr) { handleAjaxError(xhr, 'Could not mark notifications as read.'); })
                    .always(resolve);
            });
        });
    });

    function closeNotificationDropdown(returnFocus) {
        $('#notification-dropdown').addClass('hidden');
        $('#notification-bell').attr('aria-expanded', 'false');
        if (returnFocus) $('#notification-bell').trigger('focus');
    }

    $('#notification-bell').click(function(e) {
        e.stopPropagation();
        const dd = $('#notification-dropdown');
        dd.toggleClass('hidden');
        const open = !dd.hasClass('hidden');
        $(this).attr('aria-expanded', open ? 'true' : 'false');
        if (open) loadNotifications();
    });

    $(document).click(function(e) {
        if (!$(e.target).closest('#notification-bell-container').length) {
            closeNotificationDropdown(false);
        }
    });

    // Escape closes dropdowns (notification dropdown + any element tagged data-dropdown)
    $(document).on('keydown', function(e) {
        if (e.key !== 'Escape') return;
        if (!$('#notification-dropdown').hasClass('hidden')) closeNotificationDropdown(true);
        $('[data-dropdown]:not(.hidden)').addClass('hidden');
        $('[aria-haspopup="listbox"]').attr('aria-expanded', 'false');
    });

    // Email toggle
    function saveEmailPref(enabled, $el) {
        $el.prop('disabled', true);
        $.ajax({
            url: '/api/users/settings',
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({ email_notifications_enabled: enabled }),
            success: function() {
                Swal.fire({
                    icon: 'success',
                    title: enabled ? 'Email notifications on' : 'Email notifications off',
                    toast: true, position: 'top-end', showConfirmButton: false, timer: 2000
                });
            },
            error: function(xhr) {
                $el.prop('checked', !enabled); // revert on failure
                if (xhr.status === 401 || xhr.status === 419) return;
                Swal.fire({ icon: 'error', title: 'Could not update setting', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
            },
            complete: function() { $el.prop('disabled', false); }
        });
    }

    $('#email-toggle').change(function() {
        const $el = $(this);
        const enabled = this.checked;

        if (!enabled) {
            // Turning OFF — confirm first
            Swal.fire({
                icon: 'warning',
                title: 'Turn off email notifications?',
                text: "You'll still see in-app notifications, but no emails will be sent to you.",
                showCancelButton: true,
                confirmButtonText: 'Yes, turn off',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc2626',
            }).then(function(result) {
                if (result.isConfirmed) {
                    saveEmailPref(false, $el);
                } else {
                    $el.prop('checked', true); // revert — keep it on
                }
            });
        } else {
            saveEmailPref(true, $el);
        }
    });

    function getNotificationIcon(type) {
        const icons = {
            'assignment_added': '<svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>',
            'progress_completed': '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            'all_completed': '<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
            'progress_reverted': '<svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>',
            'comment_added': '<svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>',
        };
        return icons[type] || '<svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="2"/></svg>';
    }

    function timeAgo(value) {
        // Accepts a Unix timestamp (epoch seconds, the API default) or an ISO string.
        const date = (value !== null && value !== undefined && /^\d+$/.test(String(value)))
            ? new Date(parseInt(value, 10) * 1000)
            : new Date(value);
        const seconds = Math.floor((new Date() - date) / 1000);
        if (isNaN(seconds)) return '';
        if (seconds < 60) return 'just now';
        const mins = Math.floor(seconds / 60);
        if (mins < 60) return mins + 'm ago';
        const hrs = Math.floor(mins / 60);
        if (hrs < 24) return hrs + 'h ago';
        const days = Math.floor(hrs / 24);
        return days + 'd ago';
    }

    // Poll every 30 seconds (stops after a 401/419)
    fetchUnreadCount();
    pollTimer = setInterval(fetchUnreadCount, 30000);
    </script>
    @endauth

    @stack('scripts')
</body>
</html>
