<?php

namespace Tests\Feature;

use App\Models\FeeConcession;
use App\Models\FeeInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeConcessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_concession_requires_approval_before_adjusting_invoice(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $invoice = FeeInvoice::firstOrFail();
        $original = $invoice->total;
        $this->actingAs($u)->post(route('concessions.store', $invoice), ['type' => 'scholarship', 'amount' => 1000, 'reason' => 'Merit scholarship'])->assertRedirect();
        $c = FeeConcession::firstOrFail();
        $this->assertSame('pending', $c->status);
        $this->assertSame($original, $invoice->refresh()->total);
        $this->actingAs($u)->post(route('concessions.approve', $c))->assertRedirect();
        $this->assertSame('approved', $c->refresh()->status);
        $this->assertSame($original - 100000, $invoice->refresh()->total);
        $this->assertSame(100000, $invoice->discount);
    }

    public function test_concession_cannot_exceed_outstanding_balance_or_apply_twice(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $invoice = FeeInvoice::firstOrFail();
        $this->actingAs($u)->post(route('concessions.store', $invoice), ['type' => 'special', 'amount' => ($invoice->balance / 100) + 1, 'reason' => 'Invalid'])->assertStatus(422);
        $c = FeeConcession::create(['school_id' => $invoice->school_id, 'student_id' => $invoice->student_id, 'fee_invoice_id' => $invoice->id, 'type' => 'special', 'amount' => 10000, 'reason' => 'Approved case', 'requested_by' => $u->id]);
        $this->actingAs($u)->post(route('concessions.approve', $c))->assertRedirect();
        $this->actingAs($u)->post(route('concessions.approve', $c))->assertStatus(422);
    }
}
