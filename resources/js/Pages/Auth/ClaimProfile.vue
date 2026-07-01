<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    sets: Array,
});

const step = ref('search'); // search, verify, complete
const searchQuery = ref('');
const selectedSet = ref('');
const searchResults = ref([]);
const selectedUser = ref(null);
const otp = ref('');
const otpSent = ref(false);
const otpError = ref('');

const form = useForm({
    user_id: '',
    otp: '',
    password: '',
    password_confirmation: '',
});

const searchProfiles = async () => {
    try {
        const response = await fetch('/api/v1/claim-profile/search', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || '',
            },
            body: JSON.stringify({
                query: searchQuery.value,
                set_id: selectedSet.value,
            }),
        });
        const data = await response.json();
        searchResults.value = data.results || [];
    } catch (e) {
        console.error(e);
    }
};

const selectProfile = (user) => {
    selectedUser.value = user;
    form.user_id = user.id;
    step.value = 'verify';
};

const sendOtp = async () => {
    try {
        const response = await fetch('/api/v1/claim-profile/send-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || '',
            },
            body: JSON.stringify({ user_id: selectedUser.value.id }),
        });
        otpSent.value = true;
    } catch (e) {
        console.error(e);
    }
};

const verifyAndClaim = () => {
    form.otp = otp.value;
    form.post('/claim-profile/verify', {
        onSuccess: () => {
            step.value = 'complete';
        },
        onError: (errors) => {
            otpError.value = errors.otp || 'Invalid OTP';
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Claim Your Profile" />

        <div class="max-w-lg mx-auto">
            <h1 class="text-2xl font-bold text-center mb-6">Claim Your Profile</h1>
            <p class="text-gray-600 text-center mb-8">We have pre-loaded alumni profiles. Search for yours below.</p>

            <!-- Step 1: Search -->
            <div v-if="step === 'search'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search by name or email</label>
                    <input v-model="searchQuery" type="text" placeholder="Enter your name or email..."
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent-500 focus:ring-accent-500"
                        @keyup.enter="searchProfiles" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Graduating Set (optional)</label>
                    <select v-model="selectedSet" class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent-500 focus:ring-accent-500">
                        <option value="">All Sets</option>
                        <option v-for="s in sets" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <button @click="searchProfiles"
                    class="w-full bg-accent-500 hover:bg-accent-600 text-white font-bold py-2 px-4 rounded">
                    Search
                </button>

                <div v-if="searchResults.length > 0" class="mt-6 space-y-3">
                    <h3 class="font-medium text-gray-700">Select your profile:</h3>
                    <div v-for="result in searchResults" :key="result.id"
                        @click="selectProfile(result)"
                        class="border rounded-lg p-4 cursor-pointer hover:border-accent-500 hover:bg-accent-50 transition">
                        <div class="font-semibold">{{ result.name }}</div>
                        <div class="text-sm text-gray-500">{{ result.email }}</div>
                        <div v-if="result.graduating_set" class="text-xs text-gray-400">{{ result.graduating_set.name }}</div>
                    </div>
                </div>

                <div v-if="searchResults.length === 0 && searchQuery.length > 2" class="mt-4 text-center text-gray-500">
                    No profiles found. <Link :href="route('register')" class="text-accent-600 hover:text-accent-700">Register as a new member</Link>
                </div>
            </div>

            <!-- Step 2: Verify -->
            <div v-if="step === 'verify'" class="space-y-4">
                <div class="bg-accent-50 border border-accent-200 rounded-lg p-4">
                    <div class="font-semibold">{{ selectedUser.name }}</div>
                    <div class="text-sm text-gray-500">{{ selectedUser.email }}</div>
                </div>

                <div v-if="!otpSent">
                    <button @click="sendOtp"
                        class="w-full bg-accent-500 hover:bg-accent-600 text-white font-bold py-2 px-4 rounded">
                        Send Verification Code
                    </button>
                </div>

                <div v-else class="space-y-4">
                    <p class="text-sm text-gray-600">A verification code has been sent to {{ selectedUser.email }}</p>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Verification Code</label>
                        <input v-model="otp" type="text" maxlength="6" placeholder="Enter 6-digit code"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent-500 focus:ring-accent-500 text-center text-2xl tracking-widest" />
                        <div v-if="otpError" class="text-red-500 text-sm mt-1">{{ otpError }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Set Password</label>
                        <input v-model="form.password" type="password" placeholder="Choose a password"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent-500 focus:ring-accent-500" />
                        <div v-if="form.errors.password" class="text-red-500 text-sm mt-1">{{ form.errors.password }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <input v-model="form.password_confirmation" type="password" placeholder="Confirm password"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent-500 focus:ring-accent-500" />
                    </div>
                    <button @click="verifyAndClaim"
                        :disabled="form.processing"
                        class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded disabled:opacity-50">
                        {{ form.processing ? 'Claiming...' : 'Claim Profile' }}
                    </button>
                </div>

                <button @click="step = 'search'" class="text-sm text-gray-500 hover:text-gray-700">← Back to search</button>
            </div>

            <!-- Step 3: Complete -->
            <div v-if="step === 'complete'" class="text-center py-8">
                <div class="text-6xl mb-4">🎉</div>
                <h2 class="text-xl font-bold mb-2">Profile Claimed!</h2>
                <p class="text-gray-600 mb-6">Your profile has been verified and activated.</p>
                <Link :href="route('dashboard')"
                    class="bg-accent-500 hover:bg-accent-600 text-white font-bold py-2 px-6 rounded inline-block">
                    Go to Dashboard
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
