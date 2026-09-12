<div
    class="nb-wrap"
    x-data="{
        open: @entangle('open'),
        hasNew: {{ $unreadCount > 0 ? 'true' : 'false' }},
        rippling: false,
        webPushOn: localStorage.getItem('dcms_webpush_active') === '1',
        webPushLoading: false,
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.rippling = true;
                setTimeout(() => this.rippling = false, 700);
                this.checkWebPushState();
            }
        },
        async checkWebPushState() {
            if (!('Notification' in window) || !('serviceWorker' in navigator)) {
                this.webPushOn = false;
                localStorage.setItem('dcms_webpush_active', '0');
                return;
            }
            if (Notification.permission === 'granted') {
                try {
                    const reg = await navigator.serviceWorker.ready;
                    const sub = await reg.pushManager.getSubscription();
                    if (sub) {
                        this.webPushOn = true;
                        localStorage.setItem('dcms_webpush_active', '1');
                    } else if (localStorage.getItem('dcms_webpush_active') === '1') {
                        this.webPushOn = true;
                    } else {
                        this.webPushOn = false;
                        localStorage.setItem('dcms_webpush_active', '0');
                    }
                } catch(e) {}
            } else {
                this.webPushOn = false;
                localStorage.setItem('dcms_webpush_active', '0');
            }
        },
        async toggleWebPush() {
            if (!('Notification' in window) || !('serviceWorker' in navigator)) {
                alert('Browser Anda tidak mendukung Web Push Notification.');
                return;
            }
            if (this.webPushLoading) return;
            this.webPushLoading = true;
            if (this.webPushOn) {
                try {
                    const reg = await navigator.serviceWorker.ready;
                    const sub = await reg.pushManager.getSubscription();
                    if (sub) {
                        const endpoint = sub.endpoint;
                        await sub.unsubscribe();
                        const csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
                        await fetch('/api/push-subscriptions', {
                            method: 'DELETE',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: JSON.stringify({ endpoint })
                        });
                    }
                    this.webPushOn = false;
                    localStorage.setItem('dcms_webpush_active', '0');
                } catch(e) {
                    alert('Gagal mematikan Web Push: ' + e.message);
                } finally {
                    this.webPushLoading = false;
                }
            } else {
                if (Notification.permission === 'denied') {
                    alert('Notifikasi diblokir oleh browser.\n\nCara mengaktifkan:\n1. Klik ikon 🔒 gembok di address bar\n2. Ubah Notifikasi → Izinkan\n3. Refresh halaman');
                    this.webPushLoading = false;
                    return;
                }
                try {
                    const perm = await Notification.requestPermission();
                    if (perm !== 'granted') {
                        this.webPushLoading = false;
                        return;
                    }
                    const reg = await navigator.serviceWorker.ready;
                    const keyRes = await fetch('/api/push-subscriptions/vapid-key');
                    const keyData = await keyRes.json();
                    const sub = await reg.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: this.urlBase64ToUint8Array(keyData.publicKey)
                    });
                    const csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
                    const subJson = Object.assign({}, sub.toJSON(), { send_welcome: true });
                    await fetch('/api/push-subscriptions', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify(subJson)
                    });
                    this.webPushOn = true;
                    localStorage.setItem('dcms_webpush_active', '1');
                } catch(e) {
                    alert('Gagal mengaktifkan Web Push: ' + e.message);
                } finally {
                    this.webPushLoading = false;
                }
            }
        },
        async sendTestPush() {
            if (!this.webPushOn) {
                alert('Aktifkan Web Push terlebih dahulu dengan menekan toggle (Push ON).');
                return;
            }
            try {
                const csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
                const res = await fetch('/api/push-subscriptions/send-test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }
                });
                const data = await res.json();
                if (data.message) {
                    // Notifikasi terkirim via Web Push backend
                } else if (data.error) {
                    alert(data.error);
                }
            } catch(e) {
                alert('Gagal mengirim notifikasi uji coba: ' + e.message);
            }
        },
        urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }
    }"
    x-init="checkWebPushState()"
    x-on:click.outside="open = false"
    wire:poll.20s>
    {{-- ── Bell Button ── --}}
    <button
        type="button"
        class="nb-btn"
        x-on:click="toggle()"
        :class="{ 'nb-btn--active': open }"
        aria-label="Notifikasi"
        id="notification-bell-btn">
        {{-- Ripple layer --}}
        <span class="nb-ripple" x-show="rippling" x-cloak></span>

        {{-- Bell Icon Container --}}
        <span
            class="nb-icon"
            :class="{ 'nb-icon--shake': hasNew && !open }">
            {{-- Bell Icon (shown when closed) --}}
            <span
                x-show="!open"
                style="display:flex;align-items:center;justify-content:center;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="19" height="19">
                    <path d="M5.25 9a6.75 6.75 0 0 1 13.5 0v.75c0 2.123.8 4.057 2.118 5.52a.75.75 0 0 1-.297 1.206c-1.544.57-3.16.99-4.831 1.243a3.75 3.75 0 1 1-7.48 0 24.585 24.585 0 0 1-4.831-1.244.75.75 0 0 1-.298-1.205A8.217 8.217 0 0 0 5.25 9.75V9Zm4.502 8.9a2.25 2.25 0 1 0 4.496 0 25.057 25.057 0 0 1-4.496 0Z" />
                </svg>
            </span>
            {{-- X Close Icon (shown when open) --}}
            <span
                x-show="open"
                x-cloak
                style="display:flex;align-items:center;justify-content:center;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </span>
        </span>

        {{-- Unread Badge (hidden when dropdown is open to prevent overlapping X icon) --}}
        @if ($unreadCount > 0)
        <span class="nb-badge" id="nb-badge-count" x-show="!open" x-cloak>
            <span class="nb-badge-pulse"></span>
            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
        </span>
        @endif
    </button>

    {{-- ── Dropdown Panel ── --}}
    <div
        class="nb-panel"
        x-show="open"
        x-transition:enter="nb-panel-enter-active"
        x-transition:enter-start="nb-panel-enter-from"
        x-transition:enter-end="nb-panel-enter-to"
        x-transition:leave="nb-panel-leave-active"
        x-transition:leave-start="nb-panel-leave-from"
        x-transition:leave-end="nb-panel-leave-to"
        x-cloak>
        {{-- Shimmer accent line --}}
        <div class="nb-panel-shimmer"></div>

        {{-- Header --}}
        <div class="nb-panel-head">
            <div class="nb-panel-head-row nb-panel-head-row1">
                <div class="nb-panel-head-left">
                    <span class="nb-panel-head-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M5.25 9a6.75 6.75 0 0 1 13.5 0v.75c0 2.123.8 4.057 2.118 5.52a.75.75 0 0 1-.297 1.206c-1.544.57-3.16.99-4.831 1.243a3.75 3.75 0 1 1-7.48 0 24.585 24.585 0 0 1-4.831-1.244.75.75 0 0 1-.298-1.205A8.217 8.217 0 0 0 5.25 9.75V9Zm4.502 8.9a2.25 2.25 0 1 0 4.496 0 25.057 25.057 0 0 1-4.496 0Z" />
                        </svg>
                    </span>
                    <span class="nb-panel-title">Notifikasi</span>
                </div>

                @if ($unreadCount > 0)
                <span class="nb-panel-new-badge">
                    <span class="nb-panel-new-badge-dot"></span>
                    {{ $unreadCount }} baru
                </span>
                @endif
            </div>

            <div class="nb-panel-head-row nb-panel-head-row2">
                @if ($unreadCount > 0)
                <button
                    type="button"
                    class="nb-read-all-btn"
                    wire:click="markAllAsRead"
                    title="Tandai semua sudah dibaca">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Baca semua
                </button>
                @endif

                {{-- Toggle Switch ON/OFF Web Push Notification (Minimalist) --}}
                <button
                    type="button"
                    class="nb-webpush-toggle"
                    x-on:click.stop="toggleWebPush()"
                    :title="webPushOn ? 'Notif Pop-up: Aktif' : 'Notif Pop-up: Nonaktif'"
                    role="switch"
                    :aria-checked="webPushOn">
                    <span class="nb-webpush-label">Notif Pop-up</span>
                    <span class="nb-webpush-track" :class="{ 'nb-webpush-track--active': webPushOn }">
                        <span class="nb-webpush-thumb" :class="{ 'nb-webpush-thumb--active': webPushOn }"></span>
                    </span>
                </button>
            </div>
        </div>

        {{-- Notification List --}}
        <ul class="nb-panel-list" id="nb-notification-list">
            @forelse ($notifications as $index => $notif)
            @php
            $data = is_array($notif->data) ? $notif->data : (json_decode($notif->data ?? '[]', true) ?? []);
            $type = $data['type'] ?? '';
            $docId = $data['document_id'] ?? null;
            $title = $data['title'] ?? 'Notifikasi';
            $code = $data['code_number'] ?? '';
            $message = $data['message'] ?? ($data['body'] ?? '');
            $isUnread = is_null($notif->read_at);

            $user = auth()->user();
            $panelPath = request()->segment(1) ?: 'admin';

            // Parse document_id or meeting_id from action URL if not explicitly set
            if (!$docId && !empty($data['actions'][0]['url'])) {
            if (preg_match('/\/documents\/(\d+)/', $data['actions'][0]['url'], $matches)) {
            $docId = $matches[1];
            }
            }
            $meetingId = $data['meeting_id'] ?? null;
            if (!$meetingId && !empty($data['actions'][0]['url'])) {
            if (preg_match('/\/meetings\/(\d+)/', $data['actions'][0]['url'], $matches)) {
            $meetingId = $matches[1];
            }
            }

            if ($docId) {
            if (in_array($type, ['document_submitted', 'document_approved_kabid']) || str_contains(strtolower($title), 'persetujuan kabid') || str_contains(strtolower($title), 'menunggu review')) {
            $href = ($user && ($user->hasRole('kabid') || $user->hasRole('direktur')))
            ? "/reviewer/review-documents/{$docId}"
            : "/{$panelPath}/documents/{$docId}";
            } elseif (in_array($type, ['document_final_decision', 'document_created', 'document_expiry_reminder'])) {
            $href = "/{$panelPath}/documents/{$docId}";
            } elseif ($type === 'change_request_submitted' || $type === 'change_request_status_updated') {
            $crId = $data['change_request_id'] ?? '';
            $href = $crId ? "/{$panelPath}/document-change-requests/{$crId}" : "/{$panelPath}/document-change-requests";
            } elseif ($type === 'discussion_replied' || $type === 'discussion_pinned') {
            $href = "/{$panelPath}/documents/{$docId}#discussion-section";
            } else {
            $href = ($user && ($user->hasRole('kabid') || $user->hasRole('direktur')) && str_contains(strtolower($title), 'kabid'))
            ? "/reviewer/review-documents/{$docId}"
            : "/{$panelPath}/documents/{$docId}";
            }
            } elseif ($meetingId) {
            $href = "/{$panelPath}/meetings/{$meetingId}";
            } elseif (!empty($data['actions'][0]['url'])) {
            $rawUrl = $data['actions'][0]['url'];
            $path = parse_url($rawUrl, PHP_URL_PATH);
            $href = $path ?: $rawUrl;
            } else {
            $href = '#';
            }

            // Icon color class
            $iconClass = match($type) {
            'document_final_decision' => ($data['decision'] ?? '') === 'approved' ? 'nb-icon-wrap--approved' : 'nb-icon-wrap--rejected',
            'document_submitted', 'document_created', 'change_request_submitted', 'discussion_replied', 'meeting_invitation' => 'nb-icon-wrap--new',
            'document_approved_kabid', 'discussion_pinned' => 'nb-icon-wrap--kabid',
            'document_expiry_reminder', 'change_request_status_updated' => (($data['status'] ?? '') === 'rejected' ? 'nb-icon-wrap--rejected' : 'nb-icon-wrap--approved'),
            default => 'nb-icon-wrap--default',
            };

            // SVG icon per type
            $typeSvg = match($type) {
            'document_submitted', 'document_created' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>',
            'document_approved_kabid' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>',
            'document_final_decision' => ($data['decision'] ?? '') === 'approved'
            ? '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
            </svg>'
            : '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>',
            'document_expiry_reminder' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>',
            'change_request_submitted', 'change_request_status_updated' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
            </svg>',
            'discussion_replied' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
            </svg>',
            'discussion_pinned' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13h1m2 0h1m2 0h1m2 0h1m2 0h1m2 0h1M6 21v-8a6 6 0 0 1 12 0v8M12 3v2" />
            </svg>',
            'meeting_invitation' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
            </svg>',
            default => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>',
            };

            // Badge label
            $badgeLabel = match($type) {
            'document_submitted', 'document_created' => 'Dokumen Baru',
            'document_approved_kabid' => 'Kabid Disetujui',
            'document_final_decision' => ($data['decision'] ?? '') === 'approved' ? 'Disetujui' : 'Ditolak',
            'document_expiry_reminder' => 'Pengingat',
            'change_request_submitted' => 'Revisi Baru',
            'change_request_status_updated' => 'Status Revisi',
            'discussion_replied' => 'Balasan Q&A',
            'discussion_pinned' => 'Jawaban Resmi',
            'meeting_invitation' => 'Undangan Rapat',
            default => 'Info',
            };

            $badgeClass = match($type) {
            'document_final_decision' => ($data['decision'] ?? '') === 'approved'
            ? 'nb-badge-label--approved'
            : 'nb-badge-label--rejected',
            'document_submitted', 'document_created', 'change_request_submitted', 'discussion_replied', 'meeting_invitation' => 'nb-badge-label--new',
            'document_approved_kabid', 'discussion_pinned' => 'nb-badge-label--kabid',
            'document_expiry_reminder' => 'nb-badge-label--rejected',
            'change_request_status_updated' => (($data['status'] ?? '') === 'rejected' ? 'nb-badge-label--rejected' : 'nb-badge-label--approved'),
            default => 'nb-badge-label--pending',
            };
            @endphp

            <li class="nb-panel-item-wrap">
                <a
                    href="{{ $href }}"
                    class="nb-panel-item {{ $isUnread ? 'nb-panel-item--unread' : '' }}"
                    wire:click="markAsRead('{{ $notif->id }}')"
                    title="{{ $title }}">
                    {{-- Icon wrap --}}
                    <span class="nb-item-icon-wrap {{ $iconClass }}">
                        <span class="nb-item-emoji">{!! $typeSvg !!}</span>
                    </span>

                    <span class="nb-item-content">
                        <span class="nb-item-title">{{ Str::limit($title, 40) }}</span>
                        @if ($message)
                        <span class="nb-item-msg">{{ Str::limit($message, 55) }}</span>
                        @endif
                        <span class="nb-item-meta">
                            @if ($code)
                            <span class="nb-item-code">{{ $code }}</span>
                            <span class="nb-item-meta-sep">·</span>
                            @endif
                            <span class="nb-badge-label {{ $badgeClass }}">{{ $badgeLabel }}</span>
                        </span>
                        <span class="nb-item-time">
                            <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            {{ $notif->created_at->diffForHumans() }}
                        </span>
                    </span>

                    <svg xmlns="http://www.w3.org/2000/svg" class="nb-chevron" width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </li>
            @empty
            <li class="nb-empty">
                <span class="nb-empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.143 17.082a24.248 24.248 0 0 0 3.844.148m-3.844-.148a23.856 23.856 0 0 1-5.455-1.31 8.964 8.964 0 0 0 2.3-5.542m3.155 6.852a3 3 0 0 0 5.667 1.97m1.965-2.277L21 21m-4.225-4.225a23.81 23.81 0 0 0 .342-5.048m-9.841-4.199a8.952 8.952 0 0 1-.299-1.5 3 3 0 1 0-5.862-1.144l.009.12" />
                    </svg>
                </span>
                <span class="nb-empty-title">Semua beres!</span>
                <span class="nb-empty-sub">Tidak ada notifikasi saat ini</span>
            </li>
            @endforelse
        </ul>

        {{-- Footer --}}
        @if ($notifications->count() > 0)
        <div class="nb-panel-footer">
            <span class="nb-panel-footer-info">
                {{ $notifications->count() }} notifikasi terakhir
            </span>
        </div>
        @endif
    </div>

    @once
    <style>
        /* ═══════════════════════════════════════════════════
   NOTIFICATION BELL — SLEEK MODERN REDESIGN
═══════════════════════════════════════════════════ */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        .nb-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* ── Button ─────────────────────────────────────── */
        .nb-btn {
            position: relative;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.12);
            color: rgba(255, 255, 255, 0.85);
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
            outline: none;
        }

        .nb-btn:hover {
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff;
            transform: scale(1.05);
        }

        .nb-btn--active {
            background: rgba(255, 255, 255, 0.25) !important;
            color: #ffffff !important;
        }

        /* ── Ripple ──────────────────────────────────────── */
        .nb-ripple {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.35) 0%, transparent 70%);
            animation: nb-ripple-anim 0.6s ease-out forwards;
            pointer-events: none;
        }

        @keyframes nb-ripple-anim {
            0% {
                transform: scale(0.6);
                opacity: 1;
            }

            100% {
                transform: scale(2.0);
                opacity: 0;
            }
        }

        /* ── Glow Ring ───────────────────────────────────── */
        .nb-glow-ring {
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            border: 1.5px solid rgba(255, 255, 255, 0.35);
            opacity: 0;
            transform: scale(0.9);
            transition: opacity 0.2s ease, transform 0.2s ease;
            pointer-events: none;
        }

        .nb-glow-ring--visible {
            opacity: 1;
            transform: scale(1);
        }

        /* ── Bell Icon Container ─────────────────────────── */
        .nb-icon {
            width: 19px;
            height: 19px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── Bell shake ──────────────────────────────────── */
        .nb-icon--shake {
            animation: nb-bell-shake 4s cubic-bezier(0.36, 0.07, 0.19, 0.97) infinite;
        }

        @keyframes nb-bell-shake {

            0%,
            42%,
            100% {
                transform: rotate(0deg);
            }

            45% {
                transform: rotate(-14deg);
            }

            50% {
                transform: rotate(12deg);
            }

            55% {
                transform: rotate(-9deg);
            }

            60% {
                transform: rotate(6deg);
            }

            65% {
                transform: rotate(-3deg);
            }

            70% {
                transform: rotate(0deg);
            }
        }

        /* ── Unread badge ────────────────────────────────── */
        .nb-badge {
            position: absolute;
            top: 0px;
            right: 0px;
            min-width: 17px;
            height: 17px;
            padding: 0 4px;
            border-radius: 999px;
            background: linear-gradient(135deg, #f43f5e, #e11d48);
            color: #fff;
            font-size: 9px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #0f172a;
            box-shadow: 0 2px 6px rgba(225, 29, 72, 0.4);
            letter-spacing: -0.3px;
            z-index: 2;
        }

        .nb-badge-pulse {
            position: absolute;
            inset: -2px;
            border-radius: 999px;
            background: rgba(244, 63, 94, 0.5);
            animation: nb-pulse 2.5s cubic-bezier(0.22, 1, 0.36, 1) infinite;
        }

        @keyframes nb-pulse {
            0% {
                transform: scale(1);
                opacity: 0.8;
            }

            60% {
                transform: scale(2.2);
                opacity: 0;
            }

            100% {
                transform: scale(2.2);
                opacity: 0;
            }
        }

        /* ── Dropdown panel ──────────────────────────────── */
        .nb-panel {
            position: absolute;
            top: calc(100% + 10px);
            right: -4px;
            width: 368px;
            max-width: calc(100vw - 24px);
            max-height: 440px;
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 16px;
            box-shadow:
                0 10px 30px -5px rgba(0, 0, 0, 0.15),
                0 20px 40px -15px rgba(0, 0, 0, 0.10);
            overflow: hidden !important;
            z-index: 9999;
            transform-origin: top right;
        }

        /* Arrow pointer */
        .nb-panel::before {
            content: '';
            position: absolute;
            top: -5px;
            right: 15px;
            width: 10px;
            height: 10px;
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-bottom: none;
            border-right: none;
            transform: rotate(45deg);
            z-index: 2;
        }

        /* ── Panel transitions ───────────────────────────── */
        .nb-panel-enter-active {
            transition: opacity 0.2s ease, transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .nb-panel-leave-active {
            transition: opacity 0.15s ease, transform 0.15s ease;
        }

        .nb-panel-enter-from {
            opacity: 0;
            transform: scale(0.94) translateY(-8px);
        }

        .nb-panel-enter-to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }

        .nb-panel-leave-from {
            opacity: 1;
            transform: scale(1) translateY(0);
        }

        .nb-panel-leave-to {
            opacity: 0;
            transform: scale(0.95) translateY(-4px);
        }

        /* ── Shimmer accent line ─────────────────────────── */
        .nb-panel-shimmer {
            height: 3px;
            background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 50%, #3b82f6 100%);
            background-size: 200% 100%;
            animation: nb-shimmer-slide 3s linear infinite;
        }

        @keyframes nb-shimmer-slide {
            0% {
                background-position: 0% 0%;
            }

            100% {
                background-position: 200% 0%;
            }
        }

        /* ── Panel Header ────────────────────────────────── */
        .nb-panel-head {
            display: flex;
            flex-direction: column;
            gap: 9px;
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            background: #fafcff;
        }

        .nb-panel-head-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nb-panel-head-row1 {
            justify-content: space-between;
        }

        .nb-panel-head-row2 {
            padding-top: 9px;
            border-top: 1px dashed #e8edf3;
        }

        .nb-panel-head-left {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .nb-panel-head-icon {
            width: 24px;
            height: 24px;
            border-radius: 7px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .nb-panel-title {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }

        .nb-panel-new-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .nb-panel-new-badge-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #64748b;
            flex-shrink: 0;
        }

        .nb-read-all-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10.5px;
            font-weight: 600;
            color: #4f46e5;
            background: #e0e7ff;
            border: none;
            cursor: pointer;
            padding: 4px 9px;
            border-radius: 6px;
            transition: background 0.2s ease, color 0.2s ease;
            font-family: inherit;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .nb-read-all-btn:hover {
            background: #c7d2fe;
            color: #3730a3;
        }

        /* ── Notification List ───────────────────────────── */
        .nb-panel-list {
            list-style: none;
            margin: 0;
            padding: 8px;
            max-height: 330px;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        .nb-panel-list::-webkit-scrollbar {
            width: 5px;
        }

        .nb-panel-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 99px;
        }

        .nb-panel-list::-webkit-scrollbar-track {
            background: transparent;
        }

        /* ── List Item Container ─────────────────────────── */
        .nb-panel-item-wrap {
            margin: 4px 0;
            padding: 0;
            list-style: none;
        }

        /* ── Item Card ───────────────────────────────────── */
        .nb-panel-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 12px;
            text-decoration: none !important;
            cursor: pointer;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            transition: background 0.2s ease, border-color 0.2s ease, transform 0.15s ease;
            position: relative;
            overflow: hidden;
        }

        .nb-panel-item:hover {
            background: #f1f5f9 !important;
            border-color: #e2e8f0;
            transform: translateY(-1px);
        }

        .nb-panel-item--unread {
            background: #f0f9ff !important;
            border-color: #bae6fd !important;
            border-left: 4px solid #0284c7 !important;
        }

        .nb-panel-item--unread:hover {
            background: #e0f2fe !important;
        }

        /* ── Item Icon Wrap ──────────────────────────────── */
        .nb-item-icon-wrap {
            width: 34px;
            height: 34px;
            border-radius: 99px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .nb-icon-wrap--new {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .nb-icon-wrap--approved {
            background: #d1fae5;
            color: #047857;
        }

        .nb-icon-wrap--rejected {
            background: #fee2e2;
            color: #b91c1c;
        }

        .nb-icon-wrap--kabid {
            background: #f3e8ff;
            color: #6d28d9;
        }

        .nb-icon-wrap--default {
            background: #f1f5f9;
            color: #475569;
        }

        /* ── Item Content ────────────────────────────────── */
        .nb-item-content {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .nb-item-title {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a !important;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nb-item-msg {
            font-size: 11px;
            color: #64748b !important;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-top: 1px;
        }

        .nb-item-meta {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 2px;
        }

        .nb-item-code {
            font-size: 9.5px;
            color: #475569 !important;
            font-family: monospace;
            background: #e2e8f0;
            padding: 1px 5px;
            border-radius: 4px;
            font-weight: 600;
        }

        .nb-item-meta-sep {
            color: #94a3b8;
            font-size: 9px;
        }

        .nb-item-time {
            display: flex;
            align-items: center;
            gap: 3px;
            font-size: 9.5px;
            color: #94a3b8 !important;
            margin-top: 2px;
        }

        .nb-item-time svg {
            flex-shrink: 0;
            opacity: 0.7;
        }

        /* ── Inline Badge Label ──────────────────────────── */
        .nb-badge-label {
            font-size: 8.5px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            letter-spacing: 0.1px;
            text-transform: uppercase;
        }

        .nb-badge-label--approved {
            background: #d1fae5;
            color: #047857 !important;
        }

        .nb-badge-label--rejected {
            background: #fee2e2;
            color: #b91c1c !important;
        }

        .nb-badge-label--new {
            background: #dbeafe;
            color: #1d4ed8 !important;
        }

        .nb-badge-label--kabid {
            background: #f3e8ff;
            color: #6d28d9 !important;
        }

        .nb-badge-label--pending {
            background: #fef3c7;
            color: #b45309 !important;
        }

        /* ── Chevron ─────────────────────────────────────── */
        .nb-chevron {
            color: #94a3b8;
            flex-shrink: 0;
            align-self: center;
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .nb-panel-item:hover .nb-chevron {
            color: #4f46e5;
            transform: translateX(2px);
        }

        /* ── Empty state ─────────────────────────────────── */
        .nb-empty {
            padding: 30px 16px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .nb-empty-icon {
            color: #cbd5e1;
        }

        .nb-empty-title {
            font-size: 13px;
            font-weight: 700;
            color: #334155 !important;
        }

        .nb-empty-sub {
            font-size: 11px;
            color: #94a3b8 !important;
        }

        /* ── Footer ──────────────────────────────────────── */
        .nb-panel-footer {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-top: 1px solid #f1f5f9;
            background: #fafcff;
        }

        .nb-panel-footer-info {
            font-size: 10px;
            color: #94a3b8 !important;
            font-weight: 500;
            flex: 1;
        }

        .nb-footer-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #10b981;
            flex-shrink: 0;
        }

        .nb-panel-footer-live {
            font-size: 9.5px;
            font-weight: 700;
            color: #10b981 !important;
            text-transform: uppercase;
        }

        /* ── Web Push Toggle Switch in Bell Header (Minimalist) ── */
        .nb-webpush-toggle {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: transparent;
            border: none;
            padding: 0;
            margin-left: auto;
            cursor: pointer;
            user-select: none;
            outline: none;
            transition: opacity 0.2s ease;
            flex-shrink: 0;
        }

        .nb-webpush-toggle:hover {
            opacity: 0.85;
        }

        .nb-webpush-label {
            font-size: 11px;
            font-weight: 500;
            color: #1e293b !important;
            text-transform: none;
            letter-spacing: 0;
            transition: color 0.2s ease;
        }

        .nb-webpush-track {
            position: relative;
            width: 28px;
            height: 16px;
            border-radius: 999px;
            background: #cbd5e1;
            transition: background 0.2s ease;
            display: inline-block;
            flex-shrink: 0;
        }

        .nb-webpush-track--active {
            background: #10b981 !important;
        }

        .nb-webpush-thumb {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .nb-webpush-thumb--active {
            transform: translateX(12px) !important;
        }

        /* ── Test Button in Footer ───────────────────────── */
        .nb-test-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            font-weight: 700;
            color: #0284c7;
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            cursor: pointer;
            padding: 3px 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .nb-test-btn:hover {
            background: #bae6fd;
            color: #0369a1;
        }

        /* ── Alpine x-cloak ──────────────────────────────── */
        [x-cloak] {
            display: none !important;
        }

        /* ── Mobile Responsiveness ───────────────────────── */
        @media (max-width: 767.98px) {

            /* Hapus posisi relative dari wrapper di mobile agar panel memposisikan dirinya terhadap topbar (yang lebarnya 100% layar) */
            .nb-wrap {
                position: static !important;
            }

            .nb-panel {
                position: absolute !important;
                top: 70px !important;
                /* 70px dari atas topbar */
                left: 16px !important;
                right: 16px !important;
                width: auto !important;
                /* width auto akan merentangkan panel ke left dan right */
                max-width: none !important;
                transform-origin: top center !important;
            }
        }
    </style>
    @endonce
</div>