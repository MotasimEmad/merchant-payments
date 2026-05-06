<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="space-y-4 p-6 text-gray-900">
                        <p>You're logged in!</p>
                        <p class="text-sm text-gray-600">
                            Merchant catalog and “add service” live under
                            <strong>Pay Infra</strong>
                            →
                            <Link
                                :href="route('pay.merchant.services')"
                                class="font-medium text-indigo-600 hover:text-indigo-800"
                            >My services</Link>
                            (
                            <code
                                class="rounded bg-gray-100 px-1 text-xs text-gray-700"
                            >/pay/merchant/services</code>
                            ).
                        </p>
                        <div class="flex flex-wrap gap-3 pt-2">
                            <Link
                                :href="route('pay.dashboard')"
                                class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700"
                            >
                                Open Pay Infra
                            </Link>
                            <Link
                                :href="route('pay.merchant.services')"
                                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                            >
                                My services (merchant catalog)
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
