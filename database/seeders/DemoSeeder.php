<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\DocumentType;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorDocument;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\FuelType;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Enums\Transmission;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Models\ListingInspectionSlot;
use App\Domains\Listing\Models\ListingPhoto;
use App\Domains\Payment\Actions\PostPayout;
use App\Domains\Payment\Actions\PostPurchasePayout;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Rating\Actions\RecalculateInspectorRating;
use App\Domains\Rating\Models\Rating;
use App\Domains\Result\DTOs\ScoreResult;
use App\Domains\Result\Enums\ItemRating;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\ResultItem;
use App\Domains\Result\Models\ResultPhoto;
use App\Domains\Result\Models\TemplateItem;
use App\Domains\Result\Services\ScoreCalculator;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Support\PurchaseBreakdown;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class DemoSeeder extends Seeder
{
    public const string DEMO_PASSWORD = 'Password123!';

    public function run(): void
    {
        if (app()->environment('production') || ! env('SEED_DEMO', true)) {
            return;
        }

        DB::transaction(function (): void {
            $users = $this->seedUsersAndProfiles();
            $listings = $this->seedListings($users);
            $jobs = $this->seedInspectionsAndResults($users, $listings);
            $this->seedTransactionsAndRatings($users, $listings, $jobs);
        });
    }

    /**
     * @return array<string, User>
     */
    private function seedUsersAndProfiles(): array
    {
        $hashedPassword = Hash::make(self::DEMO_PASSWORD);

        // 1. Admin Demo
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@demo.id'],
            [
                'name' => 'Admin Demo Pekanbaru',
                'phone' => '08117600001',
                'password' => $hashedPassword,
                'role' => UserRole::Admin,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
                'accepted_terms_at' => now(),
            ]
        );

        // 2. 3 Pengguna biasa (pembeli/penjual)
        $seller = User::query()->updateOrCreate(
            ['email' => 'penjual@demo.id'],
            [
                'name' => 'Budi Hartono (Penjual Demo)',
                'phone' => '08127600001',
                'password' => $hashedPassword,
                'role' => UserRole::User,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
                'accepted_terms_at' => now(),
            ]
        );

        $buyer = User::query()->updateOrCreate(
            ['email' => 'pembeli@demo.id'],
            [
                'name' => 'Siti Rahma (Pembeli Demo)',
                'phone' => '08127600002',
                'password' => $hashedPassword,
                'role' => UserRole::User,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
                'accepted_terms_at' => now(),
            ]
        );

        $user3 = User::query()->updateOrCreate(
            ['email' => 'user3@demo.id'],
            [
                'name' => 'Rian Pratama (Pengguna Demo)',
                'phone' => '08127600003',
                'password' => $hashedPassword,
                'role' => UserRole::User,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
                'accepted_terms_at' => now(),
            ]
        );

        // 3. 3 Inspektor (2 terverifikasi dan menyetujui, 1 menunggu verifikasi)
        $inspector1 = User::query()->updateOrCreate(
            ['email' => 'inspektor@demo.id'],
            [
                'name' => 'Ahmad Syahputra (Inspektor Mobil & Motor)',
                'phone' => '08137600001',
                'password' => $hashedPassword,
                'role' => UserRole::Inspector,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
                'accepted_terms_at' => now(),
            ]
        );

        InspectorProfile::query()->updateOrCreate(
            ['user_id' => $inspector1->id],
            [
                'vehicle_types' => ['car', 'motorcycle'],
                'experience_years' => 5,
                'verification_status' => VerificationStatus::Approved,
                'verified_by' => $admin->id,
                'verified_at' => now()->subMonths(6),
                'rating_avg' => 4.80,
                'rating_count' => 5,
                'completed_jobs' => 12,
            ]
        );

        $this->createInspectorDummyDoc($inspector1, DocumentType::Ktp);
        $this->createInspectorDummyDoc($inspector1, DocumentType::Certificate);

        $inspector2 = User::query()->updateOrCreate(
            ['email' => 'inspektor2@demo.id'],
            [
                'name' => 'Doni Saputra (Inspektor Mobil)',
                'phone' => '08137600002',
                'password' => $hashedPassword,
                'role' => UserRole::Inspector,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
                'accepted_terms_at' => now(),
            ]
        );

        InspectorProfile::query()->updateOrCreate(
            ['user_id' => $inspector2->id],
            [
                'vehicle_types' => ['car'],
                'experience_years' => 3,
                'verification_status' => VerificationStatus::Approved,
                'verified_by' => $admin->id,
                'verified_at' => now()->subMonths(3),
                'rating_avg' => 4.50,
                'rating_count' => 2,
                'completed_jobs' => 4,
            ]
        );

        $this->createInspectorDummyDoc($inspector2, DocumentType::Ktp);

        $inspector3 = User::query()->updateOrCreate(
            ['email' => 'inspektor_pending@demo.id'],
            [
                'name' => 'Eko Prasetyo (Menunggu Verifikasi)',
                'phone' => '08137600003',
                'password' => $hashedPassword,
                'role' => UserRole::Inspector,
                'status' => UserStatus::Pending,
                'email_verified_at' => now(),
                'accepted_terms_at' => null,
            ]
        );

        InspectorProfile::query()->updateOrCreate(
            ['user_id' => $inspector3->id],
            [
                'vehicle_types' => ['motorcycle'],
                'experience_years' => 2,
                'verification_status' => VerificationStatus::Pending,
                'verified_by' => null,
                'verified_at' => null,
                'rating_avg' => 0.00,
                'rating_count' => 0,
                'completed_jobs' => 0,
            ]
        );

        $this->createInspectorDummyDoc($inspector3, DocumentType::Ktp);
        $this->createInspectorDummyDoc($inspector3, DocumentType::Portfolio);

        $outUsers = [
            'admin' => $admin,
            'seller' => $seller,
            'buyer' => $buyer,
            'user3' => $user3,
            'inspector1' => $inspector1,
            'inspector2' => $inspector2,
            'inspector3' => $inspector3,
        ];

        return $outUsers;
    }

    /**
     * @param  array<string, User>  $users
     * @return array<string, Listing>
     */
    private function seedListings(array $users): array
    {
        $seller = $users['seller'];
        $user3 = $users['user3'];
        $admin = $users['admin'];

        $listingsData = [
            'avanza' => [
                'seller_id' => $seller->id,
                'vehicle_type' => VehicleType::Car,
                'title' => 'Toyota Avanza 1.3 G M/T 2019',
                'brand' => 'Toyota',
                'model' => 'Avanza',
                'year' => 2019,
                'price' => 165000000,
                'negotiable' => true,
                'odometer_km' => 58000,
                'transmission' => Transmission::Manual,
                'fuel' => FuelType::Petrol,
                'color' => 'Silver Metalik',
                'chassis_number' => 'MHKM1BA3JK0123456',
                'description' => 'Tangan pertama dari baru, service record bengkel resmi Toyota Pekanbaru, AC dingin, ban tebal.',
                'area_name' => 'Marpoyan Damai, Pekanbaru',
                'lat' => '0.4851000',
                'lng' => '101.4423000',
                'address_full' => 'Jl. Arifin Achmad No. 45, Marpoyan Damai',
                'inspection_place' => 'seller_home',
                'status' => ListingStatus::Active,
                'is_inspected' => true,
                'last_inspected_at' => now()->subDays(5),
                'validated_by' => $admin->id,
                'validated_at' => now()->subDays(20),
            ],
            'brio' => [
                'seller_id' => $seller->id,
                'vehicle_type' => VehicleType::Car,
                'title' => 'Honda Brio Satya E CVT 2021',
                'brand' => 'Honda',
                'model' => 'Brio',
                'year' => 2021,
                'price' => 155000000,
                'negotiable' => false,
                'odometer_km' => 28000,
                'transmission' => Transmission::Automatic,
                'fuel' => FuelType::Petrol,
                'color' => 'Taffeta White',
                'chassis_number' => 'MHRDD1850MK0987654',
                'description' => 'Kondisi mulus, pajak hidup panjang, interior wangi bersih, jarang pakai hanya rute kantor.',
                'area_name' => 'Sukajadi, Pekanbaru',
                'lat' => '0.5180000',
                'lng' => '101.4360000',
                'address_full' => 'Jl. KH Ahmad Dahlan No. 12, Sukajadi',
                'inspection_place' => 'seller_home',
                'status' => ListingStatus::Sold,
                'is_inspected' => true,
                'last_inspected_at' => now()->subDays(10),
                'validated_by' => $admin->id,
                'validated_at' => now()->subDays(25),
            ],
            'innova' => [
                'seller_id' => $user3->id,
                'vehicle_type' => VehicleType::Car,
                'title' => 'Toyota Kijang Innova Reborn 2.4 V Diesel A/T 2018',
                'brand' => 'Toyota',
                'model' => 'Innova Reborn',
                'year' => 2018,
                'price' => 325000000,
                'negotiable' => true,
                'odometer_km' => 85000,
                'transmission' => Transmission::Automatic,
                'fuel' => FuelType::Diesel,
                'color' => 'Attitude Black',
                'chassis_number' => 'MHKGUN40JK0456123',
                'description' => 'Mesin 2GD sehat kering, tarikan enteng responsif, jok kulit sudah dilapisi, rawatan prima.',
                'area_name' => 'Bukit Raya, Pekanbaru',
                'lat' => '0.4950000',
                'lng' => '101.4620000',
                'address_full' => 'Jl. Jenderal Sudirman No. 210, Bukit Raya',
                'inspection_place' => 'seller_home',
                'status' => ListingStatus::Active,
                'is_inspected' => true,
                'last_inspected_at' => now()->subDays(12),
                'validated_by' => $admin->id,
                'validated_at' => now()->subDays(30),
            ],
            'xpander' => [
                'seller_id' => $seller->id,
                'vehicle_type' => VehicleType::Car,
                'title' => 'Mitsubishi Xpander Ultimate A/T 2020',
                'brand' => 'Mitsubishi',
                'model' => 'Xpander',
                'year' => 2020,
                'price' => 220000000,
                'negotiable' => true,
                'odometer_km' => 45000,
                'transmission' => Transmission::Automatic,
                'fuel' => FuelType::Petrol,
                'color' => 'Titanium Grey',
                'chassis_number' => 'MMBNCW000LK0345678',
                'description' => 'Tipe tertinggi Ultimate, keyless entry, kamera mundur, kaca film Solar Gard.',
                'area_name' => 'Payung Sekaki, Pekanbaru',
                'lat' => '0.5280000',
                'lng' => '101.4120000',
                'address_full' => 'Jl. Riau Ujung No. 88, Payung Sekaki',
                'inspection_place' => 'workshop',
                'status' => ListingStatus::Rejected,
                'rejection_reason' => 'Hasil inspeksi menunjukkan ketidakcocokan nomor rangka mesin dengan dokumen.',
                'is_inspected' => true,
                'last_inspected_at' => now()->subDays(20),
                'validated_by' => $admin->id,
                'validated_at' => now()->subDays(35),
            ],
            'nmax' => [
                'seller_id' => $user3->id,
                'vehicle_type' => VehicleType::Motorcycle,
                'title' => 'Yamaha NMAX 155 Connected ABS 2022',
                'brand' => 'Yamaha',
                'model' => 'NMAX 155',
                'year' => 2022,
                'price' => 28500000,
                'negotiable' => true,
                'odometer_km' => 14000,
                'transmission' => Transmission::Automatic,
                'fuel' => FuelType::Petrol,
                'color' => 'Matte Black',
                'chassis_number' => 'MH3SG5620NJ0567891',
                'description' => 'Fitur Y-Connect normal, ABS aktif, ban Michelin baru ganti sebulan, kunci lengkap 2 buah.',
                'area_name' => 'Tampan, Pekanbaru',
                'lat' => '0.4650000',
                'lng' => '101.3980000',
                'address_full' => 'Jl. HR Soebrantas No. 77, Tampan',
                'inspection_place' => 'seller_home',
                'status' => ListingStatus::Inactive,
                'is_inspected' => true,
                'last_inspected_at' => now()->subDays(7),
                'validated_by' => $admin->id,
                'validated_at' => now()->subDays(40),
            ],
            'hrv' => [
                'seller_id' => $seller->id,
                'vehicle_type' => VehicleType::Car,
                'title' => 'Honda HR-V 1.5 E CVT 2019',
                'brand' => 'Honda',
                'model' => 'HR-V',
                'year' => 2019,
                'price' => 235000000,
                'negotiable' => true,
                'odometer_km' => 52000,
                'transmission' => Transmission::Automatic,
                'fuel' => FuelType::Petrol,
                'color' => 'Crystal Black Pearl',
                'chassis_number' => 'MHRRU1850KK0876543',
                'description' => 'Pajak on, paddle shift responsif, kaki-kaki senyap tanpa bunyi, siap pakai luar kota.',
                'area_name' => 'Senapelan, Pekanbaru',
                'lat' => '0.5350000',
                'lng' => '101.4460000',
                'address_full' => 'Jl. Ahmad Yani No. 56, Senapelan',
                'inspection_place' => 'seller_home',
                'status' => ListingStatus::InTransaction,
                'is_inspected' => false,
                'validated_by' => $admin->id,
                'validated_at' => now()->subDays(15),
            ],
            'ertiga' => [
                'seller_id' => $seller->id,
                'vehicle_type' => VehicleType::Car,
                'title' => 'Suzuki Ertiga GL M/T 2018',
                'brand' => 'Suzuki',
                'model' => 'Ertiga',
                'year' => 2018,
                'price' => 148000000,
                'negotiable' => true,
                'odometer_km' => 65000,
                'transmission' => Transmission::Manual,
                'fuel' => FuelType::Petrol,
                'color' => 'Pearl Snow White',
                'chassis_number' => 'MHKZE81S0JK0135790',
                'description' => 'Mobil keluarga irit dan nyaman, head unit upgrade Android, kabin lapang.',
                'area_name' => 'Tenayan Raya, Pekanbaru',
                'lat' => '0.5100000',
                'lng' => '101.4880000',
                'address_full' => 'Jl. Hangtuah No. 102, Tenayan Raya',
                'inspection_place' => 'seller_home',
                'status' => ListingStatus::InTransaction,
                'is_inspected' => false,
                'validated_by' => $admin->id,
                'validated_at' => now()->subDays(10),
            ],
            'jazz' => [
                'seller_id' => $user3->id,
                'vehicle_type' => VehicleType::Car,
                'title' => 'Honda Jazz RS CVT 2018',
                'brand' => 'Honda',
                'model' => 'Jazz',
                'year' => 2018,
                'price' => 195000000,
                'negotiable' => true,
                'odometer_km' => 70000,
                'transmission' => Transmission::Automatic,
                'fuel' => FuelType::Petrol,
                'color' => 'Phoenix Orange Pearl',
                'chassis_number' => 'MHRGK5880JK0246810',
                'description' => 'Full original pabrik, transmisi CVT halus responsif, lampu LED projector bening.',
                'area_name' => 'Bina Widya, Pekanbaru',
                'lat' => '0.4700000',
                'lng' => '101.3700000',
                'address_full' => 'Jl. Garuda Sakti Km 1, Bina Widya',
                'inspection_place' => 'seller_home',
                'status' => ListingStatus::InInspection,
                'is_inspected' => false,
                'validated_by' => $admin->id,
                'validated_at' => now()->subDays(8),
            ],
            'aerox' => [
                'seller_id' => $seller->id,
                'vehicle_type' => VehicleType::Motorcycle,
                'title' => 'Yamaha Aerox 155 VVA Connected 2022',
                'brand' => 'Yamaha',
                'model' => 'Aerox 155',
                'year' => 2022,
                'price' => 24000000,
                'negotiable' => true,
                'odometer_km' => 16000,
                'transmission' => Transmission::Automatic,
                'fuel' => FuelType::Petrol,
                'color' => 'Silver Cyan',
                'chassis_number' => 'MH3SG5920NJ0357913',
                'description' => 'Motor terawat, mesin halus standar pabrik, tarikan enteng, ban tebal siap pakai.',
                'area_name' => 'Rumbai, Pekanbaru',
                'lat' => '0.5600000',
                'lng' => '101.4350000',
                'address_full' => 'Jl. Yos Sudarso No. 34, Rumbai',
                'inspection_place' => 'seller_home',
                'status' => ListingStatus::InInspection,
                'is_inspected' => false,
                'validated_by' => $admin->id,
                'validated_at' => now()->subDays(6),
            ],
            'sigra' => [
                'seller_id' => $user3->id,
                'vehicle_type' => VehicleType::Car,
                'title' => 'Daihatsu Sigra 1.2 R M/T 2022',
                'brand' => 'Daihatsu',
                'model' => 'Sigra',
                'year' => 2022,
                'price' => 128000000,
                'negotiable' => true,
                'odometer_km' => 22000,
                'transmission' => Transmission::Manual,
                'fuel' => FuelType::Petrol,
                'color' => 'Red Solid',
                'chassis_number' => 'MHKB3BA3NK0579246',
                'description' => 'Irit BBM 7 penumpang, AC double blower dingin, kondisi masih seperti baru dari dealer.',
                'area_name' => 'Tuah Madani, Pekanbaru',
                'lat' => '0.4550000',
                'lng' => '101.3850000',
                'address_full' => 'Jl. Melati Indah No. 19, Tuah Madani',
                'inspection_place' => 'seller_home',
                'status' => ListingStatus::InInspection,
                'is_inspected' => false,
                'validated_by' => $admin->id,
                'validated_at' => now()->subDays(4),
            ],
            'terios' => [
                'seller_id' => $seller->id,
                'vehicle_type' => VehicleType::Car,
                'title' => 'Daihatsu Terios R Deluxe M/T 2019',
                'brand' => 'Daihatsu',
                'model' => 'Terios',
                'year' => 2019,
                'price' => 178000000,
                'negotiable' => true,
                'odometer_km' => 60000,
                'transmission' => Transmission::Manual,
                'fuel' => FuelType::Petrol,
                'color' => 'Icy White',
                'chassis_number' => 'MHKJ20000KK0468135',
                'description' => 'Ground clearance tinggi cocok kontur jalan Pekanbaru, kamera 360 aktif normal.',
                'area_name' => 'Marpoyan Damai, Pekanbaru',
                'lat' => '0.4890000',
                'lng' => '101.4480000',
                'address_full' => 'Jl. Paus No. 81, Marpoyan Damai',
                'inspection_place' => 'seller_home',
                'status' => ListingStatus::PendingReview,
                'is_inspected' => false,
            ],
            'beat' => [
                'seller_id' => $user3->id,
                'vehicle_type' => VehicleType::Motorcycle,
                'title' => 'Honda Beat Street CBS 2023',
                'brand' => 'Honda',
                'model' => 'Beat Street',
                'year' => 2023,
                'price' => 16000000,
                'negotiable' => true,
                'odometer_km' => 8000,
                'transmission' => Transmission::Automatic,
                'fuel' => FuelType::Petrol,
                'color' => 'Street Black Doff',
                'chassis_number' => 'MH1JM8110PK0681357',
                'description' => 'Stang telanjang gagah, mesin eSP halus dan irit luar biasa, surat-surat lengkap faktur ada.',
                'area_name' => 'Sukajadi, Pekanbaru',
                'lat' => '0.5120000',
                'lng' => '101.4390000',
                'address_full' => 'Jl. Melur No. 23, Sukajadi',
                'inspection_place' => 'seller_home',
                'status' => ListingStatus::PendingReview,
                'is_inspected' => false,
            ],
            'fortuner' => [
                'seller_id' => $seller->id,
                'vehicle_type' => VehicleType::Car,
                'title' => 'Toyota Fortuner 2.4 VRZ Diesel A/T 2020',
                'brand' => 'Toyota',
                'model' => 'Fortuner',
                'year' => 2020,
                'price' => 450000000,
                'negotiable' => true,
                'odometer_km' => 40000,
                'transmission' => Transmission::Automatic,
                'fuel' => FuelType::Diesel,
                'color' => 'Phantom Brown',
                'chassis_number' => 'MHKGUN50LK0792468',
                'description' => 'Power backdoor kick sensor normal, jok kulit rapi, servis rutin Nasmoco/Agung Toyota.',
                'area_name' => 'Bukit Raya, Pekanbaru',
                'lat' => '0.4980000',
                'lng' => '101.4550000',
                'address_full' => 'Jl. Harapan Raya No. 15, Bukit Raya',
                'inspection_place' => 'seller_home',
                'status' => ListingStatus::Active,
                'is_inspected' => false,
                'validated_by' => $admin->id,
                'validated_at' => now()->subDays(18),
            ],
            'pcx' => [
                'seller_id' => $seller->id,
                'vehicle_type' => VehicleType::Motorcycle,
                'title' => 'Honda PCX 160 CBS 2021',
                'brand' => 'Honda',
                'model' => 'PCX 160',
                'year' => 2021,
                'price' => 27000000,
                'negotiable' => true,
                'odometer_km' => 19000,
                'transmission' => Transmission::Automatic,
                'fuel' => FuelType::Petrol,
                'color' => 'Wonderful White',
                'chassis_number' => 'MH1KF7110MK0192837',
                'description' => 'Kunci remote smart key 2 pcs lengkap barcode, bagasi super luas, lampu all LED.',
                'area_name' => 'Payung Sekaki, Pekanbaru',
                'lat' => '0.5220000',
                'lng' => '101.4180000',
                'address_full' => 'Jl. Tuanku Tambusai No. 90, Payung Sekaki',
                'inspection_place' => 'seller_home',
                'status' => ListingStatus::Active,
                'is_inspected' => false,
                'validated_by' => $admin->id,
                'validated_at' => now()->subDays(14),
            ],
            'vario' => [
                'seller_id' => $user3->id,
                'vehicle_type' => VehicleType::Motorcycle,
                'title' => 'Honda Vario 160 CBS 2023',
                'brand' => 'Honda',
                'model' => 'Vario 160',
                'year' => 2023,
                'price' => 23500000,
                'negotiable' => false,
                'odometer_km' => 9000,
                'transmission' => Transmission::Automatic,
                'fuel' => FuelType::Petrol,
                'color' => 'Grande Matte Red',
                'chassis_number' => 'MH1KF8110PK0472918',
                'description' => 'Pemakaian apik km rendah, bodi mulus tanpa baret jatuh, tarikan kencang responsif.',
                'area_name' => 'Senapelan, Pekanbaru',
                'lat' => '0.5310000',
                'lng' => '101.4420000',
                'address_full' => 'Jl. Riau No. 40, Senapelan',
                'inspection_place' => 'seller_home',
                'status' => ListingStatus::Active,
                'is_inspected' => false,
                'validated_by' => $admin->id,
                'validated_at' => now()->subDays(11),
            ],
            'scoopy' => [
                'seller_id' => $seller->id,
                'vehicle_type' => VehicleType::Motorcycle,
                'title' => 'Honda Scoopy Prestige 2022',
                'brand' => 'Honda',
                'model' => 'Scoopy',
                'year' => 2022,
                'price' => 19500000,
                'negotiable' => true,
                'odometer_km' => 12000,
                'transmission' => Transmission::Automatic,
                'fuel' => FuelType::Petrol,
                'color' => 'Prestige Green',
                'chassis_number' => 'MH1JM3120NK0839201',
                'description' => 'Tipe Prestige velg emas elegan, keyless, charger HP berfungsi baik, helm bawaan ada.',
                'area_name' => 'Tampan, Pekanbaru',
                'lat' => '0.4620000',
                'lng' => '101.4020000',
                'address_full' => 'Jl. Suka Karya No. 11, Tampan',
                'inspection_place' => 'seller_home',
                'status' => ListingStatus::Active,
                'is_inspected' => false,
                'validated_by' => $admin->id,
                'validated_at' => now()->subDays(9),
            ],
            'filano' => [
                'seller_id' => $user3->id,
                'vehicle_type' => VehicleType::Motorcycle,
                'title' => 'Yamaha Grand Filano Hybrid 2023',
                'brand' => 'Yamaha',
                'model' => 'Grand Filano',
                'year' => 2023,
                'price' => 24500000,
                'negotiable' => true,
                'odometer_km' => 7500,
                'transmission' => Transmission::Automatic,
                'fuel' => FuelType::Petrol,
                'color' => 'White Pearl',
                'chassis_number' => 'MH3SG6110PK0928374',
                'description' => 'Classy Yamaha, speedometer full TFT layar warna, bagasi besar lampu LED bagasi menyala.',
                'area_name' => 'Bina Widya, Pekanbaru',
                'lat' => '0.4680000',
                'lng' => '101.3780000',
                'address_full' => 'Jl. Manyar Sakti No. 5, Bina Widya',
                'inspection_place' => 'seller_home',
                'status' => ListingStatus::Active,
                'is_inspected' => false,
                'validated_by' => $admin->id,
                'validated_at' => now()->subDays(5),
            ],
        ];

        foreach ($listingsData as $key => $data) {
            /** @var Listing $listing */
            $listing = Listing::query()->create($data);

            // Buat foto placeholder berwarna dengan GD
            $this->createListingPhotos($listing);

            // Buat slot inspeksi
            ListingInspectionSlot::query()->create([
                'listing_id' => $listing->id,
                'slot_start' => now()->addDays(2)->setHour(10)->setMinute(0)->setSecond(0),
                'note' => 'Bisa dihubungi 1 jam sebelum tiba',
            ]);
            ListingInspectionSlot::query()->create([
                'listing_id' => $listing->id,
                'slot_start' => now()->addDays(3)->setHour(14)->setMinute(0)->setSecond(0),
                'note' => 'Siang setelah jam makan siang',
            ]);

            $outListings[$key] = $listing;
        }

        return $outListings;
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Listing>  $listings
     * @return array<string, InspectionJob>
     */
    private function seedInspectionsAndResults(array $users, array $listings): array
    {
        $seller = $users['seller'];
        $inspector1 = $users['inspector1'];
        $inspector2 = $users['inspector2'];

        /** @var InspectionTemplate $carTemplate */
        $carTemplate = InspectionTemplate::query()
            ->where('vehicle_type', VehicleType::Car->value)
            ->where('is_active', true)
            ->firstOrFail();

        /** @var InspectionTemplate $motorTemplate */
        $motorTemplate = InspectionTemplate::query()
            ->where('vehicle_type', VehicleType::Motorcycle->value)
            ->where('is_active', true)
            ->firstOrFail();

        // 1. Result 1: Avanza (Layak / Recommended, Score 92.5)
        $outJobs['avanza'] = $this->createInspectionResultSetup(
            listing: $listings['avanza'],
            requester: $seller,
            inspector: $inspector1,
            template: $carTemplate,
            desiredRecommendation: 'recommended',
            feeAmount: 50000,
            completedDaysAgo: 5
        );

        // 2. Result 2: Brio (Layak / Recommended, Score 85.0)
        $outJobs['brio'] = $this->createInspectionResultSetup(
            listing: $listings['brio'],
            requester: $seller,
            inspector: $inspector1,
            template: $carTemplate,
            desiredRecommendation: 'recommended',
            feeAmount: 50000,
            completedDaysAgo: 10
        );

        // 3. Result 3: Innova (Perlu Perhatian / Caution, Score 72.0)
        $outJobs['innova'] = $this->createInspectionResultSetup(
            listing: $listings['innova'],
            requester: $users['user3'],
            inspector: $inspector2,
            template: $carTemplate,
            desiredRecommendation: 'caution',
            feeAmount: 50000,
            completedDaysAgo: 12
        );

        // 4. Result 4: Xpander (Tidak Direkomendasikan / NotRecommended, Score 45.0)
        $outJobs['xpander'] = $this->createInspectionResultSetup(
            listing: $listings['xpander'],
            requester: $seller,
            inspector: $inspector1,
            template: $carTemplate,
            desiredRecommendation: 'not_recommended',
            feeAmount: 50000,
            completedDaysAgo: 20
        );

        // 5. Result 5: NMAX (Layak / Recommended, Score 88.0)
        $outJobs['nmax'] = $this->createInspectionResultSetup(
            listing: $listings['nmax'],
            requester: $users['user3'],
            inspector: $inspector1,
            template: $motorTemplate,
            desiredRecommendation: 'recommended',
            feeAmount: 15000,
            completedDaysAgo: 7
        );

        // 6. 2 Job Terbuka (status paid, inspector_id null)
        // Job A: Jazz (Car)
        $jazzJob = InspectionJob::query()->create([
            'listing_id' => $listings['jazz']->id,
            'requester_id' => $listings['jazz']->seller_id,
            'inspector_id' => null,
            'status' => JobStatus::Paid,
            'fee_amount' => 50000,
            'platform_fee_pct' => 10,
            'available_since' => now()->subHours(8),
            'expires_at' => now()->addDays(7),
        ]);
        Payment::query()->create([
            'payer_id' => $listings['jazz']->seller_id,
            'purpose' => PaymentPurpose::Inspection,
            'ref_id' => $jazzJob->id,
            'amount' => 50000,
            'status' => PaymentStatus::Success,
            'method' => PaymentMethod::Qris,
            'idempotency_key' => (string) Str::uuid(),
            'paid_at' => now()->subHours(8),
        ]);
        $outJobs['jazz'] = $jazzJob;

        // Job B: Aerox (Motorcycle)
        $aeroxJob = InspectionJob::query()->create([
            'listing_id' => $listings['aerox']->id,
            'requester_id' => $listings['aerox']->seller_id,
            'inspector_id' => null,
            'status' => JobStatus::Paid,
            'fee_amount' => 15000,
            'platform_fee_pct' => 10,
            'available_since' => now()->subHours(5),
            'expires_at' => now()->addDays(7),
        ]);
        Payment::query()->create([
            'payer_id' => $listings['aerox']->seller_id,
            'purpose' => PaymentPurpose::Inspection,
            'ref_id' => $aeroxJob->id,
            'amount' => 15000,
            'status' => PaymentStatus::Success,
            'method' => PaymentMethod::Qris,
            'idempotency_key' => (string) Str::uuid(),
            'paid_at' => now()->subHours(5),
        ]);
        $outJobs['aerox'] = $aeroxJob;

        // 7. 1 Job Berjalan (in_progress)
        $sigraJob = InspectionJob::query()->create([
            'listing_id' => $listings['sigra']->id,
            'requester_id' => $listings['sigra']->seller_id,
            'inspector_id' => $inspector1->id,
            'status' => JobStatus::InProgress,
            'fee_amount' => 50000,
            'platform_fee_pct' => 10,
            'available_since' => now()->subDays(1),
            'taken_at' => now()->subHours(4),
            'scheduled_at' => now()->addHours(2),
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->addDays(6),
        ]);
        Payment::query()->create([
            'payer_id' => $listings['sigra']->seller_id,
            'purpose' => PaymentPurpose::Inspection,
            'ref_id' => $sigraJob->id,
            'amount' => 50000,
            'status' => PaymentStatus::Success,
            'method' => PaymentMethod::Qris,
            'idempotency_key' => (string) Str::uuid(),
            'paid_at' => now()->subDays(1),
        ]);
        $outJobs['sigra'] = $sigraJob;

        return $outJobs;
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Listing>  $listings
     * @param  array<string, InspectionJob>  $jobs
     */
    private function seedTransactionsAndRatings(array $users, array $listings, array $jobs): void
    {
        $seller = $users['seller'];
        $buyer = $users['buyer'];
        $inspector1 = $users['inspector1'];

        // 1. Transaksi Offered (Ertiga)
        Transaction::query()->create([
            'listing_id' => $listings['ertiga']->id,
            'buyer_id' => $buyer->id,
            'inspector_id' => null,
            'status' => TransactionStatus::Offered,
            'price_amount' => 145000000,
            'commission_pct' => null,
            'notes' => 'Nego tipis harga cash pembeli langsung Pekanbaru',
            'expires_at' => now()->addHours(36),
        ]);

        // 2. Transaksi PaidHeld (HR-V)
        $txPaidHeld = Transaction::query()->create([
            'listing_id' => $listings['hrv']->id,
            'buyer_id' => $buyer->id,
            'inspector_id' => $inspector1->id,
            'status' => TransactionStatus::PaidHeld,
            'price_amount' => 230000000,
            'commission_pct' => 3,
            'notes' => 'Pembayaran lunas simulasi, menunggu jadwal serah terima unit di rumah penjual',
        ]);

        $paymentHeld = Payment::query()->create([
            'payer_id' => $buyer->id,
            'purpose' => PaymentPurpose::Purchase,
            'ref_id' => $txPaidHeld->id,
            'amount' => 230000000,
            'status' => PaymentStatus::Success,
            'method' => PaymentMethod::Qris,
            'idempotency_key' => (string) Str::uuid(),
            'paid_at' => now()->subHours(12),
        ]);
        $txPaidHeld->update(['payment_id' => $paymentHeld->id]);

        // 3. Transaksi Completed dengan Rating (Brio)
        $txCompleted = Transaction::query()->create([
            'listing_id' => $listings['brio']->id,
            'buyer_id' => $buyer->id,
            'inspector_id' => $inspector1->id,
            'status' => TransactionStatus::Completed,
            'price_amount' => 150000000,
            'commission_pct' => 3,
            'notes' => 'Transaksi selesai dan serah terima unit sukses',
            'completed_at' => now()->subDays(2),
        ]);

        $paymentCompleted = Payment::query()->create([
            'payer_id' => $buyer->id,
            'purpose' => PaymentPurpose::Purchase,
            'ref_id' => $txCompleted->id,
            'amount' => 150000000,
            'status' => PaymentStatus::Success,
            'method' => PaymentMethod::Qris,
            'idempotency_key' => (string) Str::uuid(),
            'paid_at' => now()->subDays(4),
        ]);
        $txCompleted->update(['payment_id' => $paymentCompleted->id]);

        // Hitung komisi transaksi 3% dan posting ledger payout lewat Action PostPurchasePayout
        $breakdown = PurchaseBreakdown::calculate(150000000, 3, 10);
        $txCompleted->update([
            'inspector_commission' => $breakdown['inspector'],
            'platform_commission' => $breakdown['platform'],
        ]);

        app(PostPurchasePayout::class)->handle(
            payment: $paymentCompleted,
            saleCommission: $breakdown['commission'],
            inspectorCommission: $breakdown['inspector'],
            inspectorUserId: $inspector1->id
        );

        // Buat Rating untuk transaksi selesai (RTG-1)
        Rating::query()->create([
            'transaction_id' => $txCompleted->id,
            'inspector_id' => $inspector1->id,
            'buyer_id' => $buyer->id,
            'score' => 5,
            'comment' => 'Inspeksi sangat teliti dan penjelasannya sangat jelas, hasil inspeksi akurat membantu proses transaksi.',
            'is_hidden' => false,
        ]);

        // Hitung ulang rating inspektor
        app(RecalculateInspectorRating::class)->handle($inspector1->id);
        app(RecalculateInspectorRating::class)->handle($users['inspector2']->id);
    }

    private function createInspectionResultSetup(
        Listing $listing,
        User $requester,
        User $inspector,
        InspectionTemplate $template,
        string $desiredRecommendation,
        int $feeAmount,
        int $completedDaysAgo,
    ): InspectionJob {
        $completedAt = now()->subDays($completedDaysAgo);

        /** @var InspectionJob $job */
        $job = InspectionJob::query()->create([
            'listing_id' => $listing->id,
            'requester_id' => $requester->id,
            'inspector_id' => $inspector->id,
            'status' => JobStatus::Completed,
            'fee_amount' => $feeAmount,
            'platform_fee_pct' => 10,
            'available_since' => $completedAt->copy()->subDays(2),
            'taken_at' => $completedAt->copy()->subDays(2),
            'scheduled_at' => $completedAt->copy()->subDay(),
            'started_at' => $completedAt->copy()->subDay(),
            'submitted_at' => $completedAt->copy()->subHours(3),
            'completed_at' => $completedAt,
            'expires_at' => $completedAt->copy()->addDays(7),
        ]);

        // Pembayaran Job Inspeksi
        $payment = Payment::query()->create([
            'payer_id' => $requester->id,
            'purpose' => PaymentPurpose::Inspection,
            'ref_id' => $job->id,
            'amount' => $feeAmount,
            'status' => PaymentStatus::Success,
            'method' => PaymentMethod::Qris,
            'idempotency_key' => (string) Str::uuid(),
            'paid_at' => $completedAt->copy()->subDays(2),
        ]);

        // Payout ke Ledger via Action PostPayout
        app(PostPayout::class)->handle(
            payment: $payment,
            inspectorId: $inspector->id,
            creditType: LedgerEntryType::InspectionFee,
            platformFeePct: 10
        );

        // Buat InspectionResult
        /** @var InspectionResult $result */
        $result = InspectionResult::query()->create([
            'job_id' => $job->id,
            'listing_id' => $listing->id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
            'status' => ResultStatus::Submitted,
            'summary_note' => "Hasil inspeksi lengkap untuk {$listing->title}. Pemeriksaan menyeluruh pada dokumen, bodi, mesin, dan kelistrikan.",
            'submitted_at' => $completedAt->copy()->subHours(3),
        ]);

        // Isi item inspeksi
        $itemsData = [];
        $templateItems = TemplateItem::query()->where('template_id', $template->id)->get();

        foreach ($templateItems as $tItem) {
            $rating = match ($desiredRecommendation) {
                'recommended' => ItemRating::Good,
                'caution' => $tItem->is_critical ? ItemRating::Good : ($tItem->sort_order % 2 === 0 ? ItemRating::Fair : ItemRating::Good),
                'not_recommended' => $tItem->is_critical ? ItemRating::Poor : ItemRating::Fair,
                default => ItemRating::Good,
            };

            $note = match ($rating) {
                ItemRating::Good => 'Kondisi sangat baik dan terawat',
                ItemRating::Fair => 'Ada sedikit keausan wajar pemakaian',
                ItemRating::Poor => 'Kondisi bermasalah, perlu perbaikan segera',
            };

            $createdItems[] = ResultItem::query()->create([
                'result_id' => $result->id,
                'template_item_id' => $tItem->id,
                'rating' => $rating,
                'note' => $note,
            ]);

            $itemsData[] = [
                'weight' => $tItem->weight,
                'rating' => $rating,
                'is_critical' => $tItem->is_critical,
            ];
        }

        // Hitung skor via ScoreCalculator
        $calculator = app(ScoreCalculator::class);
        /** @var ScoreResult $scoreResult */
        $scoreResult = $calculator->calculate($itemsData);

        $result->update([
            'total_score' => $scoreResult->totalScore,
            'recommendation' => $scoreResult->recommendation,
        ]);

        // Buat Foto Placeholder Hasil Inspeksi
        $this->createResultPhotos($result, $createdItems);

        return $job;
    }

    private function createListingPhotos(Listing $listing): void
    {
        $colors = [
            [30, 41, 59],    // Slate-800 netral untuk cover utama
            [71, 85, 105],   // Slate-600 netral
            [100, 116, 139], // Slate-500 netral
        ];

        for ($i = 0; $i < 3; $i++) {
            $imageBinary = $this->generatePlaceholderImage(
                text: "{$listing->brand} {$listing->model} (".($i === 0 ? 'Utama' : 'Foto '.($i + 1)).')',
                width: 600,
                height: 450,
                rgb: $colors[$i]
            );

            $path = "listings/{$listing->id}/photo_{$i}.jpg";
            Storage::disk('private')->put($path, $imageBinary);
            Storage::disk('public')->put($path, $imageBinary);

            ListingPhoto::query()->create([
                'listing_id' => $listing->id,
                'storage_path' => $path,
                'is_cover' => ($i === 0),
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * @param  array<ResultItem>  $items
     */
    private function createResultPhotos(InspectionResult $result, array $items): void
    {
        $captions = [
            'Foto Odometer dan Speedometer',
            'Kondisi Ruang Mesin',
            'Kondisi Bodi dan Kolong Kendaraan',
        ];

        foreach (array_slice($items, 0, 3) as $index => $item) {
            $caption = $captions[$index] ?? "Foto Item {$index}";
            $imageBinary = $this->generatePlaceholderImage(
                text: $caption,
                width: 600,
                height: 450,
                rgb: [51, 65, 85]
            );

            $path = "results/{$result->id}/inspect_{$item->id}_{$index}.jpg";
            Storage::disk('private')->put($path, $imageBinary);

            ResultPhoto::query()->create([
                'result_id' => $result->id,
                'result_item_id' => $item->id,
                'storage_path' => $path,
                'captured_at' => now()->subHours(4),
                'stamped_at' => now()->subHours(3),
                'is_camera_capture' => true,
            ]);
        }
    }

    private function createInspectorDummyDoc(User $user, DocumentType $type): void
    {
        $imageBinary = $this->generatePlaceholderImage(
            text: "Dokumen {$type->value} - {$user->name}",
            width: 400,
            height: 300,
            rgb: [71, 85, 105]
        );

        $path = "inspectors/{$user->id}/{$type->value}.jpg";
        Storage::disk('private')->put($path, $imageBinary);

        InspectorDocument::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'storage_path' => $path,
        ]);
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function generatePlaceholderImage(string $text, int $width, int $height, array $rgb): string
    {
        $img = imagecreatetruecolor($width, $height);
        if ($img === false) {
            return '';
        }

        $bg = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
        $fg = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 0, $width, $height, $bg);

        // Garis bingkai aksen netral
        $border = imagecolorallocate($img, 203, 213, 225); // slate-300
        imagerectangle($img, 4, 4, $width - 5, $height - 5, $border);

        $font = 5;
        $tw = imagefontwidth($font) * strlen($text);
        $th = imagefontheight($font);
        $x = (int) max(10, ($width - $tw) / 2);
        $y = (int) max(10, ($height - $th) / 2);
        imagestring($img, $font, $x, $y, $text, $fg);

        ob_start();
        imagejpeg($img, null, 80);
        $binary = (string) ob_get_clean();
        imagedestroy($img);

        return $binary;
    }
}
