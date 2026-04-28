<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

function fmt(cents) {
    return (Number(cents) / 100).toFixed(2);
}
defineProps({ accounts: Array });
</script>

<template>
    <Head title="Platform accounts" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-900">Platform accounts (read)</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-600">
                    Internal
                    <code>platform_accounts</code>
                    + linked wallets. Balance = sum of
                    <code>ledger_lines.cents</code>.
                </p>
                <div
                    v-for="a in accounts"
                    :key="a.key"
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <h3 class="font-semibold text-slate-900">
                        <code class="text-indigo-700">{{ a.key }}</code>
                        — {{ a.label }}
                    </h3>
                    <p v-if="a.description" class="mt-1 text-xs text-gray-500">
                        {{ a.description }}
                    </p>
                    <ul class="mt-3 space-y-1 text-sm text-gray-700">
                        <li
                            v-for="w in a.wallets"
                            :key="w.uuid"
                        >
                            {{ w.currency }}:
                            <span class="font-mono">{{ fmt(w.balance_cents) }}</span>
                            ({{ w.balance_cents }} cents)
                        </li>
                    </ul>
                </div>
                <Link
                    :href="route('pay.deposit.create')"
                    class="text-sm text-indigo-600 hover:underline"
                >Try a deposit</Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
