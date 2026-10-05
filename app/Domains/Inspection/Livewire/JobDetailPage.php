<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Livewire;

use App\Domains\Inspection\Actions\CancelJobByInspector;
use App\Domains\Inspection\Actions\ScheduleJob;
use App\Domains\Inspection\Actions\StartInspection;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use App\Support\Clock;
use App\Support\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Throwable;

final class JobDetailPage extends Component
{
    public InspectionJob $job;

    public ?Listing $listing = null;

    public string $scheduled_date = '';

    public string $scheduled_time = '';

    public string $cancel_reason = '';

    public ?string $actionError = null;

    public ?string $actionSuccess = null;

    public function mount(InspectionJob $job): void
    {
        // Otorisasi: hanya inspektor pengambil dan admin yang berhak (JOB-R5, JOB-T06)
        Gate::authorize('viewDetail', $job);

        $this->job = $job;

        if ($job->scheduled_at) {
            $wib = Clock::toWib($job->scheduled_at);
            $this->scheduled_date = $wib->format('Y-m-d');
            $this->scheduled_time = $wib->format('H:i');
        }

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

    public function saveSchedule(ScheduleJob $scheduleJob): void
    {
        Gate::authorize('schedule', $this->job);

        $this->validate([
            'scheduled_date' => ['required', 'date_format:Y-m-d'],
            'scheduled_time' => ['required', 'date_format:H:i'],
        ], [
            'scheduled_date.required' => 'Tanggal inspeksi wajib diisi.',
            'scheduled_date.date_format' => 'Format tanggal tidak valid.',
            'scheduled_time.required' => 'Waktu inspeksi wajib diisi.',
            'scheduled_time.date_format' => 'Format waktu tidak valid.',
        ]);

        try {
            $carbonWib = Carbon::createFromFormat(
                'Y-m-d H:i',
                "{$this->scheduled_date} {$this->scheduled_time}",
                Clock::DISPLAY_TIMEZONE
            );

            $carbonUtc = $carbonWib->copy()->setTimezone(Clock::TIMEZONE);

            if ($carbonUtc->isPast() || $carbonUtc->equalTo(now())) {
                $this->addError('scheduled_time', __('inspection.errors.schedule_must_be_in_future'));

                return;
            }
        } catch (Throwable) {
            $this->addError('scheduled_date', 'Tanggal atau waktu tidak valid.');

            return;
        }

        try {
            $this->actionError = null;
            $this->job = $scheduleJob->handle(Auth::user(), $this->job, $carbonUtc);
            $this->actionSuccess = __('inspection.detail.schedule_success');
        } catch (DomainException $e) {
            $this->actionError = $e->getMessage();
            $this->job->refresh();
        }
    }

    public function startInspection(StartInspection $startInspection): void
    {
        Gate::authorize('start', $this->job);

        try {
            $this->actionError = null;
            $this->job = $startInspection->handle(Auth::user(), $this->job);
            $this->actionSuccess = __('inspection.detail.start_success');
        } catch (DomainException $e) {
            $this->actionError = $e->getMessage();
            $this->job->refresh();
        }
    }

    public function cancelJob(CancelJobByInspector $cancelJob): void
    {
        Gate::authorize('cancelAsInspector', $this->job);

        $this->validate([
            'cancel_reason' => ['required', 'string', 'min:5'],
        ], [
            'cancel_reason.required' => __('inspection.errors.cancel_reason_required'),
            'cancel_reason.min' => 'Alasan pembatalan minimal 5 karakter.',
        ]);

        try {
            $this->actionError = null;
            $this->job = $cancelJob->handle(Auth::user(), $this->job, $this->cancel_reason);
            session()->flash('success', __('inspection.detail.inspector_cancel_success'));
            $this->redirect(route('jobs.inspector'), navigate: true);
        } catch (DomainException $e) {
            $this->actionError = $e->getMessage();
            $this->job->refresh();
            $this->dispatch('close-modal', 'inspector-cancel-modal');
        }
    }

    public function render(): View
    {
        return view('inspection::livewire.job-detail-page', [
            'job' => $this->job,
            'listing' => $this->listing,
        ])->layout('components.layouts.app', ['title' => __('inspection.detail.title')]);
    }
}
