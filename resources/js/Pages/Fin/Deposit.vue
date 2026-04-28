<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    wallet: Object,
});

const form = useForm({
    amount: '',
    currency: props.wallet?.currency || 'USD',
    idempotency: '',
});

function submit() {
    form.post(route('pay.deposit.store'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Simulated deposit" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-900">Deposit (simulated)</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-lg sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white p-6 shadow sm:rounded-lg"
                >
                    <p class="mb-4 text-sm text-gray-600">
                        This runs the same
                        <code class="rounded bg-gray-100 px-1">WalletOperationService::deposit</code>
                        as the API: your personal wallet is credited; a matching
                        <strong>clearing</strong> line is posted for double-entry.
                    </p>
                    <p class="mb-6 text-xs text-gray-500">
                        Wallet:
                        <code class="select-all rounded bg-slate-100 px-1 py-0.5">{{
                            wallet.uuid
                        }}</code>
                    </p>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <InputLabel
                                for="amount"
                                value="Amount (USD, major units e.g. 25.50)"
                            />
                            <TextInput
                                id="amount"
                                v-model="form.amount"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                autocomplete="off"
                            />
                            <InputError
                                class="mt-1"
                                :message="form.errors.amount"
                            />
                        </div>
                        <div>
                            <InputLabel
                                for="idempotency"
                                value="Idempotency key (optional — leave empty to auto-generate)"
                            />
                            <TextInput
                                id="idempotency"
                                v-model="form.idempotency"
                                type="text"
                                class="mt-1 block w-full"
                                autocomplete="off"
                            />
                        </div>
                        <div class="flex items-center gap-3">
                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                Post deposit
                            </PrimaryButton>
                            <a
                                :href="route('pay.ledger')"
                                class="text-sm text-indigo-600 hover:underline"
                            >View ledger</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
