<template>
    <button
        class="button"
        v-bind="$attrs"
    >
        <slot></slot>
    </button>
</template>

<script>
    export default {
        name:'app-button',
    };
</script>
