<?php

declare(strict_types=1);

use App\Domains\Result\Livewire\InspectionFormPage;

it('RES-T40 no public method shares its name with a public property (wire:click would silently do nothing)', function (): void {
    $reflection = new ReflectionClass(InspectionFormPage::class);

    $properties = array_map(
        fn (ReflectionProperty $property): string => $property->getName(),
        $reflection->getProperties(ReflectionProperty::IS_PUBLIC),
    );

    $methods = array_map(
        fn (ReflectionMethod $method): string => $method->getName(),
        $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
    );

    expect(array_intersect($properties, $methods))->toBe([]);
});

it('RES-T41 the submit button calls a method that exists on the component', function (): void {
    $view = (string) file_get_contents(app_path('Domains/Result/Resources/views/livewire/inspection-form-page.blade.php'));

    expect($view)->toContain('wire:click="openSubmitConfirmation"')
        ->and(method_exists(InspectionFormPage::class, 'openSubmitConfirmation'))->toBeTrue();
});
