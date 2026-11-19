<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            ['name' => 'Arjun Sharma', 'phone' => '+919810012345', 'id_card_number' => '234567890101'],
            ['name' => 'Priya Patel', 'phone' => '+919810012346', 'id_card_number' => '234567890102'],
            ['name' => 'Rohan Mehta', 'phone' => '+919810012347', 'id_card_number' => '234567890103'],
            ['name' => 'Ananya Iyer', 'phone' => '+919810012348', 'id_card_number' => '234567890104'],
            ['name' => 'Vikram Singh', 'phone' => '+919810012349', 'id_card_number' => '234567890105'],
            ['name' => 'Neha Kapoor', 'phone' => '+919810012350', 'id_card_number' => '234567890106'],
            ['name' => 'Karthik Reddy', 'phone' => '+919810012351', 'id_card_number' => '234567890107'],
            ['name' => 'Sneha Joshi', 'phone' => '+919810012352', 'id_card_number' => '234567890108'],
            ['name' => 'Rahul Verma', 'phone' => '+919810012353', 'id_card_number' => '234567890109'],
            ['name' => 'Pooja Nair', 'phone' => '+919810012354', 'id_card_number' => '234567890110'],
            ['name' => 'Amit Kumar', 'phone' => '+919810012355', 'id_card_number' => '234567890111'],
            ['name' => 'Divya Menon', 'phone' => '+919810012356', 'id_card_number' => '234567890112'],
            ['name' => 'Sameer Khan', 'phone' => '+919810012357', 'id_card_number' => '234567890113'],
            ['name' => 'Ritika Das', 'phone' => '+919810012358', 'id_card_number' => '234567890114'],
            ['name' => 'Nikhil Rao', 'phone' => '+919810012359', 'id_card_number' => '234567890115'],
            ['name' => 'Aditya Gupta', 'phone' => '+919810012360', 'id_card_number' => '234567890116'],
            ['name' => 'Shreya Bose', 'phone' => '+919810012361', 'id_card_number' => '234567890117'],
            ['name' => 'Manish Tiwari', 'phone' => '+919810012362', 'id_card_number' => '234567890118'],
            ['name' => 'Kavya Pillai', 'phone' => '+919810012363', 'id_card_number' => '234567890119'],
            ['name' => 'Gaurav Chauhan', 'phone' => '+919810012364', 'id_card_number' => '234567890120'],
        ];

        foreach ($tenants as $data) {
            Tenant::firstOrCreate(
                ['id_card_number' => $data['id_card_number']],
                $data,
            );
        }

        DB::statement("UPDATE tenants SET is_active = false WHERE id_card_number = '234567890112'");
        DB::statement("UPDATE tenants SET is_active = false WHERE id_card_number = '234567890115'");
    }
}
