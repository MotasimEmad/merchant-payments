<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    wallets: Array,
    ledgerPreview: Array,
});

function fmtMinor(minor, currency) {
    const n = Number(minor) / 100;
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: currency || 'USD',
    }).format(n);
}

const totalPersonalUsd = computed(() => {
    const row = props.wallets?.find((w) => w.holder === 'User');
    return row ? row.balance_minor : 0;
});
</script>

<template>
    <Head title="Merchant Payment Infrastructure" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Wallets & ledger
                </h2>
                <p class="text-sm text-gray-500">
                    Balances are derived only from posted ledger lines (double-entry).
                </p>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-900 to-slate-800 p-6 text-white shadow-lg sm:p-8"
                >
                    <p class="text-sm font-medium uppercase tracking-wider text-slate-300">
                        Personal wallet (USD)
                    </p>
                    <p class="mt-2 text-4xl font-semibold tabular-nums">
                        {{ fmtMinor(totalPersonalUsd, 'USD') }}
                    </p>
                    <p class="mt-4 max-w-xl text-sm text-slate-300">
                        Deposits, card-style charges, refunds, and transfers all post as balanced
                        batches—never a raw balance update.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3 text-sm">
                        <Link
                            :href="route('pay.deposit.create')"
                            class="rounded-lg bg-white/10 px-3 py-2 font-medium text-white ring-1 ring-white/20 hover:bg-white/20"
                        >Simulate deposit</Link>
                        <Link
                            :href="route('pay.ledger')"
                            class="rounded-lg border border-white/30 bg-white/5 px-3 py-2 text-white hover:bg-white/10"
                        >My ledger</Link>
                        <Link
                            :href="route('pay.shops')"
                            class="rounded-lg border border-white/30 bg-white/5 px-3 py-2 text-white hover:bg-white/10"
                        >Browse shops</Link>
                        <Link
                            :href="route('pay.merchant.services')"
                            class="rounded-lg border border-white/30 bg-white/5 px-3 py-2 text-white hover:bg-white/10"
                        >My services</Link>
                        <Link
                            :href="route('pay.platform')"
                            class="rounded-lg border border-white/30 bg-white/5 px-3 py-2 text-white hover:bg-white/10"
                        >Platform accounts</Link>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                    >
                        <div class="border-b border-gray-100 px-5 py-4">
                            <h3 class="text-sm font-semibold text-gray-900">
                                Wallets
                            </h3>
                            <p class="text-xs text-gray-500">
                                User + connected merchant business wallets
                            </p>
                        </div>
                        <ul class="divide-y divide-gray-100">
                            <li
                                v-for="w in wallets"
                                :key="w.uuid"
                                class="flex items-center justify-between px-5 py-4"
                            >
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ w.label || 'Wallet' }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ w.holder }} · {{ w.currency }}
                                    </p>
                                </div>
                                <span
                                    class="text-sm font-semibold tabular-nums text-gray-900"
                                >
                                    {{ fmtMinor(w.balance_minor, w.currency) }}
                                </span>
                            </li>
                        </ul>
                    </div>

                    <div
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                    >
                        <div class="border-b border-gray-100 px-5 py-4">
                            <h3 class="text-sm font-semibold text-gray-900">
                                API surface
                            </h3>
                            <p class="text-xs text-gray-500">
                                Token auth (Sanctum) under
                                <code class="rounded bg-gray-100 px-1 text-xs"
                                    >/api/v1</code
                                >
                            </p>
                        </div>
                        <div class="space-y-3 px-5 py-4 text-sm text-gray-600">
                            <p>
                                <span class="font-medium text-gray-800"
                                    >POST</span
                                >
                                <code
                                    class="ml-2 rounded bg-slate-50 px-1.5 py-0.5 text-xs"
                                    >/api/v1/wallets/&#123;uuid&#125;/deposits</code
                                >
                            </p>
                            <p>
                                <span class="font-medium text-gray-800"
                                    >POST</span
                                >
                                <code
                                    class="ml-2 rounded bg-slate-50 px-1.5 py-0.5 text-xs"
                                    >/api/v1/payment_intents</code
                                >
                                →
                                <code
                                    class="ml-1 rounded bg-slate-50 px-1.5 py-0.5 text-xs"
                                    >/confirm</code
                                >
                            </p>
                            <p>
                                <span class="font-medium text-gray-800"
                                    >GET</span
                                >
                                <code
                                    class="ml-2 rounded bg-slate-50 px-1.5 py-0.5 text-xs"
                                    >/api/v1/ledger</code
                                >
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-900">
                            Recent ledger lines
                        </h3>
                        <p class="text-xs text-gray-500">
                            Newest first (across your wallets)
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="px-5 py-2 font-medium">Type</th>
                                    <th class="px-5 py-2 font-medium">Amount</th>
                                    <th class="px-5 py-2 font-medium">Batch</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="row in ledgerPreview"
                                    :key="row.id"
                                >
                                    <td class="px-5 py-2.5 text-gray-800">
                                        <code
                                            class="rounded bg-slate-50 px-1.5 py-0.5 text-xs"
                                            >{{ row.type }}</code
                                        >
                                    </td>
                                    <td
                                        class="px-5 py-2.5 font-mono text-sm tabular-nums"
                                        :class="
                                            row.cents < 0
                                                ? 'text-rose-600'
                                                : 'text-emerald-600'
                                        "
                                    >
                                        {{ (row.cents / 100).toFixed(2) }}
                                    </td>
                                    <td class="max-w-xs truncate px-5 py-2.5 text-gray-500">
                                        {{ row.batch_name }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
