<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    merchants: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Shops" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-900">Browse shops</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <p class="mb-6 text-sm text-gray-600">
                    Merchants with at least one active service. Open a shop to view what they
                    offer and pay from your wallet (you need a positive balance or deposit
                    first).
                </p>
                <ul
                    v-if="merchants.length"
                    class="divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm"
                >
                    <li v-for="m in merchants" :key="m.public_id" class="flex items-center justify-between gap-3 px-4 py-3">
                        <span class="font-medium text-gray-900">{{ m.business_name }}</span>
                        <Link
                            :href="route('pay.shop', m.public_id)"
                            class="shrink-0 rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-800"
                        >View</Link>
                    </li>
                </ul>
                <p v-else class="text-sm text-gray-500">No shops with services yet.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
