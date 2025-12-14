@php
    $user = auth()->user();
@endphp

<div x-data="notificationBell({
    userId: {{ (int) $user->id }},
    fetchUrl: '{{ route('notifications.index') }}',
    markAllReadUrl: '{{ route('notifications.mark_all_read') }}',
})" x-init="init()" class="relative">
    <button type="button" @click="toggle()" class="relative inline-flex items-center">
        <span class="text-xl">🔔</span>

        <span x-show="unreadCount > 0" x-text="unreadCount"
            class="absolute -top-2 -right-2 text-xs px-1.5 py-0.5 rounded-full bg-red-600 text-white"></span>
    </button>

    <div x-show="open" @click.outside="open = false"
        class="absolute right-0 mt-2 w-96 bg-white border rounded-lg shadow-lg overflow-hidden z-50">
        <div class="flex items-center justify-between px-3 py-2 border-b">
            <div class="font-semibold">Notifications</div>
            <button class="text-sm text-blue-600 hover:underline" @click="markAllRead()">Mark all read</button>
        </div>

        <div class="max-h-96 overflow-auto">
            <template x-if="items.length === 0">
                <div class="p-3 text-sm text-gray-600">No notifications</div>
            </template>

            <template x-for="n in items" :key="n.id">
                <a :href="notificationLink(n)" class="block px-3 py-2 border-b hover:bg-gray-50">
                    <div class="text-sm">
                        <span class="font-medium" x-text="titleOf(n)"></span>
                    </div>
                    <div class="text-xs text-gray-500" x-text="timeOf(n)"></div>
                    <div x-show="!n.read_at" class="text-xs text-red-600">Unread</div>
                </a>
            </template>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('notificationBell', (cfg) => ({
            open: false,
            unreadCount: 0,
            items: [],

            async init() {
                await this.refresh();

                // Listen realtime notification + metrics on private channel
                if (window.Echo && cfg.userId) {
                    window.Echo.private(`users.${cfg.userId}`)
                        .notification((payload) => {
                            // Laravel broadcast notification shortcut
                            this.unshiftNotification(payload);
                        })
                        .listen('.user.metric_delta', (e) => {
                            // OPTIONAL: bạn có thể hook dashboard ở đây nếu muốn global
                            // console.log('metric delta', e.metric, e.delta);
                        });
                }
            },

            toggle() {
                this.open = !this.open;
                if (this.open) this.refresh();
            },

            async refresh() {
                const res = await fetch(cfg.fetchUrl, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                this.unreadCount = data.unread_count ?? 0;
                this.items = data.items ?? [];
            },

            async markAllRead() {
                await fetch(cfg.markAllReadUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector(
                            'meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({}),
                });

                // cập nhật local
                this.items = this.items.map(i => ({
                    ...i,
                    read_at: i.read_at ?? new Date().toISOString()
                }));
                this.unreadCount = 0;
            },

            unshiftNotification(payload) {
                // payload dạng { id, type, data, ... } tuỳ Laravel version
                const n = {
                    id: payload.id ?? crypto.randomUUID(),
                    read_at: null,
                    created_at: new Date().toISOString(),
                    data: payload,
                };
                this.items = [n, ...this.items].slice(0, 15);
                this.unreadCount++;
            },

            titleOf(n) {
                const type = n.data?.type ?? n.data?.data?.type;
                if (type === 'post_commented') return 'New comment on your post';
                if (type === 'post_liked') return 'Your post got a like';
                return 'New notification';
            },

            timeOf(n) {
                return n.created_at ? new Date(n.created_at).toLocaleString() : '';
            },

            notificationLink(n) {
                const postId = n.data?.post_id ?? n.data?.data?.post_id;
                if (postId) return `/posts/${postId}`; // nếu bạn route theo slug thì cần map khác
                return '#';
            },
        }));
    });
</script>
