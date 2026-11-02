<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { send } from '@/routes/verification';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Mijn gegevens" />

    <div class="flex flex-col gap-6">
        <Card class="rounded-3xl border-2 border-white/90 shadow-sm">
            <CardContent class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Mijn gegevens"
                    description="Je naam en mailadres. We sturen de betaalgegevens en herinneringen naar dit adres."
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="name">Naam</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Voor- en achternaam"
                        />
                        <InputError class="mt-2" :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Mailadres</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="naam@voorbeeld.be"
                        />
                        <InputError class="mt-2" :message="errors.email" />
                    </div>

                    <div
                        v-if="
                            page.props.mustVerifyEmail &&
                            !user.email_verified_at
                        "
                    >
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Je mailadres is nog niet bevestigd.
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Stuur de bevestigingsmail opnieuw.
                            </Link>
                        </p>

                        <div
                            v-if="
                                page.props.status === 'verification-link-sent'
                            "
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            We hebben een nieuwe bevestigingslink naar je
                            mailadres gestuurd.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            class="rounded-full font-bold"
                            data-test="update-profile-button"
                            >Bewaren</Button
                        >
                    </div>
                </Form>
            </CardContent>
        </Card>

        <DeleteUser />
    </div>
</template>
