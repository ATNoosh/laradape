<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    metrics: { type: Object, required: true },
    jobs: { type: Array, required: true },
    refreshedAt: { type: String, required: true },
    profiles: { type: Array, required: true },
});

const refreshing = ref(false);
const submitting = ref(false);
const submitError = ref('');
const form = ref({
    project_path: '',
    profile: 'conservative',
    from: '7.4',
    to: '8.3',
    dry_run: true,
    engine: 'auto',
    ai: 'off',
    exakat: 'off',
});
let refreshTimer;

const cards = computed(() => [
    { label: 'Total jobs', value: props.metrics.total },
    { label: 'Running', value: props.metrics.running },
    { label: 'Pending', value: props.metrics.pending },
    { label: 'Completed', value: props.metrics.completed },
    { label: 'Failed', value: props.metrics.failed },
    { label: 'Cancelled', value: props.metrics.cancelled },
]);

function refresh() {
    refreshing.value = true;
    router.reload({
        only: ['metrics', 'jobs', 'refreshedAt'],
        onFinish: () => { refreshing.value = false; },
    });
}

async function submitUpgrade() {
    submitting.value = true;
    submitError.value = '';

    try {
        const response = await fetch('/api/v1/upgrades/queue', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                job_id: `dashboard-${crypto.randomUUID()}`,
                project_path: form.value.project_path,
                from: form.value.from,
                to: form.value.to,
                dry_run: form.value.dry_run,
                profile: form.value.profile,
                options: {
                    engine: form.value.engine,
                    ai: form.value.ai,
                    exakat: form.value.exakat,
                },
            }),
        });

        const payload = await response.json();
        if (!response.ok || !payload.data) {
            submitError.value = payload.error?.message ?? 'Unable to queue the upgrade.';
            return;
        }

        form.value.project_path = '';
        refresh();
    } catch (error) {
        submitError.value = error instanceof Error ? error.message : 'Unable to reach the queue endpoint.';
    } finally {
        submitting.value = false;
    }
}

function applyProfile() {
    const profile = props.profiles.find((item) => item.name === form.value.profile);
    if (!profile) return;

    form.value.engine = profile.configuration.engine;
    form.value.ai = profile.configuration.ai.mode;
    form.value.exakat = profile.configuration.exakat.mode === 'configured'
        ? 'auto'
        : profile.configuration.exakat.mode;
}

function statusClass(status) {
    return {
        pending: 'bg-amber-50 text-amber-700 ring-amber-600/20',
        running: 'bg-blue-50 text-blue-700 ring-blue-600/20',
        completed: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        failed: 'bg-rose-50 text-rose-700 ring-rose-600/20',
        cancelled: 'bg-slate-100 text-slate-600 ring-slate-500/20',
    }[status] ?? 'bg-slate-100 text-slate-600 ring-slate-500/20';
}

function formatDate(value) {
    if (!value) return '-';
    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' })
        .format(new Date(value));
}

onMounted(() => { refreshTimer = window.setInterval(refresh, 15000); });
onBeforeUnmount(() => window.clearInterval(refreshTimer));
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-slate-500">Pupgrader operations</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">Upgrade dashboard</h1>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700 disabled:cursor-wait disabled:opacity-60"
                    :disabled="refreshing"
                    @click="refresh"
                >
                    <span aria-hidden="true" :class="{ 'animate-spin': refreshing }">&#8635;</span>
                    Refresh
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5">
                    <h2 class="text-base font-semibold text-slate-950">Queue an upgrade</h2>
                    <p class="mt-1 text-sm text-slate-500">Submit a project to the database-backed execution queue.</p>
                </div>

                <form class="grid gap-4 md:grid-cols-2 xl:grid-cols-4" @submit.prevent="submitUpgrade">
                    <label class="md:col-span-2 xl:col-span-4">
                        <span class="text-sm font-medium text-slate-700">Project path</span>
                        <input v-model="form.project_path" required type="text" placeholder="C:\\projects\\legacy-app" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500" />
                    </label>

                    <label>
                        <span class="text-sm font-medium text-slate-700">Profile</span>
                        <select v-model="form.profile" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500" @change="applyProfile">
                            <option v-for="profile in profiles" :key="profile.name" :value="profile.name">{{ profile.name }}</option>
                        </select>
                    </label>
                    <label>
                        <span class="text-sm font-medium text-slate-700">From PHP</span>
                        <input v-model="form.from" required type="text" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500" />
                    </label>
                    <label>
                        <span class="text-sm font-medium text-slate-700">To PHP</span>
                        <input v-model="form.to" required type="text" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500" />
                    </label>
                    <label>
                        <span class="text-sm font-medium text-slate-700">Engine</span>
                        <select v-model="form.engine" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="auto">Auto</option>
                            <option value="hybrid">Hybrid</option>
                            <option value="rector">Rector</option>
                            <option value="regex">Regex</option>
                        </select>
                    </label>
                    <label>
                        <span class="text-sm font-medium text-slate-700">AI</span>
                        <select v-model="form.ai" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="off">Off</option>
                            <option value="auto">Auto</option>
                            <option value="ollama">Ollama</option>
                        </select>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700 md:col-span-2">
                        <input v-model="form.dry_run" type="checkbox" class="rounded border-slate-300 text-slate-900 shadow-sm focus:ring-slate-500" />
                        Dry run
                    </label>
                    <div class="flex items-end justify-end md:col-span-2">
                        <button type="submit" :disabled="submitting" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700 disabled:cursor-wait disabled:opacity-60">
                            {{ submitting ? 'Submitting...' : 'Queue upgrade' }}
                        </button>
                    </div>
                </form>
                <p v-if="submitError" class="mt-4 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">{{ submitError }}</p>
            </section>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6" aria-label="Job metrics">
                <article v-for="card in cards" :key="card.label" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">{{ card.label }}</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ card.value }}</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-5">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Recent jobs</h2>
                        <p class="mt-1 text-sm text-slate-500">Latest execution records from the database queue.</p>
                    </div>
                    <p class="text-xs text-slate-400">Updated {{ formatDate(refreshedAt) }}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-slate-50">
                            <tr class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-6 py-3">Job</th>
                                <th class="px-6 py-3">Pipeline</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Current stage</th>
                                <th class="px-6 py-3">Files</th>
                                <th class="px-6 py-3">Attempts</th>
                                <th class="px-6 py-3">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="job in jobs" :key="job.id" class="text-sm text-slate-700">
                                <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-950">
                                    <Link :href="route('dashboard.jobs.show', job.id)" class="hover:text-slate-500">{{ job.id }}</Link>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 font-mono text-xs text-slate-600">{{ job.pipeline }}</td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1 ring-inset" :class="statusClass(job.status)">
                                        {{ job.status }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 font-mono text-xs text-slate-600">{{ job.current_stage ?? '-' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-slate-600">{{ job.files_changed }} / {{ job.files_total }}</td>
                                <td class="whitespace-nowrap px-6 py-4">{{ job.attempts }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-slate-500">{{ formatDate(job.created_at) }}</td>
                            </tr>
                            <tr v-if="jobs.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">No jobs have been submitted yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
