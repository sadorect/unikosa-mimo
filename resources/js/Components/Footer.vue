<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const siteName = computed(() => page.props.settings?.site_name || 'UNIKOSA');
const social = computed(() => page.props.settings?.social || {});

const socialIcons = {
    facebook: 'M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z',
    twitter: 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
    instagram: 'M12 2c-2.72 0-3.06.012-4.123.06-1.064.049-1.79.218-2.427.465a4.9 4.9 0 00-1.771 1.153A4.9 4.9 0 002.526 5.45c-.247.637-.416 1.363-.465 2.427C2.012 8.94 2 9.28 2 12s.012 3.06.06 4.123c.049 1.064.218 1.79.465 2.427a4.9 4.9 0 001.153 1.771 4.9 4.9 0 001.771 1.153c.637.247 1.363.416 2.427.465C8.94 21.988 9.28 22 12 22s3.06-.012 4.123-.06c1.064-.049 1.79-.218 2.427-.465a4.9 4.9 0 001.771-1.153 4.9 4.9 0 001.153-1.771c.247-.637.416-1.363.465-2.427.048-1.064.06-1.403.06-4.123s-.012-3.06-.06-4.123c-.049-1.064-.218-1.79-.465-2.427a4.9 4.9 0 00-1.153-1.771A4.9 4.9 0 0018.55 2.526c-.637-.247-1.363-.416-2.427-.465C15.06 2.012 14.72 2 12 2zm0 1.802c2.67 0 2.987.01 4.042.059.976.045 1.505.207 1.858.344.467.182.8.399 1.15.748.35.35.566.683.748 1.15.137.353.3.882.344 1.858.048 1.055.058 1.372.058 4.042s-.01 2.987-.058 4.042c-.045.976-.207 1.505-.344 1.858a3.1 3.1 0 01-.748 1.15 3.1 3.1 0 01-1.15.748c-.353.137-.882.3-1.858.344-1.055.048-1.372.058-4.042.058s-2.987-.01-4.042-.058c-.976-.045-1.505-.207-1.858-.344a3.1 3.1 0 01-1.15-.748 3.1 3.1 0 01-.748-1.15c-.137-.353-.3-.882-.344-1.858-.048-1.055-.058-1.372-.058-4.042s.01-2.987.058-4.042c.045-.976.207-1.505.344-1.858.182-.467.399-.8.748-1.15.35-.35.683-.566 1.15-.748.353-.137.882-.3 1.858-.344 1.055-.048 1.372-.059 4.042-.059zM12 7a5 5 0 100 10 5 5 0 000-10zm0 8.25a3.25 3.25 0 110-6.5 3.25 3.25 0 010 6.5zm5.2-8.45a1.17 1.17 0 100-2.34 1.17 1.17 0 000 2.34z',
    linkedin: 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 110-4.124 2.062 2.062 0 010 4.124zM7.114 20.452H3.558V9h3.556v11.452z',
};
const socialLinks = computed(() => Object.entries(social.value).filter(([, url]) => url));
</script>

<template>
    <footer class="bg-gray-900 text-gray-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-8 mb-8">
                <div class="md:col-span-2">
                    <Link href="/" class="text-2xl font-bold text-accent-500 mb-3 inline-block">{{ siteName }}</Link>
                    <p class="text-sm leading-relaxed">
                        The official alumni platform for the {{ siteName }} community —
                        connecting graduates from every set and every corner of the world.
                    </p>
                    <div v-if="socialLinks.length" class="flex items-center gap-3 mt-4">
                        <a v-for="[platform, url] in socialLinks" :key="platform" :href="url" target="_blank" rel="noopener noreferrer"
                            :aria-label="platform"
                            class="w-9 h-9 rounded-full bg-gray-800 hover:bg-accent-500 flex items-center justify-center transition text-gray-300 hover:text-white">
                            <svg v-if="socialIcons[platform]" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path :d="socialIcons[platform]" />
                            </svg>
                        </a>
                    </div>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-3 text-sm">Platform</h4>
                    <ul class="space-y-2 text-sm">
                        <li><Link :href="route('register')" class="hover:text-accent-400 transition">Join the Network</Link></li>
                        <li><Link :href="route('login')" class="hover:text-accent-400 transition">Sign In</Link></li>
                        <li><Link :href="route('claim-profile.show')" class="hover:text-accent-400 transition">Claim Profile</Link></li>
                        <li><Link :href="route('transparency.index')" class="hover:text-accent-400 transition">Transparency</Link></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-3 text-sm">Community</h4>
                    <ul class="space-y-2 text-sm">
                        <li><Link :href="route('blog.index')" class="hover:text-accent-400 transition">Blog</Link></li>
                        <li><Link :href="route('directory')" class="hover:text-accent-400 transition">Directory</Link></li>
                        <li><Link :href="route('events.index')" class="hover:text-accent-400 transition">Events</Link></li>
                        <li><Link :href="route('forum.index')" class="hover:text-accent-400 transition">Forum</Link></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-3 text-sm">Legal</h4>
                    <ul class="space-y-2 text-sm">
                        <li><Link :href="route('legal.privacy')" class="hover:text-accent-400 transition">Privacy Policy</Link></li>
                        <li><Link :href="route('legal.terms')" class="hover:text-accent-400 transition">Terms of Service</Link></li>
                        <li><Link :href="route('legal.contact')" class="hover:text-accent-400 transition">Contact Us</Link></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-6 text-center text-xs">
                &copy; {{ new Date().getFullYear() }} {{ siteName }} Alumni Association. All rights reserved.
            </div>
        </div>
    </footer>
</template>
