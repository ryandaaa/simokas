<?php

declare(strict_types=1);

namespace App\Domains\Result\Providers;

use App\Domains\Payment\Events\PaymentSucceeded;
use App\Domains\Result\Actions\UpdateTemplateItems;
use App\Domains\Result\Contracts\TemplateEditor;
use App\Domains\Result\Listeners\GrantAccessOnPaymentSuccess;
use App\Domains\Result\Livewire\InspectionFormPage;
use App\Domains\Result\Livewire\ViewResultPage;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Policies\InspectionResultPolicy;
use App\Domains\Result\Policies\InspectionTemplatePolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class ResultServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TemplateEditor::class, UpdateTemplateItems::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'result');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        Gate::policy(InspectionResult::class, InspectionResultPolicy::class);
        Gate::policy(InspectionTemplate::class, InspectionTemplatePolicy::class);
        Gate::define('fill-inspection', [InspectionResultPolicy::class, 'fill']);

        Event::listen(PaymentSucceeded::class, GrantAccessOnPaymentSuccess::class);

        Livewire::component('result.inspection-form-page', InspectionFormPage::class);
        Livewire::component('result.view-result-page', ViewResultPage::class);
    }
}
