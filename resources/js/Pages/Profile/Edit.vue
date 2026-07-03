<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ user: Object, sets: Array, chapters: Array, privacy: Object });

// date-cast fields arrive as ISO datetimes; <input type=date> needs YYYY-MM-DD.
const dateOnly = (v) => (v ? String(v).slice(0, 10) : '');

const profileForm = useForm({
    name: props.user.name || '',
    email: props.user.email || '',
    phone: props.user.phone || '',
    date_of_birth: dateOnly(props.user.date_of_birth),
    wedding_anniversary: dateOnly(props.user.wedding_anniversary),
    gender: props.user.gender || '',
    graduating_set_id: props.user.graduating_set_id || '',
    chapter_id: props.user.chapter_id || '',
    country: props.user.country || '',
    city: props.user.city || '',
    profession: props.user.profession || '',
    bio: props.user.bio || '',
    skills: (props.user.skills || []).join(', '),
    social_links: {
        linkedin: props.user.social_links?.linkedin || '',
        twitter: props.user.social_links?.twitter || '',
        facebook: props.user.social_links?.facebook || '',
        instagram: props.user.social_links?.instagram || '',
    },
    avatar: null,
});

const avatarPreview = ref(props.user.avatar || null);

const onAvatarChange = (event) => {
    const file = event.target.files[0];
    profileForm.avatar = file || null;
    avatarPreview.value = file ? URL.createObjectURL(file) : props.user.avatar || null;
};

const submitProfile = () => {
    // Files can't be sent over a real PUT (PHP doesn't parse multipart PUT bodies),
    // so submit as POST with method spoofing — works with or without an avatar.
    profileForm
        .transform((data) => ({
            ...data,
            _method: 'put',
            skills: data.skills.split(',').map((s) => s.trim()).filter(Boolean),
            social_links: Object.fromEntries(Object.entries(data.social_links).filter(([, v]) => v)),
        }))
        .post(route('profile.update'), { preserveScroll: true, forceFormData: true });
};

const privacyFields = [
    { key: 'show_email', label: 'Show my email address to other members' },
    { key: 'show_phone', label: 'Show my phone number to other members' },
    { key: 'show_dob', label: 'Show my date of birth to other members' },
    { key: 'show_profession', label: 'Show my profession and bio to other members' },
    { key: 'show_skills', label: 'Show my skills to other members' },
    { key: 'show_social_links', label: 'Show my social links to other members' },
    { key: 'show_location', label: 'Show my location (city/country) to other members' },
];

const privacyForm = useForm({ ...props.privacy });

const submitPrivacy = () => {
    privacyForm.put(route('profile.privacy.update'), { preserveScroll: true });
};

