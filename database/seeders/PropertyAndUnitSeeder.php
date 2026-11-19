<?php

namespace Database\Seeders;

use App\Enums\UnitStatus;
use App\Models\City;
use App\Models\Property;
use App\Models\Region;
use App\Models\Unit;
use App\Models\UnitRate;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertyAndUnitSeeder extends Seeder
{
    private const NAMESPACE = 'ope-184-demo';

    private array $properties = [
        [
            'name' => 'Rosewood Residency',
            'address' => '12, MG Road, Near Metro Station',
            'region_name' => 'Karnataka',
            'city_name' => 'Bengaluru',
            'postal_code' => '560001',
            'phone' => '+919812345678',
            'description' => 'Comfortable serviced rooms in the city centre with easy access to offices and shopping.',
        ],
        [
            'name' => 'White Lotus Apartments',
            'address' => 'Plot 25, Sector 14, Vashi',
            'region_name' => 'Maharashtra',
            'city_name' => 'Mumbai',
            'postal_code' => '400703',
            'phone' => '+919812345679',
            'description' => 'Premium apartments with full amenities, AC, WiFi, and electricity included.',
        ],
        [
            'name' => 'Greenview Enclave',
            'address' => '7, Green Park Colony, Road No. 3',
            'region_name' => 'Telangana',
            'city_name' => 'Hyderabad',
            'postal_code' => '500016',
            'phone' => '+919812345680',
            'description' => 'Leafy gated community, ideal for working professionals and students.',
        ],
        [
            'name' => 'Sunny Meadows Homes',
            'address' => '33, Baner Road, Near Allegro',
            'region_name' => 'Maharashtra',
            'city_name' => 'Pune',
            'postal_code' => '411045',
            'phone' => '+919812345681',
            'description' => 'Well-located homes close to the IT corridor, safe and quiet neighbourhood.',
        ],
        [
            'name' => 'Palmgrove Suites',
            'address' => '15, TTK Road, Alwarpet',
            'region_name' => 'Tamil Nadu',
            'city_name' => 'Chennai',
            'postal_code' => '600018',
            'phone' => '+919812345682',
            'description' => 'Modern suites with a swimming pool and shared lounge areas.',
        ],
        [
            'name' => 'Emerald Court',
            'address' => '5A, Rajouri Garden, Block C',
            'region_name' => 'Delhi',
            'city_name' => 'New Delhi',
            'postal_code' => '110027',
            'phone' => '+919812345683',
            'description' => 'Newly built minimalist court near the metro and university campus.',
        ],
        [
            'name' => 'Silver Springs Residency',
            'address' => '88, Salt Lake Sector V, Block EP',
            'region_name' => 'West Bengal',
            'city_name' => 'Kolkata',
            'postal_code' => '700091',
            'phone' => '+919812345684',
            'description' => 'Fully furnished premium residency with attached bathrooms.',
        ],
        [
            'name' => 'Lakeview Heights',
            'address' => '12, C-Scheme, Ashok Marg',
            'region_name' => 'Rajasthan',
            'city_name' => 'Jaipur',
            'postal_code' => '302001',
            'phone' => '+919812345685',
            'description' => 'Comfortable heights near the business district and malls.',
        ],
        [
            'name' => 'Cedarwood Apartments',
            'address' => '3, Satellite Road, Jodhpur Gam',
            'region_name' => 'Gujarat',
            'city_name' => 'Ahmedabad',
            'postal_code' => '380015',
            'phone' => '+919812345686',
            'description' => 'Family-friendly apartments with a green courtyard and ample parking.',
        ],
        [
            'name' => 'Harbourview Residency',
            'address' => '18, Marine Drive, Nariman Point',
            'region_name' => 'Goa',
            'city_name' => 'Panaji',
            'postal_code' => '403001',
            'phone' => '+919812345687',
            'description' => 'Bright rooms minutes from the waterfront promenade and city centre.',
        ],
    ];

    private array $unitTemplates = [
        [
            'prefix' => 'A',
            'count' => 4,
            'floor' => 1,
            'capacity' => 1,
            'rates' => [
                ['billing_interval' => 1, 'billing_unit' => 'month', 'amount' => '18000', 'currency' => 'INR'],
                ['billing_interval' => 1, 'billing_unit' => 'year', 'amount' => '198000', 'currency' => 'INR'],
            ],
        ],
        [
            'prefix' => 'B',
            'count' => 4,
            'floor' => 2,
            'capacity' => 3,
            'rates' => [
                ['billing_interval' => 1, 'billing_unit' => 'month', 'amount' => '25000', 'currency' => 'INR'],
                ['billing_interval' => 3, 'billing_unit' => 'month', 'amount' => '72000', 'currency' => 'INR'],
            ],
        ],
        [
            'prefix' => 'C',
            'count' => 4,
            'floor' => 3,
            'capacity' => 1,
            'rates' => [
                ['billing_interval' => 1, 'billing_unit' => 'month', 'amount' => '22000', 'currency' => 'INR'],
                ['billing_interval' => 1, 'billing_unit' => 'week', 'amount' => '5500', 'currency' => 'INR'],
            ],
        ],
    ];

    public function run(): void
    {
        $now = now();

        DB::transaction(function () use ($now): void {
            foreach ($this->properties as $data) {
                $region = Region::query()
                    ->where('country_code', 'IN')
                    ->where('name', $data['region_name'])
                    ->firstOrFail();
                $city = City::query()
                    ->where('region_id', $region->id)
                    ->where('name', $data['city_name'])
                    ->firstOrFail();

                $property = $this->upsertProperty($data, $region->id, $city->id);

                foreach ($this->unitTemplates as $template) {
                    for ($index = 1; $index <= $template['count']; $index++) {
                        $unit = $this->upsertUnit($property, $template, $index);

                        foreach ($template['rates'] as $rate) {
                            $this->upsertRate($unit, $rate, $now);
                        }
                    }
                }
            }
        });
    }

    /**
     * @param  array{name: string, address: string, region_name: string, city_name: string, postal_code: string, phone: string, description: string}  $data
     */
    private function upsertProperty(array $data, int $regionId, int $cityId): Property
    {
        $slug = self::NAMESPACE.'-'.str($data['name'])->slug();
        $property = Property::withTrashed()->firstOrNew(['slug' => $slug]);

        if ($property->trashed()) {
            $property->restoreQuietly();
        }

        $property->forceFill([
            'name' => $data['name'],
            'type' => 'boarding_house',
            'slug' => $slug,
            'address' => $data['address'],
            'region_id' => $regionId,
            'city_id' => $cityId,
            'postal_code' => $data['postal_code'],
            'phone' => $data['phone'],
            'description' => $data['description'],
            'is_active' => true,
        ])->saveQuietly();

        return $property->refresh();
    }

    /**
     * @param  array{prefix: string, count: int, floor: int, capacity: int, rates: array<int, array{billing_interval: int, billing_unit: string, amount: string, currency: string}>}  $template
     */
    private function upsertUnit(Property $property, array $template, int $index): Unit
    {
        $name = $template['prefix'].$index;
        $unit = Unit::withTrashed()->firstOrNew([
            'property_id' => $property->id,
            'name' => $name,
        ]);

        if ($unit->trashed()) {
            $unit->restoreQuietly();
        }

        $status = $unit->leases()->active()->exists()
            ? UnitStatus::Occupied
            : UnitStatus::Available;

        $unit->forceFill([
            'property_id' => $property->id,
            'name' => $name,
            'slug' => self::NAMESPACE.'-'.str($property->slug.'-'.$name)->slug(),
            'floor' => (string) $template['floor'],
            'size_sqm' => 16 + ($index * 2),
            'capacity' => $template['capacity'],
            'status' => $status,
            'notes' => 'GrihaFlow demonstration unit.',
        ])->saveQuietly();

        return $unit->refresh();
    }

    /**
     * @param  array{billing_interval: int, billing_unit: string, amount: string, currency: string}  $rateData
     */
    private function upsertRate(Unit $unit, array $rateData, CarbonInterface $now): UnitRate
    {
        $rate = UnitRate::query()->firstOrNew([
            'unit_id' => $unit->id,
            'billing_interval' => $rateData['billing_interval'],
            'billing_unit' => $rateData['billing_unit'],
            'currency' => $rateData['currency'],
        ]);

        $rate->forceFill([
            'unit_id' => $unit->id,
            'billing_interval' => $rateData['billing_interval'],
            'billing_unit' => $rateData['billing_unit'],
            'amount' => $rateData['amount'],
            'currency' => $rateData['currency'],
            'is_active' => true,
            'effective_from' => $now->copy()->startOfMonth()->toDateString(),
            'effective_until' => null,
        ])->saveQuietly();

        return $rate->refresh();
    }
}
