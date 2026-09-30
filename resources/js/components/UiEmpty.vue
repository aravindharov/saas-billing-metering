<template>
    <div class="ui-empty">
        <p class="ui-empty-title">{{ title }}</p>
        <p v-if="$slots.default" class="ui-empty-text">
            <slot />
        </p>
        <p v-else-if="message" class="ui-empty-text">{{ message }}</p>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
    message?: string;
}>();
</script>
