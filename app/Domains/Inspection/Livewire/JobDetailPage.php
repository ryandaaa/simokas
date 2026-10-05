<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Livewire;

use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class JobDetailPage extends Component
{
    public InspectionJob $job;

    public ?Listing $listing = null;

    public function mount(InspectionJob $job): void
    {
        // Otorisasi: hanya inspektor pengambil dan admin yang berhak (JOB-R5, JOB-T06)
        Gate::authorize('viewDetail', $job);

        $this->job = $job;

        // Query data persis dijalankan HANYA setelah otorisasi lolos
        $this->listing = Listing::query()
            ->select([
                'id',
                'seller_id',
                'vehicle_type',
                'brand',
                'model',
                'year',
                'area_name',
                'address_full',
                'lat',
                'lng',
                'chassis_number',
                'inspection_place',
                'color',
                'transmission',
                'fuel',
            ])
            ->whereKey($job->listing_id)
            ->first();
    }

    public function render(): View
    {
        return view('inspection::livewire.job-detail-page', [
            'job' => $this->job,
            'listing' => $this->listing,
        ])->layout('components.layouts.app', ['title' => __('inspection.detail.title')]);
    }
}
