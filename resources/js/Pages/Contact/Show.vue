<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import CaptchaField from '@/Components/CaptchaField.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ intro: String, whatsappNumber: String });

const authUser = usePage().props.auth?.user;

const captcha = ref(null);
const form = useForm({
    name: authUser?.name ?? '',
    email: authUser?.email ?? '',
    subject: '',
    message: '',
    captcha_id: '',
    captcha_answer: '',
});

const whatsappHref = (number) =>
    `https://wa.me/${number.replace(/\D/g, '')}?text=${encodeURIComponent("Hi, I'd like to get in touch.")}`;

const submit = () => {
    form.post(route('contact.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('name', 'email', 'subject', 'message'),
        onError: () => captcha.value?.refresh(),
    });
};
</script>

<template>
    <PublicLayout current="contact">
        <SeoHead title="Contact Us" description="Get in touch with the UNIKOSA alumni association." />

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-10">
                <div class="lg:col-span-2">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Contact Us</h1>
                    <div v-if="intro" class="prose dark:prose-invert max-w-none text-gray-600 dark:text-gray-400" v-html="intro"></div>
                    <p v-else class="text-gray-500 dark:text-gray-400">
                        Have a question, suggestion, or need help with your account? Send us a message and a member of the team will get back to you.
                    </p>

                    <a v-if="whatsappNumber" :href="whatsappHref(whatsappNumber)" target="_blank" rel="noopener"
                        class="mt-6 inline-flex items-center gap-2 text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 text-sm font-medium">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" /><path d="M12.001 2.003c-5.514 0-9.997 4.483-9.997 9.997 0 1.762.464 3.482 1.345 4.997l-1.43 5.223 5.348-1.403c1.462.799 3.111 1.221 4.734 1.221h.004c5.514 0 9.997-4.483 9.997-9.998 0-2.671-1.04-5.183-2.929-7.072-1.889-1.89-4.4-2.965-7.072-2.965zm0 18.174h-.003c-1.475 0-2.923-.396-4.19-1.146l-.3-.178-3.173.832.847-3.095-.196-.318a8.15 8.15 0 01-1.257-4.365c0-4.508 3.669-8.176 8.177-8.176 2.184 0 4.238.851 5.783 2.397a8.126 8.126 0 012.394 5.784c0 4.508-3.669 8.265-8.082 8.265z" /></svg>
                        Chat with us on WhatsApp
                    </a>
                </div>

                <div class="lg:col-span-3">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 sm:p-8">
                        <div v-if="$page.props.flash?.success" class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 px-4 py-3 rounded-lg text-sm">
                            {{ $page.props.flash.success }}
                        </div>

                        <form @submit.prevent="submit" class="space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Your name</label>
                                    <input v-model="form.name" type="text" autocomplete="name" required
                                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent transition" />
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email address</label>
                                    <input v-model="form.email" type="email" autocomplete="email" required
                                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent transition" />
                                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input v-model="form.subject" type="text"
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent transition" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message</label>
                                <textarea v-model="form.message" rows="5" required
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent transition"></textarea>
                                <p v-if="form.errors.message" class="mt-1 text-xs text-red-500">{{ form.errors.message }}</p>
                            </div>

                            <CaptchaField ref="captcha" form="contact" v-model:id="form.captcha_id" v-model:answer="form.captcha_answer" :error="form.errors.captcha_answer" />

                            <button type="submit"
                                class="w-full bg-accent-500 hover:bg-accent-600 text-white font-semibold py-2.5 px-4 rounded-lg transition disabled:opacity-60"
                                :disabled="form.processing">
                                {{ form.processing ? 'Sending…' : 'Send Message' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
