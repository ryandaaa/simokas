<?php

declare(strict_types=1);

namespace App\Domains\Admin\Livewire;

use App\Domains\Admin\Actions\RecordAuditLog;
use App\Domains\Setting\Contracts\SettingsReader;
use App\Domains\Setting\Contracts\UpdatesSettings;
use App\Domains\Setting\DTOs\SettingsData;
use App\Domains\Setting\Enums\SettingKey;
use App\Domains\Setting\Exceptions\InvalidSettingValueException;
use App\Domains\Setting\Models\AppSetting;
use App\Support\Clock;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class Settings extends Component
{
    /**
     * @var array<string, int|string>
     */
    public array $form = [
        'inspection_fee_car' => 50000,
        'inspection_fee_motorcycle' => 15000,
        'platform_fee_pct' => 10,
        'sale_commission_pct' => 3,
        'auto_accept_days' => 3,
        'paid_job_timeout_days' => 7,
    ];

    public bool $showConfirmModal = false;

    public ?string $updatedByName = null;

    public ?string $updatedAtFormatted = null;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(SettingsReader $reader): void
    {
        $this->authorize('update', AppSetting::class);

        $this->loadSettingsData($reader);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'form.inspection_fee_car' => ['required', 'integer', 'min:1'],
            'form.inspection_fee_motorcycle' => ['required', 'integer', 'min:1'],
            'form.platform_fee_pct' => ['required', 'integer', 'min:0', 'max:100'],
            'form.sale_commission_pct' => ['required', 'integer', 'min:0', 'max:100'],
            'form.auto_accept_days' => ['required', 'integer', 'min:1'],
            'form.paid_job_timeout_days' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'form.inspection_fee_car' => SettingKey::InspectionFeeCar->label(),
            'form.inspection_fee_motorcycle' => SettingKey::InspectionFeeMotorcycle->label(),
            'form.platform_fee_pct' => SettingKey::PlatformFeePct->label(),
            'form.sale_commission_pct' => SettingKey::SaleCommissionPct->label(),
            'form.auto_accept_days' => SettingKey::AutoAcceptDays->label(),
            'form.paid_job_timeout_days' => SettingKey::PaidJobTimeoutDays->label(),
        ];
    }

    public function confirmSave(): void
    {
        $this->authorize('update', AppSetting::class);
        $this->resetAlerts();
        $this->validate();

        $this->showConfirmModal = true;
        $this->dispatch('open-modal', 'confirm-save-settings');
    }

    public function closeConfirmModal(): void
    {
        $this->showConfirmModal = false;
        $this->dispatch('close-modal', 'confirm-save-settings');
    }

    public function save(UpdatesSettings $updatesSettings, RecordAuditLog $recordAuditLog): mixed
    {
        $this->authorize('update', AppSetting::class);
        $this->resetAlerts();
        $this->validate();

        try {
            $updatesSettings->handle($this->form, Auth::user());

            $recordAuditLog->handle(
                actor: Auth::user(),
                action: 'update_settings',
                entity: 'app_settings',
                entityId: null,
                meta: [
                    'form' => $this->form,
                ],
            );

            $this->closeConfirmModal();
            session()->flash('success', __('admin.settings.success'));

            return $this->redirect(route('dashboard'), navigate: true);
        } catch (InvalidSettingValueException $e) {
            $this->closeConfirmModal();
            $this->errorMessage = $e->getMessage();

            return null;
        }
    }

    public function render(): View
    {
        return view('admin::livewire.settings')
            ->layout('components.layouts.app', ['title' => __('admin.settings.title')]);
    }

    private function loadSettingsData(SettingsReader $reader): void
    {
        /** @var SettingsData $data */
        $data = $reader->all();

        $this->form = [
            'inspection_fee_car' => $data->inspectionFeeCar,
            'inspection_fee_motorcycle' => $data->inspectionFeeMotorcycle,
            'platform_fee_pct' => $data->platformFeePct,
            'sale_commission_pct' => $data->saleCommissionPct,
            'auto_accept_days' => $data->autoAcceptDays,
            'paid_job_timeout_days' => $data->paidJobTimeoutDays,
        ];

        $this->updatedByName = $data->updatedByName;
        $this->updatedAtFormatted = $data->updatedAt !== null
            ? Clock::formatWib($data->updatedAt)
            : null;
    }

    private function resetAlerts(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
    }
}
