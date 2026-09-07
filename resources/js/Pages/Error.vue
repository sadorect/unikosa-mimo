<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// Title and message are resolved server-side by App\Support\ErrorPage, which
// prefers the application's own explanation ("You have already voted in this
// election") over the generic wording for the status code.
const props = defineProps({
    status: Number,
    title: String,
    message: String,
    action: String,
    signedIn: Boolean,
    // Populated only for a super admin; see App\Support\ErrorResponder.
    diagnostic: Object,
});

const title = computed(() => props.title || 'Unexpected error');
const message = computed(() => props.message || 'Something went wrong. Please try again.');

// Where someone can usefully go next differs by what went wrong: a member who
// timed out needs the sign-in page, one who lacks permission needs their
// dashboard, and a signed-out visitor needs the front page.
const primary = computed(() => {
    if (props.action === 'login' && !props.signedIn) {
        return { label: 'Sign in', href: route('login') };
    }

    if (props.signedIn) {
        return { label: 'Back to dashboard', href: route('dashboard') };
    }

    return { label: 'Go home', href: '/' };
});

const showDetail = ref(false);

const canRetry = computed(() => [408, 429, 500, 502, 503, 504].includes(props.status));
</script>

<template>
    <Head>
        <title>{{ title }}</title>
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md text-center">
            <div class="text-xl font-bold text-accent-500 mb-8">{{ $page.props.settings?.site_name || 'UNIKOSA' }}</div>

            <div class="text-6xl font-bold text-gray-200 dark:text-gray-700 mb-4">{{ status }}</div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ title }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-8 leading-relaxed">{{ message }}</p>

            <div class="flex items-center justify-center gap-4 text-sm flex-wrap">
                <Link :href="primary.href" class="bg-accent-500 hover:bg-accent-600 text-white font-medium px-5 py-2 rounded-lg transition">
                    {{ primary.label }}
                </Link>
                <button v-if="canRetry" type="button" class="text-accent-600 hover:text-accent-700 font-medium" @click="$inertia.reload()">
                    Try again
                </button>
                <Link :href="route('legal.contact')" class="text-accent-600 hover:text-accent-700 font-medium">
                    Contact support
                </Link>
            </div>

            <p v-if="status === 403 && !signedIn" class="text-xs text-gray-400 dark:text-gray-500 mt-8">
                If you have an account, signing in may give you access to this page.
            </p>

            <!-- What actually broke. Rendered only when the server decided this
                 viewer may see it, which means super admins and nobody else. -->
            <div v-if="diagnostic" class="mt-10 text-left border border-amber-200 dark:border-amber-800 rounded-lg overflow-hidden">
                <button type="button" class="w-full flex items-center justify-between gap-3 px-4 py-2.5 bg-amber-50 dark:bg-amber-900/30 text-left"
                    @click="showDetail = !showDetail">
                    <span class="text-xs font-semibold uppercase tracking-wide text-amber-800 dark:text-amber-200">
                        What went wrong — administrators only
                    </span>
                    <span class="text-xs text-amber-700 dark:text-amber-300 shrink-0">{{ showDetail ? 'Hide' : 'Show' }}</span>
                </button>

                <div class="px-4 py-3 bg-white dark:bg-gray-800">
                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ diagnostic.summary }}</p>

                    <div v-if="showDetail" class="mt-4 space-y-3">
                        <div>
                            <div class="text-[0.65rem] uppercase tracking-wide text-gray-400">Reported as</div>
                            <div class="font-mono text-xs text-gray-900 dark:text-gray-100 break-words">{{ diagnostic.type }}</div>
                        </div>
                        <div>
                            <div class="text-[0.65rem] uppercase tracking-wide text-gray-400">Detail</div>
                            <div class="font-mono text-xs text-gray-900 dark:text-gray-100 break-words">{{ diagnostic.message }}</div>
                        </div>
                        <div v-if="diagnostic.location">
                            <div class="text-[0.65rem] uppercase tracking-wide text-gray-400">Where</div>
                            <div class="font-mono text-xs text-gray-900 dark:text-gray-100 break-words">{{ diagnostic.location }}</div>
                        </div>
                        <div v-if="diagnostic.frames?.length">
                            <div class="text-[0.65rem] uppercase tracking-wide text-gray-400">Called from</div>
                            <ol class="font-mono text-[0.7rem] text-gray-500 dark:text-gray-400 list-decimal list-inside space-y-0.5 mt-1">
                                <li v-for="(frame, i) in diagnostic.frames" :key="i" class="break-words">{{ frame }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
