<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PromotionalBanner;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class PromotionalBannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $banners = [
            [
                'title' => 'Cool Comfort AC Offer',
                'description' => 'Save 15% on your first AC repair service. Use code FIRST50. Offer valid till May 30, 2025.',
                'status' => 'accepted',
                'banner_type' => 'service',
                'service_id' => 37,
                'provider_id' => 4,
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'duration' => 30,
                'charges' => 100,
                'total_amount' => 100,
                'payment_status' => 'paid',
                'payment_method' => 'stripe',
                'is_requested_banner' => 0,
                'banner_image' => public_path('/images/promotional-banner/15_on_ac_repaire.png'),
            ],
            [
                'title' => 'Paint Your Home for Less',
                'description' => 'Save 20% on your first wall painting service. Use code FIRST50. Offer valid till June 15, 2025.',
                'status' => 'accepted',
                'banner_type' => 'service',
                'service_id' => 75,
                'provider_id' => 4,
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'duration' => 30,
                'charges' => 100,
                'total_amount' => 100,
                'payment_status' => 'paid',
                'payment_method' => 'stripe',
                'is_requested_banner' => 0,
                'banner_image' => public_path('/images/promotional-banner/20_off_on_wall_painting.png'),
            ],
            [
                'title' => 'Sparkling Home Offer — 20% OFF',
                'description' => 'Save 20% on your first cleaning service. Use code FIRST50. Offer valid till June 15, 2025.',
                'status' => 'accepted',
                'banner_type' => 'service',
                'service_id' => 18,
                'provider_id' => 4,
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'duration' => 30,
                'charges' => 100,
                'total_amount' => 100,
                'payment_status' => 'paid',
                'payment_method' => 'stripe',
                'is_requested_banner' => 0,
                'banner_image' => public_path('/images/promotional-banner/20_off_on_cleaning.png'),
            ],
            [
                'title' => 'Flat 15% OFF on Plumbing Service',
                'description' => 'Flat 15% OFF on First Plumbing Service',
                'status' => 'accepted',
                'banner_type' => 'service',
                'service_id' => 101,
                'provider_id' => 4,
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'duration' => 30,
                'charges' => 100,
                'total_amount' => 100,
                'payment_status' => 'paid',
                'payment_method' => 'stripe',
                'is_requested_banner' => 0,
                'banner_image' => public_path('/images/promotional-banner/flat_15_off_on_first_plumbing.png'),
            ],
            [
                'title' => 'Electrical Safety Special — 20% OFF',
                'description' => 'Save 20% on electrical repairs and installations. Use code First50. Offer ends June 15, 2026.',
                'status' => 'accepted',
                'banner_type' => 'service',
                'service_id' => 106,
                'provider_id' => 4,
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'duration' => 30,
                'charges' => 100,
                'total_amount' => 100,
                'payment_status' => 'paid',
                'payment_method' => 'stripe',
                'is_requested_banner' => 0,
                'banner_image' => public_path('/images/promotional-banner/switchboard_wiring_installation_20_off.png'),
            ],
            [
                'title' => 'Carpenter Services Special — 20% OFF',
                'description' => 'Save 20% on expert carpenter services. Apply code FREE50. Offer valid till June 15, 2025.',
                'status' => 'accepted',
                'banner_type' => 'service',
                'service_id' => 20,
                'provider_id' => 4,
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'duration' => 30,
                'charges' => 100,
                'total_amount' => 100,
                'payment_status' => 'paid',
                'payment_method' => 'stripe',
                'is_requested_banner' => 0,
                'banner_image' => public_path('/images/promotional-banner/carpenter_services_20_off.png'),
            ],
        ];

        foreach ($banners as $val) {
            $bannerImage = $val['banner_image'] ?? null;
            $bannerData = Arr::except($val, ['banner_image']);

            $banner = PromotionalBanner::create($bannerData);

            // Store banner image using Media Library
            if (isset($bannerImage) && File::exists($bannerImage)) {
                $file = new \Illuminate\Http\File($bannerImage);
                $banner->addMedia($file)
                    ->preservingOriginal()
                    ->toMediaCollection('banner_attachment');
            }
        }
    }
}
