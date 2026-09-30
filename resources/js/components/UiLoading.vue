<template>
    <div class="ui-loading" role="status">
        <span class="ui-spinner" aria-hidden="true" />
        <span>{{ message }}</span>
    </div>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        message?: string;
    }>(),
    { message: 'Loading…' },
);
</script>
