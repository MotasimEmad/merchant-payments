<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    batch: Object,
    lines: Array,
});

const total = computed(() => props.lines?.reduce((s, l) => s + l.cents, 0) ?? 0);
</script>

<template>
    <Head :title="`Batch ${batch.ref}`" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Ledger batch</h2>
                <p class="text-sm text-gray-500">
                    <code class="select-all text-xs">{{ batch.ref }}</code>
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <div
                    v-if="$page.props.flash?.message"
                    class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900"
                >
                    {{ $page.props.flash.message }}
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-800">
                        {{ batch.name }}
                    </h3>
                    <p class="text-xs text-gray-500">Channel: {{ batch.channel }}</p>
                </div>
                <p class="text-sm text-gray-600">
                    Full double-entry for this event — includes your wallet and
                    <strong>platform (clearing)</strong> so the total is
                    <strong>zero</strong>.
                </p>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-600">
                            <tr>
                                <th class="px-4 py-2">Wallet side</th>
                                <th class="px-4 py-2">Type</th>
                                <th class="px-4 py-2">Cents (Δ)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="(l, i) in lines" :key="i">
                                <td class="px-4 py-2">{{ l.holder }}</td>
                                <td class="px-4 py-2 font-mono text-xs">
                                    {{ l.type }}
                                </td>
                                <td
                                    class="px-4 py-2 font-mono"
                                    :class="l.cents < 0 ? 'text-rose-600' : 'text-emerald-700'"
                                >
                                    {{ l.cents }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="border-t bg-amber-50 text-sm font-medium">
                            <tr>
                                <td colspan="2" class="px-4 py-2">Sum (must be 0)</td>
                                <td class="px-4 py-2 font-mono" :class="total !== 0 ? 'text-red-600' : 'text-slate-800'">
                                    {{ total }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <Link
                    :href="route('pay.ledger')"
                    class="text-sm text-indigo-600 hover:underline"
                >Back to ledger</Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
