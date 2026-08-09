<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

const props = defineProps<{
    passwordRules: string;
    intent?: string | null;
}>();

defineOptions({
    layout: {
        title: 'Account aanmaken',
        description:
            'In een minuutje klaar, en je hoeft het maar één keer te doen',
    },
});

const loginUrl = props.intent
    ? login({ query: { bedoeling: props.intent } })
    : login();
</script>

<template>
    <Head title="Account aanmaken" />

    <div class="mb-4 rounded-md bg-muted p-3 text-sm text-muted-foreground">
        <template v-if="intent === 'bijdragen'">
            Nog even dit, en dan kom je meteen terug bij het cadeau.
        </template>
        Met een account weten wij van wie elke bijdrage komt, en kan jij je
        bijdragen later terugvinden en opvolgen. Je naam en mailadres gebruiken
        we alleen daarvoor.
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Je naam</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Voornaam en naam"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Mailadres</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="jij@voorbeeld.be"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Wachtwoord</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Wachtwoord"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Herhaal je wachtwoord</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Herhaal je wachtwoord"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Account aanmaken
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Heb je al een account?
            <TextLink
                :href="loginUrl"
                class="underline underline-offset-4"
                :tabindex="6"
                >Meld je aan</TextLink
            >
        </div>
    </Form>
</template>
