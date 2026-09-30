<template>
    <div v-if="meta && meta.last_page > 1" class="ui-pagination">
        <span>
            Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} {{ label }})
        </span>
        <div class="flex gap-2">
            <button
                type="button"
                class="ui-btn-secondary"
                :disabled="meta.current_page <= 1"
                @click="emit('change', meta.current_page - 1)"
            >
                Previous
            </button>
            <button
                type="button"
                class="ui-btn-secondary"
                :disabled="meta.current_page >= meta.last_page"
                @click="emit('change', meta.current_page + 1)"
            >
                Next
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { PaginatedResponse } from '@/types/plans';

defineProps<{
    meta: PaginatedResponse<unknown>['meta'] | null;
    label: string;
}>();

const emit = defineEmits<{
    change: [page: number];
}>();
</script>
