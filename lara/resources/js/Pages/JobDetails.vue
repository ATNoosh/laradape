<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    job: { type: Object, required: true },
    chain: { type: Object, default: null },
    events: { type: Array, required: true },
});

const cancelling = ref(false);
const cancelError = ref('');
let refreshTimer;

const canCancel = computed(() => props.chain !== null && ['pending', 'running'].includes(props.job.status));
const changedFiles = computed(() => props.events.filter((event) => event.type === 'file.changed'));
const processedPaths = computed(() => props.events.filter((event) => event.type === 'path.processed'));
const stageLabel = computed(() => props.job.status === 'completed' ? 'Last completed stage' : 'Current stage');

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

function eventSummary(event) {
    const payload = event.payload ?? {};
    const values = ['file_count', 'files_changed', 'review_candidates', 'validated_files', 'engine', 'ai', 'exakat', 'hop']
        .filter((key) => payload[key] !== undefined)
        .map((key) => `${key.replaceAll('_', ' ')}: ${payload[key]}`);

    return values.join(' | ') || event.message || event.type;
}

async function cancelJob() {
    if (!canCancel.value || !window.confirm('Cancel this job chain?')) return;

    cancelling.value = true;
    cancelError.value = '';
    try {
        const response = await fetch(`/api/v1/jobs/${encodeURIComponent(props.job.job_id)}/cancel`, {
            method: 'POST',
            headers: { Accept: 'application/json' },
        });
        const payload = await response.json();
        if (!response.ok || !payload.data) {
            cancelError.value = payload.error?.message ?? 'Unable to cancel the job.';
            return;
        }
        router.reload();
    } catch (error) {
        cancelError.value = error instanceof Error ? error.message : 'Unable to reach the queue endpoint.';
    } finally {
        cancelling.value = false;
    }
}

onMounted(() => {
    refreshTimer = window.setInterval(() => router.reload({ only: ['job', 'chain', 'events'] }), 5000);
});
onBeforeUnmount(() => window.clearInterval(refreshTimer));
</script>

