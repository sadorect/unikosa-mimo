<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    description: {
        type: String,
        default: 'UNIKOSA is the official global alumni network — connecting graduates for events, mentorship, jobs, and giving back.',
    },
    image: { type: String, default: null },
    type: { type: String, default: 'website' },
    noindex: { type: Boolean, default: false },
    publishedTime: { type: String, default: null },
    modifiedTime: { type: String, default: null },
    author: { type: String, default: null },
    jsonLd: { type: [Object, Array], default: null },
});

const page = usePage();
const siteName = computed(() => page.props.settings?.site_name || 'UNIKOSA');
const fullTitle = computed(() => `${props.title} · ${siteName.value}`);

const canonicalUrl = computed(() => `${window.location.origin}${page.url.split('?')[0]}`);

const resolvedImage = computed(() => {
    const src = props.image || page.props.settings?.logo_url;
    if (!src) return null;
    return src.startsWith('http') ? src : `${window.location.origin}${src}`;
});
</script>

<template>
    <Head>
        <title>{{ fullTitle }}</title>
        <meta name="description" :content="description" />
        <meta name="robots" :content="noindex ? 'noindex, nofollow' : 'index, follow'" />
        <link rel="canonical" :href="canonicalUrl" />

        <meta property="og:type" :content="type" />
        <meta property="og:title" :content="title" />
        <meta property="og:description" :content="description" />
        <meta property="og:url" :content="canonicalUrl" />
        <meta property="og:site_name" :content="siteName" />
        <meta v-if="resolvedImage" property="og:image" :content="resolvedImage" />
        <meta v-if="publishedTime" property="article:published_time" :content="publishedTime" />
        <meta v-if="modifiedTime" property="article:modified_time" :content="modifiedTime" />
        <meta v-if="author" property="article:author" :content="author" />

        <meta name="twitter:card" :content="resolvedImage ? 'summary_large_image' : 'summary'" />
        <meta name="twitter:title" :content="title" />
        <meta name="twitter:description" :content="description" />
        <meta v-if="resolvedImage" name="twitter:image" :content="resolvedImage" />

        <script v-if="jsonLd" type="application/ld+json">{{ JSON.stringify(jsonLd) }}</script>
    </Head>
</template>
