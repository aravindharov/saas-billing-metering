<template>
    <span class="ui-badge" :class="badgeClass">
        <slot />
    </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        variant?: 'success' | 'neutral' | 'danger' | 'warning';
    }>(),
    { variant: 'neutral' },
);

const badgeClass = computed(() => {
    switch (props.variant) {
        case 'success':
            return 'ui-badge-success';
        case 'danger':
            return 'ui-badge-danger';
        case 'warning':
            return 'ui-badge-warning';
        default:
            return 'ui-badge-neutral';
    }
});
</script>
