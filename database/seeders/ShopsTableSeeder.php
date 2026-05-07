<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shop;
use App\Models\Service;
use App\Models\ShopHour;

class ShopsTableSeeder extends Seeder
{
    public function run(): void
    {
        $days = [
            ['day' => 'monday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'tuesday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'wednesday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'thursday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'friday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'saturday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'sunday', 'start_time' => '00:00:00', 'end_time' => '00:00:00', 'is_holiday' => true, 'breaks' => []],
        ];
        $shops = [
            [
                'provider_id' => 4,
                'shop_name' => 'The Handyman Hub',
                'country_id' => 231,
                'state_id' => 3956,
                'city_id' => 47875,
                'address' => '123 Main Street, Suite 101',
                'lat' => 40.71443,
                'long' => 73.935201,
                'registration_number' => 'HMN-45678-LA',
                'shop_start_time' => '10:00',
                'shop_end_time' => '22:00',
                'contact_number' => '+12 135550101',
                'email' => 'info@thehandymanhub.com',
                'is_active' => 1,
                'shop_attachment' => public_path('/images/shops/image1.png'),
                'service_ids' => [15, 18, 22, 27, 31],
            ],
            [
                'provider_id' => 16,
                'shop_name' => 'Apex Home Services',
                'country_id' => 38,
                'state_id' => 671,
                'city_id' => 10519,
                'address' => '45 Queen Street, Unit 200',
                'lat' => 43.69211,
                'long' => 79.476598,
                'registration_number' => 'QFS-98765-TO',
                'shop_start_time' => '10:00',
                'shop_end_time' => '22:00',
                'contact_number' => '+14 165550202',
                'email' => 'support@apex.com',
                'is_active' => 1,
                'shop_attachment' => public_path('/images/shops/image2.png'),
                'service_ids' => [13, 73],
            ],
            [
                'provider_id' => 17,
                'shop_name' => 'Elite Property Care',
                'country_id' => 230,
                'state_id' => 3842,
                'city_id' => 41816,
                'address' => "789 King's Road, SW3 4AS",
                'lat' => 51.499134,
                'long' => 0.158911,
                'registration_number' => 'AIO-11223-LD',
                'shop_start_time' => '10:00',
                'shop_end_time' => '22:00',
                'contact_number' => '+44 2055550303',
                'email' => 'info@elite.com',
                'is_active' => 1,
                'shop_attachment' => public_path('/images/shops/image3.png'),
                'service_ids' => [47, 79],
            ],
            [
                'provider_id' => 7,
                'shop_name' => 'Sterling Repairs',
                'country_id' => 13,
                'state_id' => 248,
                'city_id' => 6559,
                'address' => '1 Queen Street, Balmain',
                'lat' => 33.908143,
                'long' => 151.202769,
                'registration_number' => 'UMC-44556-SYD',
                'shop_start_time' => '10:00',
                'shop_end_time' => '22:00',
                'contact_number' => '+61 255550404',
                'email' => 'support@sterling.com',
                'is_active' => 1,
                'shop_attachment' => public_path('/images/shops/image4.png'),
                'service_ids' => [74],
            ],
            [
                'provider_id' => 4,
                'shop_name' => 'Trustworthy Tradesmen',
                'country_id' => 231,
                'state_id' => 3924,
                'city_id' => 43070,
                'address' => '22 Unter den Linden',
                'lat' => 36.187383,
                'long' => 115.133936,
                'registration_number' => 'ESN-48678-LA',
                'shop_start_time' => '10:00',
                'shop_end_time' => '22:00',
                'contact_number' => '+12 135750101',
                'email' => 'info@trustworthy.com',
                'is_active' => 1,
                'shop_attachment' => public_path('/images/shops/image5.png'),
                'service_ids' => [25, 29, 33],
            ],
        ];

        foreach ($shops as $data) {
            $serviceIds = $data['service_ids'] ?? [];
            $shopAttachment = $data['shop_attachment'] ?? null;

            $data = array_diff_key($data, array_flip(['service_ids', 'shop_attachment']));

            $shop = Shop::create($data);

            if ($shopAttachment && file_exists($shopAttachment)) {
                $this->attachShopImage($shop, $shopAttachment);
            }

            if (!empty($serviceIds)) {
                $shop->services()->attach($serviceIds);
                Service::whereIn('id', $serviceIds)->update(['visit_type' => 'on_shop']);
            }
            foreach ($days as $day) {
                ShopHour::updateOrCreate([
                    'shop_id' => $shop->id,
                    'day' => $day['day'],
                    'start_time' => $day['start_time'],
                    'end_time' => $day['end_time'],
                    'is_holiday' => $day['is_holiday'],
                    'breaks' => $day['breaks'],
                ]);
            }
        }

    }

    private function attachShopImage($model, $publicPath)
    {

        $file = new \Illuminate\Http\File($publicPath);

        $media = $model->addMedia($file)->preservingOriginal()->toMediaCollection('shop_attachment');

        return $media;

    }
}
