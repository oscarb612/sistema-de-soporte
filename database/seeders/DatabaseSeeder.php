<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use App\Models\Device;
use App\Models\RepairOrder;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['rut' => '76.123.456-7'],
            [
                'name' => 'Servicio Tecnico Demo',
                'address' => 'Av. Principal 123',
                'phone' => '+56 9 1234 5678',
                'email' => 'contacto@demo.cl',
                'plan' => 'starter',
            ]
        );

        User::firstOrCreate([
            'email' => 'superadmin@example.com',
        ], [
            'company_id' => null,
            'name' => 'Super Administrador',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'company_id' => $company->id,
            'name' => 'Administrador Demo',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $client = Client::firstOrCreate(
            ['company_id' => $company->id, 'rut' => '12.345.678-5'],
            [
                'name' => 'Cliente Demo',
                'address' => 'Los Aromos 456',
                'phone' => '+56 9 8765 4321',
                'email' => 'cliente@example.com',
            ]
        );

        if (! RepairOrder::where('company_id', $company->id)->where('code', 'OT-000001')->exists()) {
            $device = Device::create([
                'company_id' => $company->id,
                'client_id' => $client->id,
                'type' => 'Notebook',
                'brand' => 'Lenovo',
                'model' => 'ThinkPad',
                'serial_number' => 'SN-DEMO-001',
                'accessories' => 'Cargador original',
                'physical_condition' => 'Equipo con marcas normales de uso.',
            ]);

            $order = RepairOrder::create([
                'company_id' => $company->id,
                'client_id' => $client->id,
                'device_id' => $device->id,
                'code' => 'OT-000001',
                'status' => 'ingresado',
                'reported_problem' => 'No enciende correctamente.',
                'initial_observations' => 'Cliente solicita diagnostico inicial.',
            ]);

            $order->statusLogs()->create([
                'status' => 'ingresado',
                'comment' => 'Orden demo creada.',
            ]);
        }
    }
}
