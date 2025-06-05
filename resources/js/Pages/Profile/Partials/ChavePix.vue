<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    _method: 'PUT',
    chave_pix: props.user.chave_pix,
});

const updateChavePix = () => {
    form.post(route('user-chave-pix.update'), {
        errorBag: 'updateChavePix',
        preserveScroll: true,
    });
};
</script>

<template>
    <FormSection @submitted="updateChavePix">
        <template #title>
            Chave Pix
        </template>

        <template #description>
            Atualize sua chave Pix.
        </template>

        <template #form>
            <!-- Chave Pix -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="chave_pix" value="Chave Pix" />
                <TextInput
                    id="chave_pix"
                    v-model="form.chave_pix"
                    type="text"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.chave_pix" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Salvo.
            </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Salvar
            </PrimaryButton>
        </template>
    </FormSection>
</template>