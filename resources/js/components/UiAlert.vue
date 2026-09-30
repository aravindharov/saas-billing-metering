<template>
    <div role="alert" :class="variant === 'success' ? 'ui-alert-success' : 'ui-alert-error'">
        <slot />
    </div>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        variant?: 'error' | 'success';
    }>(),
    { variant: 'error' },
);
</script>
