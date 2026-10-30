<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\RepairOrder;
use App\Models\RepairQuote;
use App\Models\Service;
use App\Mail\RepairOrderReceived;
use App\Mail\DataRecoveryReceived;
use App\Models\DataRecoveryCase;
use App\Models\Client;
use App\Models\DataRecoveryPayment;
use App\Models\DataRecoveryQuote;
use App\Models\HomeServicePreorder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_login_uses_branded_visual_without_demo_credentials(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Gestión de servicio técnico')
            ->assertSee('images/login-engineer.webp')
            ->assertDontSee('Demo super admin')
            ->assertDontSee('admin@example.com');
    }

    public function test_authenticated_user_can_open_repair_order_create_form(): void
    {
        $company = Company::create([
            'name' => 'Empresa Test',
            'rut' => '11.111.111-1',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/repair-orders/create');

        $response->assertOk();
        $response->assertSee('Cliente');
        $response->assertSee('RUT');
    }

    public function test_authenticated_user_can_open_repair_order_edit_form(): void
    {
        $company = Company::create(['name' => 'Empresa Editar Orden', 'rut' => '21.111.111-9']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin', 'is_active' => true]);

        $this->actingAs($user)->post('/repair-orders', [
            'company_id' => $company->id,
            'client_rut' => '12.345.678-5',
            'client_name' => 'Cliente Editar',
            'status' => 'ingresado',
            'reported_problem' => 'Equipo para revisión.',
            'devices' => [['type' => 'Notebook']],
        ]);

        $order = RepairOrder::firstOrFail();
        $this->actingAs($user)->get("/repair-orders/{$order->id}/edit")
            ->assertOk()
            ->assertSee('Tipo de atención')
            ->assertSee('Servicio con precio definido');

        $printResponse = $this->actingAs($user)->get("/repair-orders/{$order->id}/print");
        $printResponse->assertOk()->assertSee('Solicitud y autorización de diagnóstico');
        $this->assertSame(2, substr_count($printResponse->getContent(), '<article class="sheet">'));
    }

    public function test_authenticated_user_can_open_activity_logs(): void
    {
        $company = Company::create([
            'name' => 'Empresa Logs',
            'rut' => '22.222.222-2',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/activity-logs');

        $response->assertOk();
        $response->assertSee('Logs de actividad');
        $response->assertSee('ultimos 60 dias');
    }

    public function test_repair_order_can_be_created_with_multiple_devices(): void
    {
        $company = Company::create([
            'name' => 'Empresa Equipos',
            'rut' => '33.333.333-3',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/repair-orders', [
            'company_id' => $company->id,
            'client_rut' => '12.345.678-5',
            'client_name' => 'Cliente Multiples Equipos',
            'client_phone' => '+56 9 1111 2222',
            'region' => 'Biobío',
            'commune' => 'Concepción',
            'status' => 'ingresado',
            'reported_problem' => 'Ingreso de dos equipos para revision.',
            'devices' => [
                [
                    'type' => 'Notebook',
                    'brand' => 'Lenovo',
                    'model' => 'T14',
                ],
                [
                    'type' => 'PC',
                    'brand' => 'Armado',
                    'model' => 'Desktop',
                ],
            ],
        ]);

        $response->assertRedirect('/repair-orders');

        $order = RepairOrder::with('devices')->first();

        $this->assertNotNull($order);
        $this->assertCount(2, $order->devices);
        $this->assertSame('Biobío', $order->client->region);
        $this->assertSame('Concepción', $order->client->commune);
    }

    public function test_quote_can_be_created_and_approved(): void
    {
        $company = Company::create([
            'name' => 'Empresa Presupuesto',
            'rut' => '44.444.444-4',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post('/repair-orders', [
            'company_id' => $company->id,
            'client_rut' => '12.345.678-5',
            'client_name' => 'Cliente Presupuesto',
            'status' => 'ingresado',
            'reported_problem' => 'Equipo no enciende.',
            'devices' => [
                ['type' => 'Notebook', 'brand' => 'HP'],
            ],
        ]);

        $order = RepairOrder::first();

        $this->actingAs($user)->get("/repair-orders/{$order->id}/quotes/create")
            ->assertOk()
            ->assertSee('style="display:none;"', false)
            ->assertSee('Eliminar ítem');

        $response = $this->actingAs($user)->post("/repair-orders/{$order->id}/quotes", [
            'diagnosis_summary' => 'Falla en fuente de poder.',
            'discount_amount' => 5000,
            'items' => [
                ['type' => 'mano_obra', 'description' => 'Diagnostico y reparacion', 'quantity' => 1, 'unit_price' => 30000],
                ['type' => 'repuesto', 'description' => 'Fuente de poder', 'quantity' => 1, 'unit_price' => 25000],
            ],
        ]);

        $quote = RepairQuote::first();

        $response->assertRedirect("/repair-quotes/{$quote->id}");
        $this->assertSame('presupuesto_emitido', $order->fresh()->status);
        $this->assertEquals(50000, (float) $quote->net_amount);
        $this->assertEquals(9500, (float) $quote->tax_amount);
        $this->assertEquals(59500, (float) $quote->total_amount);

        $printResponse = $this->actingAs($user)->get("/repair-quotes/{$quote->id}/print");
        $printResponse->assertOk()
            ->assertSee('Copia cliente')
            ->assertSee('Datos del cliente')
            ->assertSee('Diagnóstico')
            ->assertSee('IVA (19%)')
            ->assertDontSee('Aprobar presupuesto');
        $this->assertSame(1, substr_count($printResponse->getContent(), '<article class="sheet">'));

        $this->actingAs($user)->post("/repair-quotes/{$quote->id}/approve", [
            'customer_response_notes' => 'Cliente aprueba por telefono.',
        ])->assertRedirect("/repair-quotes/{$quote->id}");

        $this->assertSame('aprobado', $quote->fresh()->status);
        $this->assertSame('en_reparacion', $order->fresh()->status);
    }

    public function test_order_with_quote_cannot_create_another_quote(): void
    {
        $company = Company::create([
            'name' => 'Empresa Presupuesto Unico',
            'rut' => '55.555.555-5',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post('/repair-orders', [
            'company_id' => $company->id,
            'client_rut' => '12.345.678-5',
            'client_name' => 'Cliente Presupuesto Unico',
            'status' => 'ingresado',
            'reported_problem' => 'Equipo lento.',
            'devices' => [
                ['type' => 'Notebook', 'brand' => 'Dell'],
            ],
        ]);

        $order = RepairOrder::first();

        $this->actingAs($user)->post("/repair-orders/{$order->id}/quotes", [
            'diagnosis_summary' => 'Requiere mantencion.',
            'items' => [
                ['type' => 'mano_obra', 'description' => 'Mantencion', 'quantity' => 1, 'unit_price' => 20000],
            ],
        ])->assertRedirect();

        $this->actingAs($user)
            ->get("/repair-orders/{$order->id}/quotes/create")
            ->assertForbidden();
    }

    public function test_quote_can_be_deleted_when_not_approved(): void
    {
        $company = Company::create([
            'name' => 'Empresa Eliminar Presupuesto',
            'rut' => '66.666.666-6',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post('/repair-orders', [
            'company_id' => $company->id,
            'client_rut' => '12.345.678-5',
            'client_name' => 'Cliente Eliminar Presupuesto',
            'status' => 'diagnosticado',
            'reported_problem' => 'Equipo sin imagen.',
            'devices' => [
                ['type' => 'PC', 'brand' => 'Armado'],
            ],
        ]);

        $order = RepairOrder::first();

        $this->actingAs($user)->post("/repair-orders/{$order->id}/quotes", [
            'diagnosis_summary' => 'Requiere cambio de memoria.',
            'items' => [
                ['type' => 'repuesto', 'description' => 'Memoria RAM', 'quantity' => 1, 'unit_price' => 18000],
            ],
        ]);

        $quote = RepairQuote::first();

        $this->actingAs($user)
            ->delete("/repair-quotes/{$quote->id}")
            ->assertRedirect("/repair-orders/{$order->id}");

        $this->assertDatabaseMissing('repair_quotes', ['id' => $quote->id]);
        $this->assertSame('diagnosticado', $order->fresh()->status);
    }

    public function test_approved_quote_can_be_deleted_and_order_returns_to_diagnosed(): void
    {
        $company = Company::create(['name' => 'Empresa Eliminar Aprobado', 'rut' => '76.666.666-8']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin', 'is_active' => true]);

        $this->actingAs($user)->post('/repair-orders', [
            'company_id' => $company->id,
            'client_rut' => '12.345.678-5',
            'client_name' => 'Cliente Presupuesto Aprobado',
            'status' => 'diagnosticado',
            'reported_problem' => 'Equipo no inicia.',
            'devices' => [['type' => 'Notebook', 'brand' => 'Acer']],
        ]);

        $order = RepairOrder::firstOrFail();
        $this->actingAs($user)->post("/repair-orders/{$order->id}/quotes", [
            'diagnosis_summary' => 'Requiere reparación.',
            'items' => [['type' => 'mano_obra', 'description' => 'Reparación', 'quantity' => 1, 'unit_price' => 25000]],
        ]);
        $quote = RepairQuote::firstOrFail();
        $this->actingAs($user)->post("/repair-quotes/{$quote->id}/approve");

        $this->actingAs($user)->get("/repair-orders/{$order->id}")
            ->assertOk()
            ->assertSee('Eliminar');

        $this->actingAs($user)->delete("/repair-quotes/{$quote->id}")
            ->assertRedirect("/repair-orders/{$order->id}");

        $this->assertDatabaseMissing('repair_quotes', ['id' => $quote->id]);
        $this->assertSame('diagnosticado', $order->fresh()->status);
    }

    public function test_fully_paid_approved_quote_finalizes_the_order(): void
    {
        $company = Company::create(['name' => 'Empresa Pago Final', 'rut' => '76.123.456-0']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin', 'is_active' => true]);

        $this->actingAs($user)->post('/repair-orders', [
            'company_id' => $company->id,
            'client_rut' => '12.345.678-5',
            'client_name' => 'Cliente Pago Final',
            'status' => 'diagnosticado',
            'reported_problem' => 'Equipo no inicia.',
            'devices' => [['type' => 'Notebook', 'brand' => 'Acer']],
        ]);

        $order = RepairOrder::firstOrFail();
        $this->actingAs($user)->post("/repair-orders/{$order->id}/quotes", [
            'diagnosis_summary' => 'Requiere reparacion.',
            'items' => [['type' => 'mano_obra', 'description' => 'Reparacion', 'quantity' => 1, 'unit_price' => 10000]],
        ]);
        $quote = RepairQuote::firstOrFail();
        $this->actingAs($user)->post("/repair-quotes/{$quote->id}/approve");

        $this->actingAs($user)->post("/repair-orders/{$order->id}/payments", [
            'repair_quote_id' => $quote->id,
            'payment_type' => 'pago_final',
            'payment_method' => 'transferencia',
            'amount' => 11900,
            'payment_date' => '2026-10-30',
        ])->assertRedirect("/repair-orders/{$order->id}");

        $this->assertSame('finalizado', $order->fresh()->status);
        $this->assertDatabaseHas('repair_order_status_logs', [
            'repair_order_id' => $order->id,
            'status' => 'finalizado',
        ]);
    }

    public function test_repair_order_can_be_deleted(): void
    {
        $company = Company::create([
            'name' => 'Empresa Eliminar Orden',
            'rut' => '77.777.777-7',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post('/repair-orders', [
            'company_id' => $company->id,
            'client_rut' => '12.345.678-5',
            'client_name' => 'Cliente Eliminar Orden',
            'status' => 'ingresado',
            'reported_problem' => 'Equipo se apaga.',
            'devices' => [
                ['type' => 'Notebook', 'brand' => 'Asus'],
            ],
        ]);

        $order = RepairOrder::first();

        $this->actingAs($user)
            ->delete("/repair-orders/{$order->id}")
            ->assertRedirect('/repair-orders');

        $this->assertDatabaseMissing('repair_orders', ['id' => $order->id]);
    }

    public function test_direct_service_order_uses_catalog_price_and_skips_quote(): void
    {
        $company = Company::create(['name' => 'Empresa Servicio Directo', 'rut' => '88.888.888-8']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin', 'is_active' => true]);
        $service = Service::create([
            'company_id' => $company->id,
            'name' => 'Limpieza interna',
            'price' => 20000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/repair-orders', [
            'company_id' => $company->id,
            'client_rut' => '12.345.678-5',
            'client_name' => 'Cliente Servicio Directo',
            'status' => 'ingresado',
            'service_mode' => 'precio_definido',
            'service_id' => $service->id,
            'service_quantity' => 2,
            'service_unit_price' => 20000,
            'discount_amount' => 5000,
            'customer_acceptance' => '1',
            'reported_problem' => 'Solicita limpieza interna.',
            'devices' => [['type' => 'Notebook', 'brand' => 'Lenovo']],
        ]);

        $response->assertRedirect('/repair-orders');
        $order = RepairOrder::with('items')->firstOrFail();
        $this->assertSame('precio_definido', $order->service_mode);
        $this->assertSame('servicio_aceptado', $order->status);
        $this->assertEquals(35000, (float) $order->net_amount);
        $this->assertEquals(6650, (float) $order->tax_amount);
        $this->assertEquals(41650, (float) $order->total_amount);
        $this->assertNotNull($order->customer_accepted_at);
        $this->assertSame($service->id, $order->items->first()->service_id);

        $this->actingAs($user)->get("/repair-orders/{$order->id}/print")
            ->assertOk()
            ->assertSee('Servicio contratado y valor aceptado')
            ->assertSee('IVA (19%)');

        $this->actingAs($user)->get("/repair-orders/{$order->id}/quotes/create")->assertForbidden();
    }

    public function test_user_can_be_deleted_but_current_user_cannot_delete_self(): void
    {
        $company = Company::create(['name' => 'Empresa Usuarios', 'rut' => '90.111.111-1']);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin', 'is_active' => true]);
        $managedUser = User::factory()->create(['company_id' => $company->id, 'role' => 'tecnico', 'is_active' => true]);

        $this->actingAs($admin)->delete("/users/{$managedUser->id}")
            ->assertRedirect('/users');
        $this->assertDatabaseMissing('users', ['id' => $managedUser->id]);

        $this->actingAs($admin)->delete("/users/{$admin->id}")->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_super_admin_can_delete_company_and_associated_users(): void
    {
        $superAdmin = User::factory()->create(['company_id' => null, 'role' => 'super_admin', 'is_active' => true]);
        $company = Company::create(['name' => 'Empresa a eliminar', 'rut' => '91.111.111-7']);
        $managedUser = User::factory()->create(['company_id' => $company->id, 'role' => 'admin', 'is_active' => true]);

        $this->actingAs($superAdmin)->delete("/companies/{$company->id}")
            ->assertRedirect('/companies');

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
        $this->assertDatabaseMissing('users', ['id' => $managedUser->id]);
    }

    public function test_company_admin_can_configure_outgoing_mail_sender(): void
    {
        $company = Company::create(['name' => 'Empresa Correo', 'rut' => '92.111.111-2']);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->get('/mail-settings')
            ->assertOk()
            ->assertSee('Correo saliente');

        $this->actingAs($admin)->put('/mail-settings', [
            'company_id' => $company->id,
            'mail_from_name' => 'Soporte Empresa Correo',
            'mail_from_address' => 'soporte@empresa.cl',
            'smtp_host' => 'smtp.empresa.cl',
            'smtp_port' => 587,
            'smtp_username' => 'soporte@empresa.cl',
            'smtp_password' => 'secret-test-password',
            'smtp_security' => 'tls',
        ])->assertRedirect('/mail-settings?company_id='.$company->id);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'mail_from_name' => 'Soporte Empresa Correo',
            'mail_from_address' => 'soporte@empresa.cl',
            'smtp_host' => 'smtp.empresa.cl',
            'smtp_port' => 587,
            'smtp_username' => 'soporte@empresa.cl',
            'smtp_security' => 'tls',
        ]);
        $company->refresh();
        $this->assertSame('secret-test-password', $company->smtp_password);
        $this->assertNotSame('secret-test-password', \DB::table('companies')->where('id', $company->id)->value('smtp_password'));
    }

    public function test_company_admin_can_configure_encrypted_groq_key(): void
    {
        $company = Company::create(['name' => 'Empresa IA', 'rut' => '93.111.111-8']);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin', 'is_active' => true]);
        $key = 'gsk_test_key_with_enough_characters_123456';

        $this->actingAs($admin)->get('/ai-settings')
            ->assertOk()
            ->assertSee('Inteligencia artificial');

        $this->actingAs($admin)->put('/ai-settings', [
            'company_id' => $company->id,
            'groq_api_key' => $key,
            'groq_model' => 'qwen/qwen3.8-27b',
        ])->assertRedirect('/ai-settings?company_id='.$company->id);

        $company->refresh();
        $this->assertSame($key, $company->groq_api_key);
        $this->assertNotSame($key, \DB::table('companies')->where('id', $company->id)->value('groq_api_key'));
    }

    public function test_order_photos_are_converted_to_webp_and_attached_to_email(): void
    {
        Storage::fake('local');
        Mail::fake();
        $company = Company::create([
            'name' => 'Empresa Fotografías', 'rut' => '94.111.111-3',
            'mail_from_name' => 'Soporte', 'mail_from_address' => 'soporte@empresa.cl',
            'smtp_host' => 'smtp.empresa.cl', 'smtp_port' => 587, 'smtp_username' => 'soporte@empresa.cl',
            'smtp_password' => 'secret-test-password', 'smtp_security' => 'tls',
        ]);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->post('/repair-orders', [
            'company_id' => $company->id,
            'client_rut' => '12.345.678-5',
            'client_name' => 'Cliente con fotos',
            'client_email' => 'cliente@example.com',
            'status' => 'ingresado',
            'reported_problem' => 'Equipo para diagnóstico.',
            'attach_photos' => '1',
            'label_photo_index' => '0',
            'photos' => [UploadedFile::fake()->image('etiqueta.jpg', 2000, 1200)],
            'devices' => [['type' => 'Notebook', 'brand' => 'HP']],
        ])->assertRedirect('/repair-orders')
            ->assertSessionHas('success', 'Orden de reparación creada correctamente. Correo enviado exitosamente a cliente@example.com.');

        $order = RepairOrder::with('photos')->firstOrFail();
        $this->assertCount(1, $order->photos);
        $this->assertSame('webp', pathinfo($order->photos->first()->path, PATHINFO_EXTENSION));
        $this->assertLessThanOrEqual(1600, max($order->photos->first()->width, $order->photos->first()->height));
        Storage::disk('local')->assertExists($order->photos->first()->path);
        $this->assertSame('enviado', $order->fresh()->email_status);
        Mail::assertSent(RepairOrderReceived::class, fn ($mail) => count($mail->attachments()) === 2);
    }

    public function test_groq_analysis_returns_device_label_fields(): void
    {
        Http::fake(['api.groq.com/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode([
                'brand' => 'Lenovo', 'model' => 'ThinkPad T14', 'serial_number' => 'ABC123', 'confidence' => 0.96,
            ])]]],
        ])]);
        $company = Company::create(['name' => 'Empresa Groq', 'rut' => '95.111.111-9', 'groq_api_key' => 'gsk_test_key_with_enough_characters', 'groq_model' => 'qwen/qwen3.8-27b']);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->post('/ai/analyze-device-label', [
            'company_id' => $company->id,
            'image' => UploadedFile::fake()->image('label.jpg', 800, 500),
        ])->assertOk()->assertJsonPath('data.serial_number', 'ABC123');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.groq.com/openai/v1/chat/completions');
    }

    public function test_recovery_form_has_webcam_and_ai_extracts_storage_fields(): void
    {
        Http::fake(['api.groq.com/*'=>Http::response(['choices'=>[['message'=>['content'=>json_encode(['brand'=>'Western Digital','model'=>'WD10SPZX','capacity'=>'1 TB','serial_number'=>'WX123','interface'=>'SATA','confidence'=>.94])]]]])]);
        $company=Company::create(['name'=>'Empresa Storage','rut'=>'95.111.111-9','groq_api_key'=>'gsk_test_key_with_enough_characters','groq_model'=>'qwen/qwen3.8-27b']);
        $admin=User::factory()->create(['company_id'=>$company->id,'role'=>'admin','is_active'=>true]);
        $this->actingAs($admin)->get('/data-recovery/create')->assertOk()->assertSee('Usar webcam')->assertSee('Autorrellenar etiqueta con IA')->assertSee('Etiqueta de la unidad');
        $this->actingAs($admin)->post('/ai/analyze-storage-label',['company_id'=>$company->id,'image'=>UploadedFile::fake()->image('disco.jpg',900,600)])
            ->assertOk()->assertJsonPath('data.capacity','1 TB')->assertJsonPath('data.interface','SATA')->assertJsonPath('data.serial_number','WX123');
    }

    public function test_data_recovery_case_reuses_client_and_sends_reception_evidence(): void
    {
        Storage::fake('local'); Mail::fake();
        $company = Company::create(['name' => 'Conexionweb SPA', 'rut' => '76.123.456-7', 'smtp_host' => 'smtp.test.cl', 'smtp_port' => 587, 'smtp_username' => 'test', 'smtp_password' => 'secret', 'mail_from_address' => 'recuperadata@test.cl']);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->post('/data-recovery', [
            'company_id' => $company->id, 'rut' => '12.345.678-5', 'client_name' => 'Cliente Recuperadata', 'client_email' => 'cliente@test.cl',
            'status' => 'recibido', 'unit_type' => 'HDD 2.5"', 'brand' => 'Seagate', 'capacity' => '1 TB', 'serial_number' => 'SN123',
            'contains_sensitive_data' => '1', 'access_credentials' => 'clave-secreta', 'photos' => [UploadedFile::fake()->image('unidad.jpg', 1800, 1200)],
        ])->assertRedirect()
            ->assertSessionHas('success', 'Caso de recuperación registrado correctamente. Correo enviado exitosamente a cliente@test.cl.');

        $case = DataRecoveryCase::with(['client','photos'])->firstOrFail();
        $this->assertSame('RD-000001', $case->code);
        $this->assertSame('clave-secreta', $case->access_credentials);
        $this->assertCount(1, $case->photos);
        Storage::disk('local')->assertExists($case->photos->first()->path);
        Mail::assertSent(DataRecoveryReceived::class, fn ($mail) => count($mail->attachments()) === 2);
        $this->actingAs($admin)->get('/data-recovery/'.$case->id)->assertOk()->assertSee('Seagate');
        $receptionPrint = $this->actingAs($admin)->get('/data-recovery/'.$case->id.'/print');
        $receptionPrint->assertOk()->assertSee('Imprimir dos copias')->assertSee('Copia oficina')->assertSee('Copia cliente');
        $this->assertSame(2, substr_count($receptionPrint->getContent(), '<article class="sheet">'));
    }

    public function test_data_recovery_quote_can_be_approved_and_receive_payments(): void
    {
        $company=Company::create(['name'=>'Conexionweb','rut'=>'76.123.456-7']);
        $admin=User::factory()->create(['company_id'=>$company->id,'role'=>'admin','is_active'=>true]);
        $client=Client::create(['company_id'=>$company->id,'rut'=>'12.345.678-5','name'=>'Cliente Datos']);
        $case=DataRecoveryCase::create(['company_id'=>$company->id,'client_id'=>$client->id,'received_by'=>$admin->id,'code'=>'RD-000001','status'=>'diagnosticado','unit_type'=>'SSD NVMe','received_at'=>now()]);

        $this->actingAs($admin)->post("/data-recovery/{$case->id}/quotes",[
            'diagnosis_summary'=>'Daño lógico recuperable','discount_amount'=>10000,
            'items'=>[['type'=>'recuperacion','description'=>'Recuperación de información','quantity'=>1,'unit_price'=>100000]],
        ])->assertRedirect();
        $quote=DataRecoveryQuote::firstOrFail();
        $this->assertSame('PRES-RD-000001',$quote->quote_number);
        $this->assertEquals(90000,$quote->net_amount);
        $this->assertEquals(107100,$quote->total_amount);
        $this->actingAs($admin)->get("/data-recovery-quotes/{$quote->id}")
            ->assertOk()
            ->assertSee('area-link recovery active', false)
            ->assertSee('Casos')
            ->assertSee('href="'.route('data-recovery.dashboard').'">Panel', false);
        $this->actingAs($admin)->get('/data-recovery-dashboard')
            ->assertOk()->assertSee('Panel Recuperadata')->assertSee('Casos activos');
        $this->actingAs($admin)->get("/data-recovery-quotes/{$quote->id}/print")
            ->assertOk()->assertSee('Imprimir copia cliente')->assertSee('Presupuesto de recuperación de datos');

        $this->actingAs($admin)->post("/data-recovery-quotes/{$quote->id}/approve")->assertRedirect();
        $this->assertSame('aprobado',$case->fresh()->status);
        $this->actingAs($admin)->post("/data-recovery/{$case->id}/payments",['data_recovery_quote_id'=>$quote->id,'payment_type'=>'abono','payment_method'=>'transferencia','amount'=>50000,'payment_date'=>now()->format('Y-m-d')])->assertRedirect();
        $this->assertEquals(50000,DataRecoveryPayment::firstOrFail()->amount);
        $this->actingAs($admin)->get("/data-recovery/{$case->id}")->assertOk()->assertSee('Saldo pendiente')->assertSee('$57.100');
    }

    public function test_home_quote_import_and_mobile_finalization_create_order_with_logistics_and_signature(): void
    {
        Storage::fake('local'); Mail::fake();
        Http::fake(function($request){$payload=['code'=>'RET-ABC1234567','address'=>'Sierra Nevada 10464','latitude'=>-33.5,'longitude'=>-70.6,'distance_km'=>12.4,'pickup_amount'=>6000,'delivery_amount'=>6000,'total_amount'=>12000,'currency_symbol'=>'$','initial_message'=>'Mensaje inicial','status'=>$request->method()==='POST'?'imported':'quoted','preorder_code'=>$request->method()==='POST'?'PRE-000001':null,'created_at'=>now()->toIso8601String(),'expires_at'=>now()->addDays(7)->toIso8601String()];return Http::response($payload);});
        $company=Company::create(['name'=>'Empresa Retiro','rut'=>'76.111.111-1','transport_api_url'=>'https://sitio.test','transport_api_key'=>'secret_key_with_more_than_32_characters','smtp_host'=>'smtp.test','smtp_port'=>587,'smtp_username'=>'user','smtp_password'=>'pass','mail_from_address'=>'test@test.cl']);
        $admin=User::factory()->create(['company_id'=>$company->id,'role'=>'admin','is_active'=>true]);
        $this->actingAs($admin)->post('/home-service',['company_id'=>$company->id,'external_quote_code'=>'RET-ABC1234567','client_rut'=>'12.345.678-5','client_name'=>'Cliente Retiro','client_phone'=>'+56911111111','client_email'=>'cliente@test.cl','service_area'=>'servicio_tecnico','conversation_summary'=>'Conversación realizada','prediagnosis'=>'Posible conector','reported_problem'=>'No enciende','unit_type'=>'Notebook'])->assertRedirect();
        $preorder=HomeServicePreorder::firstOrFail();$this->assertSame('PRE-000001',$preorder->code);$this->assertSame(12000.0,(float)$preorder->transport_total);
        $signature='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
        $this->actingAs($admin)->post("/home-service/{$preorder->id}/finalize",['unit_type'=>'Notebook','brand'=>'Lenovo','model'=>'T14','serial_number'=>'SER123','accessories'=>'Cargador','physical_condition'=>'Buen estado','signer_name'=>'Cliente Retiro','signer_rut'=>'12.345.678-5','signature'=>$signature,'label_photo_index'=>0,'photos'=>[UploadedFile::fake()->image('equipo.jpg',1200,800)]])->assertRedirect();
        $order=RepairOrder::with('items')->firstOrFail();$this->assertCount(2,$order->items);$this->assertSame(['Retiro a domicilio','Entrega a domicilio'],$order->items->pluck('description')->all());$this->assertSame('finalizada',$preorder->fresh()->status);Storage::disk('local')->assertExists($preorder->fresh()->signature_path);Mail::assertSent(RepairOrderReceived::class);
        $quote=RepairQuote::create(['company_id'=>$company->id,'repair_order_id'=>$order->id,'quote_number'=>'PRES-000001','status'=>'emitido','quote_type'=>'repair','diagnosis_summary'=>'Reparación propuesta','net_amount'=>100000,'tax_amount'=>19000,'total_amount'=>119000]);
        $this->actingAs($admin)->post("/repair-quotes/{$quote->id}/reject")->assertRedirect();
        $closure=RepairQuote::where('parent_quote_id',$quote->id)->with('items')->firstOrFail();
        $this->assertSame(['Diagnóstico (cobro por presupuesto no aprobado)','Retiro a domicilio','Entrega a domicilio'],$closure->items->pluck('description')->all());
        $this->assertEquals(32000,(float)$closure->total_amount);
    }

    public function test_pwa_installation_page_provides_local_qr_and_install_guidance(): void
    {
        $company=Company::create(['name'=>'Empresa PWA','rut'=>'76.222.222-2']);
        $admin=User::factory()->create(['company_id'=>$company->id,'role'=>'admin','is_active'=>true]);
        $this->actingAs($admin)->get('/pwa-settings')->assertOk()->assertSee('Aplicación móvil de retiros')->assertSee('Abrir aplicación para instalar')->assertSee('Agregar a inicio')->assertSee(route('home-service.index'));
        $this->actingAs($admin)->get('/home-service/mobile-app')->assertOk()->assertSee('pwa-install-button',false);
        $qr=$this->actingAs($admin)->get('/pwa-settings/qr');
        $qr->assertOk()->assertHeader('content-type','image/svg+xml')->assertSee('<svg',false);
        $manifest=json_decode(file_get_contents(public_path('home-service-manifest.webmanifest')),true,512,JSON_THROW_ON_ERROR);
        $this->assertSame('standalone',$manifest['display']);
        $this->assertCount(4,$manifest['icons']);
        foreach ($manifest['icons'] as $icon) $this->assertFileExists(public_path(ltrim($icon['src'],'/')));
        $this->assertFileExists(public_path('home-service-offline.html'));
    }

    public function test_rejected_recovery_quote_creates_diagnostic_closure_quote(): void
    {
        $company=Company::create(['name'=>'Empresa Cierre','rut'=>'76.333.333-3']);
        $admin=User::factory()->create(['company_id'=>$company->id,'role'=>'admin','is_active'=>true]);
        $client=Client::create(['company_id'=>$company->id,'rut'=>'12.345.678-5','name'=>'Cliente Cierre']);
        $case=DataRecoveryCase::create(['company_id'=>$company->id,'client_id'=>$client->id,'received_by'=>$admin->id,'code'=>'RD-000001','status'=>'presupuesto_enviado','unit_type'=>'SSD','received_at'=>now()]);
        $quote=DataRecoveryQuote::create(['company_id'=>$company->id,'data_recovery_case_id'=>$case->id,'quote_number'=>'PRES-RD-000001','status'=>'emitido','quote_type'=>'recovery','diagnosis_summary'=>'Recuperable','net_amount'=>100000,'tax_amount'=>19000,'total_amount'=>119000]);

        $this->actingAs($admin)->post("/data-recovery-quotes/{$quote->id}/reject")->assertRedirect();

        $closure=DataRecoveryQuote::where('parent_quote_id',$quote->id)->with('items')->firstOrFail();
        $this->assertSame('closure',$closure->quote_type);
        $this->assertEquals(20000,(float)$closure->total_amount);
        $this->assertSame('Diagnóstico (cobro por presupuesto no aprobado)',$closure->items->first()->description);
    }

    public function test_home_service_pickups_are_separated_by_business_area(): void
    {
        $company=Company::create(['name'=>'Empresa Retiros Separados','rut'=>'76.444.444-4']);
        $admin=User::factory()->create(['company_id'=>$company->id,'role'=>'admin','is_active'=>true]);
        $client=Client::create(['company_id'=>$company->id,'rut'=>'12.345.678-5','name'=>'Cliente Retiros']);
        foreach ([['PRE-ST-1','RET-ST-1','servicio_tecnico'],['PRE-RD-1','RET-RD-1','recuperadata']] as [$code,$external,$area]) {
            HomeServicePreorder::create(['company_id'=>$company->id,'client_id'=>$client->id,'code'=>$code,'external_quote_code'=>$external,'service_area'=>$area,'address'=>'Dirección de prueba']);
        }

        $this->actingAs($admin)->get('/home-service?area=servicio_tecnico')
            ->assertOk()->assertSee('Retiros de Servicio Técnico')->assertSee('PRE-ST-1')->assertDontSee('PRE-RD-1');
        $this->actingAs($admin)->get('/home-service?area=recuperadata')
            ->assertOk()->assertSee('Retiros de Recuperadata')->assertSee('PRE-RD-1')->assertDontSee('PRE-ST-1')
            ->assertSee(route('home-service.create',['area'=>'recuperadata']),false);
        $this->actingAs($admin)->get('/home-service/mobile-app?area=recuperadata')
            ->assertOk()->assertSee('PRE-RD-1')->assertDontSee('PRE-ST-1');
    }

    public function test_home_service_pickup_can_be_deleted_in_any_status(): void
    {
        Storage::fake('local');
        $company = Company::create(['name' => 'Empresa Eliminar Retiro', 'rut' => '76.555.555-5']);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin', 'is_active' => true]);
        $client = Client::create(['company_id' => $company->id, 'rut' => '12.345.678-5', 'name' => 'Cliente Retiro']);
        $preorder = HomeServicePreorder::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'code' => 'PRE-DELETE-1',
            'external_quote_code' => 'RET-DELETE-1',
            'service_area' => 'servicio_tecnico',
            'status' => 'finalizada',
            'address' => 'Dirección de prueba',
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)->get('/home-service?area=servicio_tecnico')
            ->assertOk()
            ->assertSee(route('home-service.destroy', $preorder), false)
            ->assertSee('Eliminar');

        $this->actingAs($admin)->delete("/home-service/{$preorder->id}")
            ->assertRedirect(route('home-service.index', ['area' => 'servicio_tecnico']))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('home_service_preorders', ['id' => $preorder->id]);
    }

    public function test_diagnosis_is_registered_before_quoting_in_both_service_areas(): void
    {
        Storage::fake('local');
        $company = Company::create(['name' => 'Empresa Diagnóstico', 'rut' => '76.777.777-7']);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->post('/repair-orders', [
            'company_id' => $company->id,
            'client_rut' => '12.345.678-5',
            'client_name' => 'Cliente Diagnóstico',
            'status' => 'ingresado',
            'reported_problem' => 'No enciende.',
            'devices' => [['type' => 'Notebook', 'brand' => 'Lenovo']],
        ]);
        $order = RepairOrder::firstOrFail();

        $this->actingAs($admin)->get("/repair-orders/{$order->id}")
            ->assertOk()->assertSee('Registrar diagnóstico')->assertDontSee('Crear presupuesto');
        $this->actingAs($admin)->post("/repair-orders/{$order->id}/diagnosis", [
            'technical_diagnosis' => 'Falla en circuito de carga.',
            'diagnosis_photos' => [
                UploadedFile::fake()->image('placa.jpg', 1800, 1200),
                UploadedFile::fake()->image('conector.png', 1200, 900),
                UploadedFile::fake()->image('medicion.webp', 900, 1200),
            ],
        ])->assertRedirect("/repair-orders/{$order->id}");
        $this->assertSame('diagnosticado', $order->fresh()->status);
        $this->assertSame('Falla en circuito de carga.', $order->fresh()->technical_diagnosis);
        $diagnosisPhotos = $order->photos()->where('category', 'diagnostico')->get();
        $this->assertCount(3, $diagnosisPhotos);
        $diagnosisPhotos->each(fn ($photo) => Storage::disk('local')->assertExists($photo->path));
        $this->actingAs($admin)->get("/repair-orders/{$order->id}")
            ->assertOk()->assertSee('Crear presupuesto');
        $this->actingAs($admin)->get("/repair-orders/{$order->id}/print")
            ->assertOk()
            ->assertDontSee(route('repair-order-photos.show', $diagnosisPhotos->first()), false);
        $quote = RepairQuote::create([
            'company_id' => $company->id,
            'repair_order_id' => $order->id,
            'quote_number' => 'PRES-DIAG-001',
            'status' => 'emitido',
            'diagnosis_summary' => 'Falla en circuito de carga.',
            'net_amount' => 100000,
            'tax_amount' => 19000,
            'total_amount' => 119000,
        ]);
        $this->actingAs($admin)->get("/repair-quotes/{$quote->id}/print")
            ->assertOk()
            ->assertSee('Diagnóstico')
            ->assertSee(route('repair-order-photos.show', $diagnosisPhotos->first()), false);
        $client = Client::where('company_id', $company->id)->firstOrFail();
        $case = DataRecoveryCase::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'received_by' => $admin->id,
            'code' => 'RD-000001',
            'status' => 'recibido',
            'unit_type' => 'SSD',
            'received_at' => now(),
        ]);
        $this->actingAs($admin)->post("/data-recovery/{$case->id}/diagnosis", [
            'diagnosis' => 'Daño lógico recuperable.',
        ])->assertRedirect("/data-recovery/{$case->id}");
        $this->assertSame('diagnosticado', $case->fresh()->status);
        $this->actingAs($admin)->get("/data-recovery/{$case->id}")
            ->assertOk()->assertSee('Crear presupuesto')->assertSee('Daño lógico recuperable.');
    }

    public function test_home_service_form_looks_up_existing_clients_by_rut(): void
    {
        $company = Company::create(['name' => 'Empresa Retiros', 'rut' => '76.888.888-5']);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin', 'is_active' => true]);
        Client::create([
            'company_id' => $company->id,
            'rut' => '12.345.678-5',
            'name' => 'Cliente existente',
            'phone' => '+56 9 1234 5678',
            'email' => 'existente@example.com',
            'address' => 'Dirección existente 123',
        ]);

        $this->actingAs($admin)->get('/home-service/create')
            ->assertOk()
            ->assertSee('id="client-rut"', false)
            ->assertSee(route('clients.lookup-by-rut'), false);

        $this->actingAs($admin)->getJson('/clients/lookup-by-rut?company_id='.$company->id.'&rut=12.345.678-5')
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('client.name', 'Cliente existente')
            ->assertJsonPath('client.email', 'existente@example.com')
            ->assertJsonPath('client.address', 'Dirección existente 123');
    }

    public function test_home_service_visit_can_close_without_creating_an_order(): void
    {
        $company = Company::create(['name' => 'Empresa Visitas', 'rut' => '76.999.999-9']);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin', 'is_active' => true]);
        $client = Client::create(['company_id' => $company->id, 'rut' => '12.345.678-5', 'name' => 'Cliente visita']);
        $preorder = HomeServicePreorder::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'code' => 'PRE-AUSENTE-1',
            'external_quote_code' => 'RET-AUSENTE-1',
            'service_area' => 'servicio_tecnico',
            'status' => 'agendada',
            'address' => 'Dirección de visita',
        ]);

        $this->actingAs($admin)->get("/home-service/{$preorder->id}/mobile")
            ->assertOk()
            ->assertSee('Cliente ausente')
            ->assertSee('Otro')
            ->assertSee('Cerrar visita sin retiro');

        $this->actingAs($admin)->post("/home-service/{$preorder->id}/close-without-pickup", [
            'outcome' => 'cliente_ausente',
        ])->assertRedirect(route('home-service.mobile-app', ['area' => 'servicio_tecnico']));

        $preorder->refresh();
        $this->assertSame('cliente_ausente', $preorder->status);
        $this->assertNotNull($preorder->completed_at);
        $this->assertNull($preorder->repair_order_id);
        $this->assertNull($preorder->data_recovery_case_id);
    }

    public function test_other_visit_outcome_requires_details(): void
    {
        $company = Company::create(['name' => 'Empresa Otro', 'rut' => '77.111.111-9']);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin', 'is_active' => true]);
        $client = Client::create(['company_id' => $company->id, 'rut' => '12.345.678-5', 'name' => 'Cliente visita']);
        $preorder = HomeServicePreorder::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'code' => 'PRE-OTRO-1',
            'external_quote_code' => 'RET-OTRO-1',
            'service_area' => 'servicio_tecnico',
            'status' => 'agendada',
            'address' => 'Dirección de visita',
        ]);

        $this->actingAs($admin)->post("/home-service/{$preorder->id}/close-without-pickup", [
            'outcome' => 'otro',
        ])->assertSessionHasErrors('outcome_details');

        $this->actingAs($admin)->post("/home-service/{$preorder->id}/close-without-pickup", [
            'outcome' => 'otro',
            'outcome_details' => 'La dirección estaba inaccesible por cierre de calle.',
        ])->assertRedirect();

        $this->assertSame('otro', $preorder->fresh()->status);
        $this->assertSame('La dirección estaba inaccesible por cierre de calle.', $preorder->fresh()->outcome_details);
    }
}
