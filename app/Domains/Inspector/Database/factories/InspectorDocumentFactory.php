<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Database\factories;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Enums\DocumentType;
use App\Domains\Inspector\Models\InspectorDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InspectorDocument>
 */
class InspectorDocumentFactory extends Factory
{
    protected $model = InspectorDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userId = User::factory()->inspector();
        $randomFileName = Str::uuid()->toString().'.pdf';

        return [
            'user_id' => $userId,
            'type' => DocumentType::Ktp,
            'storage_path' => "inspector-documents/{$randomFileName}",
            'metadata' => [
                'original_filename' => 'dokumen.pdf',
                'mime_type' => 'application/pdf',
                'size' => 1024 * 500, // 500 KB
            ],
        ];
    }

    public function ktp(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DocumentType::Ktp,
        ]);
    }

    public function certificate(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DocumentType::Certificate,
        ]);
    }

    public function cv(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DocumentType::Cv,
        ]);
    }

    public function portfolio(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DocumentType::Portfolio,
        ]);
    }

    public function portfolioUrl(string $url = 'https://portfolio.example.com'): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DocumentType::Portfolio,
            'storage_path' => null,
            'metadata' => [
                'url' => $url,
            ],
        ]);
    }
}
