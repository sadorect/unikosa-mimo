<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import SeoHead from '@/Components/SeoHead.vue';
import Footer from '@/Components/Footer.vue';
import { computed, ref } from 'vue';

defineProps({
    stats: Object,
});

const mobileOpen = ref(false);

const page = usePage();
const siteName = computed(() => page.props.settings?.site_name || 'UNIKOSA');
const heroDescription = `One platform for every ${siteName.value} alumnus — connect, collaborate, celebrate, and give back to the global alumni community.`;

const organizationJsonLd = computed(() => {
    const social = page.props.settings?.social || {};
    const sameAs = Object.values(social).filter(Boolean);
    return {
        '@context': 'https://schema.org',
        '@type': 'Organization',
        name: siteName.value,
        url: window.location.origin,
        ...(page.props.settings?.logo_url ? { logo: page.props.settings.logo_url } : {}),
        ...(sameAs.length ? { sameAs } : {}),
        description: heroDescription,
    };
});

const features = [
    {
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        title: 'Member Directory',
        desc: 'Find and connect with fellow alumni across the globe. Filter by graduating set, chapter, profession, or location.',
        color: 'amber',
    },
    {
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        title: 'Events',
        desc: 'Stay connected at reunions, webinars, and chapter meetups. RSVP, buy tickets, and watch livestreams — all in one place.',
        color: 'blue',
    },
    {
        icon: 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z',
        title: 'Forum & Community',
        desc: 'Discuss shared interests, share condolences and prayers, and celebrate each other in set and chapter groups.',
        color: 'green',
    },
    {
        icon: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        title: 'Jobs & Careers',
        desc: 'Post opportunities, get referred, or offer your expertise via "Hire an Alumnus." The alumni network is your career edge.',
        color: 'purple',
    },
    {
        icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        title: 'Photo Gallery',
        desc: 'Relive reunion memories and share moments from chapter events in organized, secure albums — powered by cloud storage.',
        color: 'rose',
    },
    {
        icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        title: 'Dues & Campaigns',
        desc: 'Pay dues, contribute to fundraising drives, and track your financial history. Full transparency reports published for all members.',
        color: 'teal',
    },
];

const colorMap = {
    amber: { bg: 'bg-accent-50 dark:bg-accent-900/20', icon: 'text-accent-500', border: 'border-accent-200 dark:border-accent-800' },
    blue:  { bg: 'bg-blue-50 dark:bg-blue-900/20',   icon: 'text-blue-500',  border: 'border-blue-200 dark:border-blue-800' },
    green: { bg: 'bg-green-50 dark:bg-green-900/20', icon: 'text-green-500', border: 'border-green-200 dark:border-green-800' },
    purple:{ bg: 'bg-purple-50 dark:bg-purple-900/20',icon:'text-purple-500',border: 'border-purple-200 dark:border-purple-800' },
    rose:  { bg: 'bg-rose-50 dark:bg-rose-900/20',   icon: 'text-rose-500',  border: 'border-rose-200 dark:border-rose-800' },
    teal:  { bg: 'bg-teal-50 dark:bg-teal-900/20',   icon: 'text-teal-500',  border: 'border-teal-200 dark:border-teal-800' },
};
</script>

