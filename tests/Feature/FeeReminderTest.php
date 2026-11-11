<?php

namespace Tests\Feature;

use App\Models\CommunicationEventLog;
use App\Models\FeeInvoice;
use App\Models\Guardian;
use App\Models\OutboundMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FeeReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_invoice_creates_one_guardian_reminder_per_day(): void
    {
        $this->seed();
        $invoice = FeeInvoice::firstOrFail();
        $invoice->update(['due_date' => today()->addDays(2), 'status' => 'issued']);
        $g = Guardian::create(['school_id' => $invoice->school_id, 'public_id' => (string) Str::ulid(), 'name' => 'Parent', 'relationship' => 'Mother', 'phone' => '+923001118888', 'whatsapp_opt_in' => true]);
        $invoice->student->guardians()->attach($g, ['primary_contact' => true]);
        $this->artisan('school:fees:remind', ['--days-before' => 3])->expectsOutput('Created 1 fee reminder(s).')->assertSuccessful();
        $this->artisan('school:fees:remind', ['--days-before' => 3])->expectsOutput('Created 0 fee reminder(s).')->assertSuccessful();
        $message = OutboundMessage::where('event', 'fee_due')->firstOrFail();
        $this->assertStringContainsString('due in 2 day(s)', $message->body);
        $this->assertDatabaseCount('outbound_messages', 1);
    }

    public function test_future_invoice_outside_reminder_window_is_ignored(): void
    {
        $this->seed();
        FeeInvoice::firstOrFail()->update(['due_date' => today()->addDays(10)]);
        $before = CommunicationEventLog::where(['event' => 'fee_due', 'status' => 'created'])->count();
        $this->artisan('school:fees:remind', ['--days-before' => 3])->expectsOutput('Created 0 fee reminder(s).');
        $this->assertSame($before, CommunicationEventLog::where(['event' => 'fee_due', 'status' => 'created'])->count());
    }
}
