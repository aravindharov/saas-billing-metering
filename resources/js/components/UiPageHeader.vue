<template>
    <header class="ui-page-header">
        <div>
            <h2 class="ui-title">{{ title }}</h2>
            <p v-if="description" class="ui-subtitle">{{ description }}</p>
        </div>
        <div v-if="$slots.actions" class="ui-toolbar shrink-0">
            <slot name="actions" />
        </div>
    </header>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>
