<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Eye, EyeOff } from 'lucide-vue-next';

defineProps({
    modelValue: String,
    id: String,
    placeholder: String,
});

defineEmits(['update:modelValue']);

const isRevealed = ref(false);
</script>

<template>
    <div class="relative">
        <Input :id="id" :type="isRevealed ? 'text' : 'password'" :model-value="modelValue"
            @update:model-value="$emit('update:modelValue', $event)" :placeholder="placeholder"
            class="font-mono pr-10" />
        <div class="absolute inset-y-0 right-0 flex items-center pr-2">
            <Button type="button" variant="ghost" size="icon"
                class="h-8 w-8 text-muted-foreground hover:text-foreground" @click="isRevealed = !isRevealed">
                <Eye v-if="!isRevealed" class="h-4 w-4" />
                <EyeOff v-else class="h-4 w-4" />
            </Button>
        </div>
    </div>
</template>
