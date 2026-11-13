<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    profiles: { type: Array, required: true },
});

function removeProfile(profile) {
    if (!window.confirm(`Delete profile "${profile.name}"?`)) return;

    router.delete(route('profiles.destroy', profile.name), { preserveScroll: true });
}
</script>

<template>
    <Head title="Upgrade profiles" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-slate-500">Configuration management</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">Upgrade profiles</h1>
                </div>
                <Link :href="route('profiles.create')" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700">
                    New profile
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article v-for="profile in profiles" :key="profile.name" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold text-slate-950">{{ profile.name }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ profile.description || 'No description' }}</p>
                        </div>
                        <span v-if="profile.built_in" class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">Built-in</span>
                    </div>
                    <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-slate-500">Engine</dt>
                            <dd class="font-medium text-slate-800">{{ profile.configuration.engine }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">AI</dt>
                            <dd class="font-medium text-slate-800">{{ profile.configuration.ai.mode }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Rector</dt>
                            <dd class="font-medium text-slate-800">{{ profile.configuration.rector.mode }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Exakat</dt>
                            <dd class="font-medium text-slate-800">{{ profile.configuration.exakat.mode }}</dd>
                        </div>
                    </dl>
                    <div v-if="!profile.built_in" class="mt-5 flex gap-2 border-t border-slate-100 pt-4">
                        <Link :href="route('profiles.edit', profile.name)" class="text-sm font-semibold text-slate-700 hover:text-slate-950">Edit</Link>
                        <button type="button" class="text-sm font-semibold text-rose-700 hover:text-rose-900" @click="removeProfile(profile)">Delete</button>
                    </div>
                </article>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
