<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import SocialLoginButtons from '@/components/SocialLoginButtons.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Aanmelden',
        description: 'Meld je aan met het account van de geboortelijst',
    },
});

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
    intent?: string | null;
}>();

const registerUrl = props.intent
    ? register({ query: { bedoeling: props.intent } })
    : register();
</script>

<template>
    <Head title="Aanmelden" />

    <div
        v-if="intent === 'bijdragen'"
        class="mb-4 rounded-md bg-muted p-3 text-sm text-muted-foreground"
    >
        Leuk dat je iets wil geven! 🎁 Meld je eerst even aan — daarna kom je
        meteen terug bij het cadeau. Zo weten we van wie de bijdrage komt en kan
        je ze later zelf opvolgen.
    </div>

    <div
        v-else-if="intent === 'opvolgen'"
        class="mb-4 rounded-md bg-muted p-3 text-sm text-muted-foreground"
    >
        Meld je aan om je eigen bijdragen te bekijken en op te volgen.
    </div>

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <PasskeyVerify />

    <SocialLoginButtons class="mb-6" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Mailadres</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="jij@voorbeeld.be"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Wachtwoord</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm"
                        :tabindex="5"
                    >
                        Wachtwoord vergeten?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Wachtwoord"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span>Aangemeld blijven</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Aanmelden
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Nog geen account?
            <TextLink :href="registerUrl" :tabindex="5"
                >Maak er een in een minuutje</TextLink
            >
        </div>
    </Form>
</template>
