<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    lines: Array,
});
</script>

<template>
    <Head title="Your ledger lines" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-xl font-semibold text-gray-900">Ledger (your wallets)</h2>
                <Link
                    :href="route('pay.deposit.create')"
                    class="text-sm font-medium text-indigo-600 hover:underline"
                >+ Deposit</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <p class="mb-4 text-sm text-gray-600">
                    Only lines for <strong>your</strong> user/merchant wallets. To see
                    <strong>both legs</strong> of a batch (incl. platform clearing), open
                    a batch.
                </p>
                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-2">Type</th>
                                <th class="px-4 py-2">Cents</th>
                                <th class="px-4 py-2">Holder</th>
                                <th class="px-4 py-2">Batch</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="row in lines" :key="row.id">
                                <td class="px-4 py-2 font-mono text-xs">
                                    {{ row.type }}
                                </td>
                                <td
                                    class="px-4 py-2 font-mono"
                                    :class="row.cents < 0 ? 'text-rose-600' : 'text-emerald-700'"
                                >
                                    {{ row.cents }}
                                </td>
                                <td class="px-4 py-2 text-gray-700">
                                    {{ row.holder_label }}
                                </td>
                                <td class="px-4 py-2">
                                    <Link
                                        v-if="row.batch?.ref"
                                        :href="route('pay.batches.show', { ref: row.batch.ref })"
                                        class="text-indigo-600 hover:underline"
                                    >
                                        {{ row.batch.name }}
                                    </Link>
                                    <span v-else>—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