<template>
    <SeoHead
        :title="`${siteName} — Global Alumni Network`"
        :description="heroDescription"
        :json-ld="organizationJsonLd"
    />

    <div class="min-h-screen bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">

        <!-- ── Navigation ── -->
        <nav class="fixed top-0 inset-x-0 z-50 bg-white/90 dark:bg-gray-900/90 backdrop-blur border-b border-gray-100 dark:border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <Link href="/" class="text-2xl font-bold text-accent-500 tracking-wide">
                    {{ $page.props.settings?.site_name || 'UNIKOSA' }}
                </Link>

                <!-- Desktop nav -->
                <div class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-600 dark:text-gray-300">
                    <a href="#features" class="hover:text-accent-500 transition">Features</a>
                    <a href="#about" class="hover:text-accent-500 transition">About</a>
                    <Link :href="route('blog.index')" class="hover:text-accent-500 transition">Blog</Link>
                    <Link :href="route('transparency.index')" class="hover:text-accent-500 transition">Transparency</Link>
                </div>

                <div class="hidden md:flex items-center gap-3">
                    <template v-if="$page.props.auth.user">
                        <Link :href="route('dashboard')"
                            class="bg-accent-500 hover:bg-accent-600 text-white px-5 py-2 rounded-full text-sm font-semibold transition shadow-sm">
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')"
                            class="text-gray-600 dark:text-gray-300 hover:text-accent-500 transition text-sm font-medium px-4 py-2">
                            Sign In
                        </Link>
                        <Link :href="route('register')"
                            class="bg-accent-500 hover:bg-accent-600 text-white px-5 py-2 rounded-full text-sm font-semibold transition shadow-sm">
                            Join the Network
                        </Link>
                    </template>
                </div>

                <!-- Mobile menu toggle -->
                <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 rounded-md text-gray-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Mobile menu -->
            <div v-if="mobileOpen" class="md:hidden bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 px-4 py-4 space-y-3">
                <a href="#features" @click="mobileOpen=false" class="block text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-accent-500">Features</a>
                <a href="#about" @click="mobileOpen=false" class="block text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-accent-500">About</a>
                <Link :href="route('transparency.index')" class="block text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-accent-500">Transparency</Link>
                <div class="pt-2 flex gap-3">
                    <Link v-if="!$page.props.auth.user" :href="route('login')" class="flex-1 text-center border border-accent-500 text-accent-600 py-2 rounded-full text-sm font-semibold">Sign In</Link>
                    <Link v-if="!$page.props.auth.user" :href="route('register')" class="flex-1 text-center bg-accent-500 text-white py-2 rounded-full text-sm font-semibold">Join Now</Link>
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="flex-1 text-center bg-accent-500 text-white py-2 rounded-full text-sm font-semibold">Dashboard</Link>
                </div>
            </div>
        </nav>

        <!-- ── Hero ── -->
        <section class="relative pt-24 pb-20 overflow-hidden">
            <!-- Background gradient -->
            <div class="absolute inset-0 bg-linear-to-br from-accent-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 pointer-events-none"></div>
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-accent-200/30 dark:bg-accent-900/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-72 h-72 bg-blue-200/30 dark:bg-blue-900/20 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col lg:flex-row items-center gap-12">
                <!-- Text -->
                <div class="flex-1 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 bg-accent-100 dark:bg-accent-900/40 text-accent-700 dark:text-accent-400 text-xs font-semibold px-3 py-1 rounded-full mb-6">
                        <span class="w-1.5 h-1.5 bg-accent-500 rounded-full animate-pulse"></span>
                        Global Alumni Network
                    </div>
                    <h1 class="text-5xl lg:text-6xl font-extrabold text-gray-900 dark:text-white leading-tight mb-6">
                        Connecting<br/>
                        <span class="text-accent-500">{{ $page.props.settings?.site_name || 'UNIKOSA' }}</span><br/>
                        Alumni Worldwide
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-8 max-w-xl mx-auto lg:mx-0">
                        One platform for every alumnus — whether you're in Lagos, London, or Los Angeles.
                        Connect, collaborate, celebrate, and give back to the community that shaped you.
                    </p>
                    <div class="flex flex-wrap gap-4 justify-center lg:justify-start">
                        <template v-if="$page.props.auth.user">
                            <Link :href="route('dashboard')"
                                class="bg-accent-500 hover:bg-accent-600 text-white font-bold px-8 py-3 rounded-full shadow-lg transition text-base">
                                Go to Dashboard
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="route('register')"
                                class="bg-accent-500 hover:bg-accent-600 text-white font-bold px-8 py-3 rounded-full shadow-lg transition text-base">
                                Join the Network
                            </Link>
                            <Link :href="route('login')"
                                class="border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-accent-500 hover:text-accent-500 font-bold px-8 py-3 rounded-full transition text-base">
                                Sign In
                            </Link>
                        </template>
                        <Link :href="route('claim-profile.show')"
                            class="text-gray-500 dark:text-gray-400 underline underline-offset-2 text-sm self-center hover:text-accent-500 transition">
                            Already a member? Claim your profile
                        </Link>
                    </div>
                </div>

                <!-- Stats card -->
                <div class="shrink-0 w-full lg:w-80">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-6 grid grid-cols-2 gap-4">
                        <div class="text-center p-4 bg-accent-50 dark:bg-accent-900/20 rounded-xl">
                            <div class="text-3xl font-extrabold text-accent-600 dark:text-accent-400">
                                {{ (stats?.members || 0).toLocaleString() }}+
                            </div>
                            <div class="text-xs text-gray-500 mt-1 font-medium">Verified Members</div>
                        </div>
                        <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <div class="text-3xl font-extrabold text-blue-600 dark:text-blue-400">
                                {{ stats?.chapters || 0 }}
                            </div>
                            <div class="text-xs text-gray-500 mt-1 font-medium">Global Chapters</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                            <div class="text-3xl font-extrabold text-green-600 dark:text-green-400">
                                {{ stats?.sets || 0 }}
                            </div>
                            <div class="text-xs text-gray-500 mt-1 font-medium">Graduating Sets</div>
                        </div>
                        <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                            <div class="text-3xl font-extrabold text-purple-600 dark:text-purple-400">
                                {{ stats?.events || 0 }}
                            </div>
                            <div class="text-xs text-gray-500 mt-1 font-medium">Events Hosted</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Features ── -->
        <section id="features" class="py-20 bg-gray-50 dark:bg-gray-800/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-14">
                    <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                        Everything the network needs
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 max-w-xl mx-auto">
                        Built specifically for alumni associations — from dues to discussions, events to employment.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="f in features" :key="f.title"
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 border hover:shadow-lg transition-shadow"
                        :class="colorMap[f.color].border">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4"
                            :class="colorMap[f.color].bg">
                            <svg class="w-6 h-6" :class="colorMap[f.color].icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="f.icon" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ f.title }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">{{ f.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── About / Mission ── -->
        <section id="about" class="py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <!-- Left: decorative grid of accent blocks -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2 bg-accent-500 rounded-2xl h-40 flex items-end p-4">
                            <span class="text-white font-bold text-lg">One School, Many Nations</span>
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl h-40 flex items-center justify-center">
                            <svg class="w-10 h-10 text-accent-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl h-40 flex items-center justify-center">
                            <svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <div class="col-span-2 bg-gray-900 dark:bg-gray-700 rounded-2xl h-40 flex items-end p-4">
                            <span class="text-accent-400 font-bold text-lg">Organized. Transparent. Connected.</span>
                        </div>
                    </div>

                    <!-- Right: text -->
                    <div>
                        <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-6">
                            Our mission is simple:<br/>
                            <span class="text-accent-500">keep the family together.</span>
                        </h2>
                        <div class="space-y-4 text-gray-600 dark:text-gray-400">
                            <p>
                                UNIKOSA alumni are spread across Nigeria, the UK, the US, Canada, and beyond.
                                This platform ensures that no matter where life has taken you, the bonds formed
                                in school remain strong.
                            </p>
                            <p>
                                We organize by <strong class="text-gray-800 dark:text-gray-200">graduating sets</strong> and
                                <strong class="text-gray-800 dark:text-gray-200">diaspora chapters</strong>, so announcements,
                                dues, and events reach exactly the right people — not everyone's inbox at once.
                            </p>
                            <p>
                                All financial activities are published in our
                                <Link :href="route('transparency.index')" class="text-accent-600 hover:underline">
                                    public transparency reports
                                </Link>
                                so every member knows exactly how contributions are used.
                            </p>
                        </div>
                        <div class="mt-8 flex gap-4">
                            <Link :href="route('register')"
                                class="bg-accent-500 hover:bg-accent-600 text-white font-semibold px-6 py-3 rounded-full transition">
                                Join Today
                            </Link>
                            <Link :href="route('transparency.index')"
                                class="border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-accent-500 hover:text-accent-500 font-semibold px-6 py-3 rounded-full transition">
                                View Reports
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── How it works ── -->
        <section class="py-20 bg-gray-50 dark:bg-gray-800/50">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Get started in 3 steps</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-12">It takes less than two minutes to join.</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div v-for="(step, i) in [
                        { title: 'Register or Claim', desc: 'Create a new account or claim a pre-loaded alumni profile using your verification code.' },
                        { title: 'Get Verified', desc: 'Your set rep or an admin reviews your profile and approves your membership.' },
                        { title: 'Join the Community', desc: 'Access the full platform — directory, events, forum, jobs, campaigns and more.' }
                    ]" :key="i" class="relative">
                        <div class="w-10 h-10 rounded-full bg-accent-500 text-white font-bold flex items-center justify-center mx-auto mb-4 text-lg">
                            {{ i + 1 }}
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ step.title }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ step.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── CTA Banner ── -->
        <section class="py-16 bg-accent-500">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl lg:text-4xl font-extrabold text-white mb-4">
                    Ready to reconnect with your alma mater?
                </h2>
                <p class="text-accent-100 mb-8 text-lg">
                    Thousands of alumni are already using the platform. Your network is waiting.
                </p>
                <div class="flex flex-wrap gap-4 justify-center">
                    <template v-if="$page.props.auth.user">
                        <Link :href="route('dashboard')"
                            class="bg-white text-accent-600 hover:bg-accent-50 font-bold px-8 py-3 rounded-full transition shadow-lg text-base">
                            Go to Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('register')"
                            class="bg-white text-accent-600 hover:bg-accent-50 font-bold px-8 py-3 rounded-full transition shadow-lg text-base">
                            Create Free Account
                        </Link>
                        <Link :href="route('claim-profile.show')"
                            class="border-2 border-white text-white hover:bg-accent-600 font-bold px-8 py-3 rounded-full transition text-base">
                            Claim Existing Profile
                        </Link>
                    </template>
                </div>
            </div>
        </section>

        <Footer />
    </div>
</template>
