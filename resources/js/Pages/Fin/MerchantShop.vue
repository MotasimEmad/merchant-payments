<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    merchant: Object,
    services: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    service_public_id: '',
});

function formatMoney(minor, currency) {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: currency || 'USD',
    }).format(Number(minor) / 100);
}

function pay(servicePublicId) {
    form.service_public_id = servicePublicId;
    form.post(route('pay.shop.pay'), { preserveScroll: true });
}
</script>

<template>
    <Head :title="merchant?.business_name ?? 'Shop'" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold text-gray-900">
                    {{ merchant?.business_name }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">Pay with your personal wallet balance</p>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <InputError class="mb-4" :message="form.errors.service_public_id" />
                <ul
                    v-if="services.length"
                    class="space-y-3"
                >
                    <li
                        v-for="s in services"
                        :key="s.public_id"
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
                    >
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ s.name }}</h3>
                                <p v-if="s.description" class="mt-1 text-sm text-gray-600">
                                    {{ s.description }}
                                </p>
                                <p class="mt-2 text-lg font-medium tabular-nums text-slate-900">
                                    {{ formatMoney(s.price_minor, s.currency) }}
                                </p>
                            </div>
                            <div class="shrink-0 sm:pt-1">
                                <button
                                    type="button"
                                    @click="pay(s.public_id)"
                                    :disabled="form.processing"
                                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-50"
                                >
                                    Pay
                                </button>
                            </div>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-gray-500">This merchant has no services listed yet.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
