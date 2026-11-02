<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';

type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

const props = defineProps<Props>();

const page = usePage();
</script>

<template>
    <Head title="Wachtwoord" />

    <div class="flex flex-col gap-6">
        <Card class="rounded-3xl border-2 border-white/90 shadow-sm">
            <CardContent class="space-y-6">
                <Heading
                    variant="small"
                    title="Wachtwoord wijzigen"
                    description="Kies een lang wachtwoord dat je nergens anders gebruikt."
                />

                <Form
                    v-bind="SecurityController.update.form()"
                    :options="{
                        preserveScroll: true,
                    }"
                    reset-on-success
                    :reset-on-error="[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="current_password">Huidig wachtwoord</Label>
                        <PasswordInput
                            id="current_password"
                            name="current_password"
                            class="mt-1 block w-full"
                            autocomplete="current-password"
                            placeholder="Huidig wachtwoord"
                        />
                        <InputError :message="errors.current_password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Nieuw wachtwoord</Label>
                        <PasswordInput
                            id="password"
                            name="password"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            placeholder="Nieuw wachtwoord"
                            :passwordrules="props.passwordRules"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation"
                            >Herhaal nieuw wachtwoord</Label
                        >
                        <PasswordInput
                            id="password_confirmation"
                            name="password_confirmation"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            placeholder="Herhaal nieuw wachtwoord"
                            :passwordrules="props.passwordRules"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            class="rounded-full font-bold"
                            data-test="update-password-button"
                        >
                            Bewaren
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <template v-if="page.props.auth.is_admin">
            <ManageTwoFactor
                :canManageTwoFactor="canManageTwoFactor"
                :requiresConfirmation="requiresConfirmation"
                :twoFactorEnabled="twoFactorEnabled"
            />

            <ManagePasskeys
                :canManagePasskeys="canManagePasskeys"
                :passkeys="passkeys"
            />
        </template>
    </div>
</template>
