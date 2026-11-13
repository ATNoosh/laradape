<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    profile: { type: Object, default: null },
});

const defaults = {
    engine: 'hybrid',
    scan_dirs: ['src', 'app', 'lib', 'modules'],
    rector: { mode: 'auto', config_path: null, exclude: [], timeout_seconds: 600 },
    exakat: { mode: 'auto', binary_path: null, config_path: null, analyzers: [], timeout_seconds: 1200 },
    ai: { mode: 'off', provider: 'ollama', base_url: 'http://127.0.0.1:11434', model: 'qwen2.5-coder:1.5b', max_files_per_hop: 5 },
};

const form = useForm({
    name: props.profile?.name ?? '',
    description: props.profile?.description ?? '',
    configuration: props.profile?.configuration ?? defaults,
});

const editing = computed(() => props.profile !== null);

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    };

    if (editing.value) {
        form.put(route('profiles.update', props.profile.name), options);
    } else {
        form.post(route('profiles.store'), options);
    }
}
</script>

<template>
    <Head :title="editing ? 'Edit profile' : 'New profile'" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <p class="text-sm font-medium text-slate-500">Configuration management</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ editing ? 'Edit profile' : 'New profile' }}</h1>
            </div>
        </template>

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <form class="space-y-6" @submit.prevent="submit">
                <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-950">Identity</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <label>
                            <span class="text-sm font-medium text-slate-700">Name</span>
                            <input v-model="form.name" required pattern="[a-z0-9][a-z0-9._-]*" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-rose-600">{{ form.errors.name }}</p>
                        </label>
                        <label>
                            <span class="text-sm font-medium text-slate-700">Description</span>
                            <input v-model="form.description" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500" />
                            <p v-if="form.errors.description" class="mt-1 text-sm text-rose-600">{{ form.errors.description }}</p>
                        </label>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-950">Pipeline</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <label>
                            <span class="text-sm font-medium text-slate-700">Engine</span>
                            <select v-model="form.configuration.engine" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                <option value="auto">Auto</option><option value="hybrid">Hybrid</option><option value="rector">Rector</option><option value="regex">Regex</option>
                            </select>
                        </label>
                        <label>
                            <span class="text-sm font-medium text-slate-700">Scan directories</span>
                            <input :value="form.configuration.scan_dirs.join(', ')" @input="form.configuration.scan_dirs = $event.target.value.split(',').map((item) => item.trim()).filter(Boolean)" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500" />
                        </label>
                    </div>
                </section>

                <section class="grid gap-6 lg:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-950">Rector</h2>
                        <label class="mt-4 block"><span class="text-sm font-medium text-slate-700">Mode</span><select v-model="form.configuration.rector.mode" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm"><option value="auto">Auto</option><option value="bundled">Bundled</option><option value="project">Project</option><option value="docker">Docker</option></select></label>
                        <label class="mt-4 block"><span class="text-sm font-medium text-slate-700">Timeout seconds</span><input v-model.number="form.configuration.rector.timeout_seconds" type="number" min="1" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm" /></label>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-950">Exakat</h2>
                        <label class="mt-4 block"><span class="text-sm font-medium text-slate-700">Mode</span><select v-model="form.configuration.exakat.mode" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm"><option value="auto">Auto</option><option value="configured">Configured</option><option value="off">Off</option></select></label>
                        <label class="mt-4 block"><span class="text-sm font-medium text-slate-700">Timeout seconds</span><input v-model.number="form.configuration.exakat.timeout_seconds" type="number" min="1" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm" /></label>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-950">AI</h2>
                        <label class="mt-4 block"><span class="text-sm font-medium text-slate-700">Mode</span><select v-model="form.configuration.ai.mode" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm"><option value="off">Off</option><option value="auto">Auto</option><option value="ollama">Ollama</option></select></label>
                        <label class="mt-4 block"><span class="text-sm font-medium text-slate-700">Model</span><input v-model="form.configuration.ai.model" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm" /></label>
                        <label class="mt-4 block"><span class="text-sm font-medium text-slate-700">Max files per hop</span><input v-model.number="form.configuration.ai.max_files_per_hop" type="number" min="0" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm" /></label>
                    </div>
                </section>

                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('profiles.index')" class="rounded-md px-4 py-2 text-sm font-semibold text-slate-700 hover:text-slate-950">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700 disabled:opacity-60">{{ form.processing ? 'Saving...' : 'Save profile' }}</button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
