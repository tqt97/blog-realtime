<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl">Dashboard</h2>
            <x-notification-bell />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="max-w-5xl mx-auto py-6" x-data="dashboardRealtime({
                    userId: {{ (int) auth()->id() }},
                    likesToday: {{ (int) $likesToday }},
                    commentsToday: {{ (int) $commentsToday }},
                    followers: {{ (int) $followers }},
                })" x-init="init()">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="border rounded p-4">
                            <div class="text-sm text-gray-600">Likes today</div>
                            <div class="text-2xl font-semibold" x-text="likesToday"></div>
                        </div>
                        <div class="border rounded p-4">
                            <div class="text-sm text-gray-600">Comments today</div>
                            <div class="text-2xl font-semibold" x-text="commentsToday"></div>
                        </div>
                        <div class="border rounded p-4">
                            <div class="text-sm text-gray-600">Followers</div>
                            <div class="text-2xl font-semibold" x-text="followers"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('dashboardRealtime', (cfg) => ({
                userId: cfg.userId,
                likesToday: cfg.likesToday,
                commentsToday: cfg.commentsToday,
                followers: cfg.followers,

                init() {
                    if (!window.Echo) return;

                    window.Echo.private(`users.${this.userId}`)
                        .listen('.user.metric_delta', (e) => {
                            if (e.metric === 'likes_today') this.likesToday += e.delta;
                            if (e.metric === 'comments_today') this.commentsToday += e.delta;
                            if (e.metric === 'followers') this.followers += e.delta;
                        });
                },
            }));
        });
    </script>
</x-app-layout>
