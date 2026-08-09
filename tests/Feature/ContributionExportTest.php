<?php

use App\Models\Contribution;

test('the export downloads a csv with all contributions', function () {
    $this->actingAs(admin());

    $contribution = Contribution::factory()->create([
        'amount' => 2500,
        'together_with' => 'Marcel',
    ]);

    $response = $this->get(route('admin.contributions.export'));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain($contribution->name)
        ->and($csv)->toContain('Marcel')
        ->and($csv)->toContain('25,00');
});

test('donors cannot download the export', function () {
    $this->actingAs(donor())
        ->get(route('admin.contributions.export'))
        ->assertForbidden();
});
