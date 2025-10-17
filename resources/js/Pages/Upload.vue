<script setup>
import { ref } from 'vue'

const file = ref(null);
const loading = ref(false);
const result = ref(null);
const error = ref(null);
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

const submit = async () => {
    error.value = null;
    result.value = null;

    if (!file.value?.files?.[0]) {
        error.value = 'Please choose a CSV file';
        return
    }

    const form = new FormData();
    form.append('file', file.value.files[0]);
    loading.value = true;

    try {
        const res = await fetch('/parse', { method: 'POST', body: form, headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token} });
        const data = await res.json();
        if (!res.ok) {
            error.value = data?.errors || 'Upload failed';
        } else {
            result.value = data;
        }
    } catch (e) {
        error.value = String(e);
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="w-full max-w-2xl space-y-4">
            <h1 class="text-2xl font-semibold">CSV to JSON</h1>

            <p>Convert CSV into individual people records</p>

            <input ref="file" type="file" accept=".csv,text/csv" />

            <button :disabled="loading" @click="submit" class="px-4 py-2 rounded bg-black text-white disabled:opacity-50">
                {{ loading ? 'Parsing…' : 'Upload & Parse' }}
            </button>

            <div v-if="error" class="text-red-600 text-sm whitespace-pre-wrap">{{ error }}</div>

            <div v-if="result" class="mt-4">
                <h2 class="font-medium mb-2">Result</h2>
                <pre class="bg-gray-100 p-3 rounded overflow-auto text-sm">{{ JSON.stringify(result, null, 2) }}</pre>
            </div>
        </div>
    </div>
</template>

<style scoped>
</style>
