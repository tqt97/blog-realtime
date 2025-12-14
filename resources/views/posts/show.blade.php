<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl">{{ $post->title }}</h2>
            @auth
                <x-notification-bell />
            @endauth
        </div>
    </x-slot>
    @php
        $initialComments = $post->comments
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'content' => $c->content,
                    'created_at' => $c->created_at->toISOString(),
                    'user' => [
                        'id' => $c->user->id,
                        'name' => $c->user->name,
                    ],
                ];
            })
            ->values();
    @endphp


    <div class="max-w-3xl mx-auto py-6" x-data="postRealtime({
        postId: {{ (int) $post->id }},
        likeUrl: '{{ route('posts.like', $post) }}',
        commentUrl: '{{ route('posts.comments.store', $post) }}',
        initialLikeCount: {{ (int) $post->likes()->count() }},
        initialComments: @json($initialComments),
    })" x-init="init()">
        <article class="prose max-w-none">
            {!! nl2br(e($post->content)) !!}
        </article>

        @auth
            <div class="mt-6 flex items-center gap-3">
                <button class="px-3 py-1 rounded border" @click="toggleLike()" x-text="liked ? 'Unlike' : 'Like'"></button>

                <div>❤️ <span x-text="likeCount"></span></div>
            </div>
        @endauth

        <section class="mt-8">
            <h3 class="font-semibold text-lg mb-3">Comments</h3>

            @auth
                <form @submit.prevent="submitComment()" class="mb-4">
                    <textarea x-model="newComment" class="w-full border rounded p-2" rows="3" placeholder="Write a comment..."></textarea>
                    <div class="mt-2 flex items-center gap-2">
                        <button class="px-3 py-1 rounded bg-black text-white" type="submit" :disabled="submitting">
                            Post
                        </button>
                        <span class="text-sm text-red-600" x-text="error"></span>
                    </div>
                </form>
            @endauth

            <div class="space-y-3">
                <template x-for="c in comments" :key="c.id">
                    <div class="border rounded p-3">
                        <div class="text-sm text-gray-600">
                            <span class="font-medium" x-text="c.user.name"></span>
                            · <span x-text="new Date(c.created_at).toLocaleString()"></span>
                        </div>
                        <div class="mt-1" x-text="c.content"></div>
                    </div>
                </template>
            </div>
        </section>
    </div>
</x-app-layout>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('postRealtime', (cfg) => ({
            postId: cfg.postId,
            likeCount: cfg.initialLikeCount ?? 0,
            comments: cfg.initialComments ?? [],
            liked: false,

            newComment: '',
            submitting: false,
            error: '',

            init() {
                // Listen public channel post.{id}
                if (window.Echo) {
                    window.Echo.channel(`post.${this.postId}`)
                        .listen('.post.like_count_updated', (e) => {
                            this.likeCount = e.likeCount ?? e.like_count ?? this.likeCount;
                        })
                        .listen('.post.comment_created', (e) => {
                            if (!e.comment) return;
                            // tránh duplicate append
                            if (this.comments.some(x => x.id === e.comment.id)) return;
                            this.comments = [e.comment, ...this.comments];
                        });
                }
            },

            async toggleLike() {
                const res = await fetch(cfg.likeUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector(
                            'meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({}),
                });

                const data = await res.json();
                this.liked = !!data.liked;
                // likeCount sẽ được sync qua realtime event, nhưng set tạm cũng ok
                if (typeof data.like_count === 'number') this.likeCount = data.like_count;
            },

            async submitComment() {
                this.error = '';
                const content = (this.newComment || '').trim();
                if (!content) {
                    this.error = 'Comment cannot be empty.';
                    return;
                }

                this.submitting = true;
                try {
                    const res = await fetch(cfg.commentUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector(
                                'meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            content
                        }),
                    });

                    if (!res.ok) {
                        const err = await res.json().catch(() => ({}));
                        this.error = err?.message ?? 'Failed to comment.';
                        return;
                    }

                    this.newComment = '';
                    // append sẽ tới qua realtime
                } finally {
                    this.submitting = false;
                }
            },
        }));
    });
</script>
