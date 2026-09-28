<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/DashboardPageController';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

const open = defineModel<boolean>('open', { required: true });

const page = usePage();

const name = ref('');
const error = ref<string | null>(null);
const saving = ref(false);

/** Creates an empty page for the team and opens it (still in edit mode). */
const create = (): void => {
    saving.value = true;
    error.value = null;
    router.post(store.url({ current_team: page.props.currentTeam?.slug ?? '' }), { name: name.value }, {
        onSuccess: () => {
            open.value = false;
            name.value = '';
        },
        onError: (errors) => {
            error.value = errors.name ?? 'Could not add the page.';
        },
        onFinish: () => {
            saving.value = false;
        },
    });
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Add a dashboard page</DialogTitle>
                <DialogDescription>The page starts empty. Drag widgets onto it from the widget sheet.</DialogDescription>
            </DialogHeader>
            <form class="space-y-2" @submit.prevent="create">
                <Input v-model="name" placeholder="e.g. Media room" maxlength="40" autofocus required />
                <p v-if="error" class="text-xs text-destructive">{{ error }}</p>
                <DialogFooter>
                    <Button type="submit" :disabled="saving || !name.trim()">Add page</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