<template>
    <Head :title="`Job ${job.job_id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <Link :href="route('dashboard')" class="text-sm font-medium text-slate-500 hover:text-slate-700">Back to dashboard</Link>
                    <h1 class="mt-1 break-all text-2xl font-semibold tracking-tight text-slate-950">{{ job.job_id }}</h1>
                </div>
                <button v-if="canCancel" type="button" :disabled="cancelling" class="rounded-md bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 disabled:cursor-wait disabled:opacity-60" @click="cancelJob">
                    {{ cancelling ? 'Cancelling...' : 'Cancel job' }}
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <p v-if="cancelError" class="rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">{{ cancelError }}</p>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Status</p>
                    <span class="mt-3 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1 ring-inset" :class="statusClass(job.status)">{{ job.status }}</span>
                </article>
                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Pipeline</p>
                    <p class="mt-3 break-all font-mono text-sm text-slate-950">{{ job.pipeline }}</p>
                </article>
                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Attempts</p>
                    <p class="mt-3 text-2xl font-semibold text-slate-950">{{ job.attempts }}</p>
                </article>
                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Queue</p>
                    <p class="mt-3 font-mono text-sm text-slate-950">{{ job.queue }}</p>
                </article>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-950">Timeline</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div><p class="text-sm text-slate-500">{{ stageLabel }}</p><p class="mt-1 font-mono text-sm text-slate-950">{{ job.current_stage ?? '-' }}</p></div>
                    <div><p class="text-sm text-slate-500">Current hop</p><p class="mt-1 text-sm text-slate-950">{{ job.current_hop ?? '-' }}</p></div>
                    <div><p class="text-sm text-slate-500">Files with planned changes</p><p class="mt-1 text-sm text-slate-950">{{ job.files_changed }} / {{ job.files_total }}</p></div>
                    <div><p class="text-sm text-slate-500">Review candidates</p><p class="mt-1 text-sm text-slate-950">{{ job.files_review }}</p></div>
                </div>
                <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full bg-slate-900 transition-all" :style="{ width: `${job.progress_percent}%` }"></div>
                </div>
                <dl class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div><dt class="text-sm text-slate-500">Created</dt><dd class="mt-1 text-sm text-slate-950">{{ formatDate(job.created_at) }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Started</dt><dd class="mt-1 text-sm text-slate-950">{{ formatDate(job.started_at) }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Finished</dt><dd class="mt-1 text-sm text-slate-950">{{ formatDate(job.finished_at) }}</dd></div>
                </dl>
                <p v-if="job.dry_run" class="mt-4 text-xs text-slate-500">Dry run: no source files were written.</p>
                <div v-if="job.error" class="mt-5 rounded-md bg-rose-50 p-4 text-sm text-rose-700">
                    <p class="font-semibold">Execution error</p>
                    <pre class="mt-2 whitespace-pre-wrap font-sans">{{ job.error }}</pre>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Execution timeline</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ events.length }} recorded events</p>
                    </div>
                </div>
                <ol class="mt-5 divide-y divide-slate-100 border-t border-slate-100">
                    <li v-for="event in events" :key="event.id" class="grid gap-2 py-4 sm:grid-cols-[10rem_1fr_auto] sm:items-center">
                        <time class="text-xs text-slate-400">{{ formatDate(event.created_at) }}</time>
                        <div>
                            <p class="text-sm font-medium text-slate-950">{{ event.stage ?? event.type }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ eventSummary(event) }}</p>
                        </div>
                        <span class="inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1 ring-inset" :class="statusClass(event.status)">{{ event.status ?? 'info' }}</span>
                    </li>
                    <li v-if="events.length === 0" class="py-8 text-center text-sm text-slate-500">No execution events have been recorded yet.</li>
                </ol>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Changed files</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ changedFiles.length }} file-level changes recorded</p>
                </div>
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr><th class="px-4 py-3">File</th><th class="px-4 py-3">Hop</th><th class="px-4 py-3">Rule</th><th class="px-4 py-3">Description</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="event in changedFiles" :key="event.id">
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-950">{{ event.payload.file }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ event.payload.hop }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600">{{ event.payload.rule }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ event.payload.description }}</td>
                            </tr>
                            <tr v-if="changedFiles.length === 0"><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No file changes recorded yet.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-if="processedPaths.length" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-950">Processed paths</h2>
                <p class="mt-1 text-sm text-slate-500">Directories or project paths processed by analysis tools.</p>
                <ul class="mt-4 space-y-2 text-sm text-slate-700">
                    <li v-for="event in processedPaths" :key="event.id" class="flex flex-wrap justify-between gap-3 rounded-md bg-slate-50 px-3 py-2">
                        <span class="font-mono text-xs">{{ event.payload.file }}</span>
                        <span class="text-xs text-slate-500">{{ event.payload.rule }}</span>
                    </li>
                </ul>
            </section>

            <section v-if="chain" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Upgrade chain</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ chain.completed_hops }} of {{ chain.total_hops }} hops completed</p>
                    </div>
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1 ring-inset" :class="statusClass(chain.status)">{{ chain.status }}</span>
                </div>
                <ol class="mt-5 divide-y divide-slate-100 border-t border-slate-100">
                    <li v-for="hop in chain.jobs" :key="hop.job_id" class="flex flex-wrap items-center justify-between gap-3 py-4 text-sm">
                        <div>
                            <p class="font-medium text-slate-950">Hop {{ (hop.hop_index ?? 0) + 1 }} of {{ hop.hop_count ?? chain.total_hops }}</p>
                            <p class="mt-1 break-all font-mono text-xs text-slate-500">{{ hop.job_id }}</p>
                        </div>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1 ring-inset" :class="statusClass(hop.status)">{{ hop.status }}</span>
                    </li>
                </ol>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
