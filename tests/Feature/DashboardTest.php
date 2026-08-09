<?php

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('admins are sent from the dashboard to the admin area', function () {
    $this->actingAs(admin());

    $this->get(route('dashboard'))
        ->assertRedirect(route('admin.contributions.index'));
});

test('donors are sent from the dashboard to their contributions', function () {
    $this->actingAs(donor());

    $this->get(route('dashboard'))
        ->assertRedirect(route('account.contributions'));
});
