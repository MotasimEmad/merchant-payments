<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

defineProps({
    merchant: Object,
    services: {
        type: Array,
        default: () => [],
    },
});

const createForm = useForm({
    name: '',
    description: '',
    price: '',
    currency: 'USD',
});

const profileForm = useForm({
    business_name: '',
});

function submitProfile() {
    profileForm.post(route('pay.merchant.profile.store'), { preserveScroll: true });
}

function submitCreate() {
    createForm.post(route('pay.merchant.services.store'), { preserveScroll: true });
}

function formatMoney(minor, currency) {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: currency || 'USD',
    }).format(Number(minor) / 100);
}

function remove(publicId) {
    if (confirm('Mark this service as inactive?')) {
        router.delete(route('pay.merchant.services.destroy', publicId), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="My services" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-900">My services</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl space-y-8 sm:px-6 lg:px-8">
                <div
                    v-if="!merchant"
                    class="space-y-6"
                >
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        Create a merchant profile to list services in your public shop.
                    </div>
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-gray-900">Merchant profile</h3>
                        <p class="mt-1 text-xs text-gray-500">Business name appears on your shop page.</p>
                        <form class="mt-4 space-y-3" @submit.prevent="submitProfile">
                            <div>
                                <InputLabel for="business_name" value="Business name" />
                                <TextInput
                                    id="business_name"
                                    v-model="profileForm.business_name"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError class="mt-1" :message="profileForm.errors.business_name" />
                            </div>
                            <PrimaryButton :disabled="profileForm.processing">Create profile</PrimaryButton>
                        </form>
                    </div>
                </div>

                <template v-else>
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-gray-900">Add a service</h3>
                        <p class="mt-1 text-xs text-gray-500">Shown to customers in your public shop (active only).</p>
                        <form class="mt-4 space-y-3" @submit.prevent="submitCreate">
                            <div>
                                <InputLabel for="name" value="Name" />
                                <TextInput id="name" v-model="createForm.name" class="mt-1 block w-full" required />
                                <InputError class="mt-1" :message="createForm.errors.name" />
                            </div>
                            <div>
                                <InputLabel for="description" value="Description (optional)" />
                                <textarea
                                    id="description"
                                    v-model="createForm.description"
                                    rows="2"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm"
                                />
                                <InputError class="mt-1" :message="createForm.errors.description" />
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="price" value="Price (USD, e.g. 12.50)" />
                                    <TextInput
                                        id="price"
                                        v-model="createForm.price"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError class="mt-1" :message="createForm.errors.price" />
                                </div>
                                <div>
                                    <InputLabel for="currency" value="Currency" />
                                    <TextInput
                                        id="currency"
                                        v-model="createForm.currency"
                                        maxlength="3"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError class="mt-1" :message="createForm.errors.currency" />
                                </div>
                            </div>
                            <PrimaryButton :disabled="createForm.processing">Add service</PrimaryButton>
                        </form>
                    </div>

                    <div>
                        <h3 class="mb-3 text-sm font-semibold text-gray-900">Your catalog</h3>
                        <ul
                            v-if="services.length"
                            class="divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 bg-white"
                        >
                            <li
                                v-for="s in services"
                                :key="s.public_id"
                                class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ s.name }}
                                        <span
                                            v-if="s.status !== 'active'"
                                            class="ml-2 text-xs font-normal text-amber-700"
                                        >inactive</span>
                                    </p>
                                    <p v-if="s.description" class="text-sm text-gray-600">{{ s.description }}</p>
                                    <p class="text-sm text-gray-900">
                                        {{ formatMoney(s.price_minor, s.currency) }}
                                    </p>
                                </div>
                                <button
                                    v-if="s.status === 'active'"
                                    type="button"
                                    class="text-sm text-rose-600 hover:text-rose-800"
                                    @click="remove(s.public_id)"
                                >Deactivate</button>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-500">No services yet.</p>
                    </div>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