const inputClass = 'w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent transition';
const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Edit Profile" />
        <template #header><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Edit Profile</h2></template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div v-if="$page.props.flash?.success" class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 text-sm rounded-lg px-4 py-3">
                    {{ $page.props.flash.success }}
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8">
                    <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-6">Basic Information</h3>

                    <form @submit.prevent="submitProfile" class="space-y-5">
                        <div class="flex items-center gap-4">
                            <img v-if="avatarPreview" :src="avatarPreview" class="w-16 h-16 rounded-full object-cover" />
                            <div v-else class="w-16 h-16 rounded-full bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center text-accent-600 font-bold text-xl">
                                {{ profileForm.name.charAt(0) }}
                            </div>
                            <div>
                                <label :class="labelClass">Photo</label>
                                <input type="file" accept="image/*" @change="onAvatarChange"
                                    class="text-sm text-gray-600 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-accent-50 dark:file:bg-accent-900/20 file:text-accent-600 file:text-sm file:font-medium hover:file:bg-accent-100" />
                                <p v-if="profileForm.errors.avatar" class="mt-1 text-xs text-red-500">{{ profileForm.errors.avatar }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label :class="labelClass">Name</label>
                                <input v-model="profileForm.name" type="text" :class="inputClass" required />
                                <p v-if="profileForm.errors.name" class="mt-1 text-xs text-red-500">{{ profileForm.errors.name }}</p>
                            </div>
                            <div>
                                <label :class="labelClass">Email</label>
                                <input v-model="profileForm.email" type="email" :class="inputClass" required />
                                <p v-if="profileForm.errors.email" class="mt-1 text-xs text-red-500">{{ profileForm.errors.email }}</p>
                            </div>
                            <div>
                                <label :class="labelClass">Phone</label>
                                <input v-model="profileForm.phone" type="text" :class="inputClass" />
                            </div>
                            <div>
                                <label :class="labelClass">Date of Birth</label>
                                <input v-model="profileForm.date_of_birth" type="date" :class="inputClass" />
                                <p class="mt-1 text-xs text-gray-400">Shown to members only if you enable it under Privacy.</p>
                            </div>
                            <div>
                                <label :class="labelClass">Wedding Anniversary</label>
                                <input v-model="profileForm.wedding_anniversary" type="date" :class="inputClass" />
                                <p class="mt-1 text-xs text-gray-400">Used to celebrate you in your set &amp; chapter.</p>
                            </div>
                            <div>
                                <label :class="labelClass">Gender</label>
                                <select v-model="profileForm.gender" :class="inputClass">
                                    <option value="">Prefer not to say</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label :class="labelClass">Profession</label>
                                <input v-model="profileForm.profession" type="text" :class="inputClass" />
                            </div>
                            <div>
                                <label :class="labelClass">Graduating Set</label>
                                <select v-model="profileForm.graduating_set_id" :class="inputClass">
                                    <option value="">Not set</option>
                                    <option v-for="set in sets" :key="set.id" :value="set.id">{{ set.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label :class="labelClass">Chapter</label>
                                <select v-model="profileForm.chapter_id" :class="inputClass">
                                    <option value="">Not set</option>
                                    <option v-for="chapter in chapters" :key="chapter.id" :value="chapter.id">{{ chapter.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label :class="labelClass">Country</label>
                                <input v-model="profileForm.country" type="text" :class="inputClass" />
                            </div>
                            <div>
                                <label :class="labelClass">City</label>
                                <input v-model="profileForm.city" type="text" :class="inputClass" />
                            </div>
                        </div>

                        <div>
                            <label :class="labelClass">Bio</label>
                            <textarea v-model="profileForm.bio" rows="4" :class="inputClass"></textarea>
                        </div>

                        <div>
                            <label :class="labelClass">Skills (comma-separated)</label>
                            <input v-model="profileForm.skills" type="text" :class="inputClass" placeholder="e.g. Product Management, Public Speaking, Python" />
                        </div>

                        <div>
                            <label :class="labelClass + ' mb-2'">Social Links</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <input v-model="profileForm.social_links.linkedin" type="url" :class="inputClass" placeholder="LinkedIn URL" />
                                <input v-model="profileForm.social_links.twitter" type="url" :class="inputClass" placeholder="X / Twitter URL" />
                                <input v-model="profileForm.social_links.facebook" type="url" :class="inputClass" placeholder="Facebook URL" />
                                <input v-model="profileForm.social_links.instagram" type="url" :class="inputClass" placeholder="Instagram URL" />
                            </div>
                        </div>

                        <button type="submit"
                            class="bg-accent-500 hover:bg-accent-600 text-white font-semibold py-2 px-5 rounded-lg transition disabled:opacity-60"
                            :disabled="profileForm.processing">
                            {{ profileForm.processing ? 'Saving…' : 'Save Profile' }}
                        </button>
                    </form>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8">
                    <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-1">Privacy</h3>
                    <p class="text-sm text-gray-500 mb-6">Control what other members can see on your profile.</p>

                    <form @submit.prevent="submitPrivacy" class="space-y-3">
                        <label v-for="field in privacyFields" :key="field.key" class="flex items-center gap-3">
                            <input type="checkbox" v-model="privacyForm[field.key]"
                                class="rounded border-gray-300 dark:border-gray-600 text-accent-500 focus:ring-accent-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ field.label }}</span>
                        </label>

                        <div class="pt-3 mt-3 border-t border-gray-100 dark:border-gray-700">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Notifications</p>
                            <label class="flex items-center gap-3">
                                <input type="checkbox" v-model="privacyForm.notify_email_messages"
                                    class="rounded border-gray-300 dark:border-gray-600 text-accent-500 focus:ring-accent-500" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">Email me when I receive a new direct message</span>
                            </label>
                            <p class="text-xs text-gray-400 mt-1 ml-7">You'll always see new messages via the notification bell regardless of this setting.</p>
                        </div>

                        <button type="submit"
                            class="mt-4 bg-accent-500 hover:bg-accent-600 text-white font-semibold py-2 px-5 rounded-lg transition disabled:opacity-60"
                            :disabled="privacyForm.processing">
                            {{ privacyForm.processing ? 'Saving…' : 'Save Privacy Settings' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
