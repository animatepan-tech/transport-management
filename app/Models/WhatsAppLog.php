<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppLog extends Model
{
    /**
     * Actual database table name.
     *
     * IMPORTANT:
     * The migration created:
     *
     * whatsapp_logs
     *
     * Laravel would normally infer:
     *
     * whats_app_logs
     *
     * Therefore this must remain explicitly defined.
     */
    protected $table = 'whatsapp_logs';

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'student_id',
        'fee_id',
        'phone',
        'template_name',
        'message_type',
        'balance_at_send',
        'status',
        'provider_message_id',
        'error_message',
        'sent_at',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'balance_at_send' => 'decimal:2',
        'sent_at' => 'datetime',
    ];

    /**
     * WhatsApp log belongs to a student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(
            Student::class,
            'student_id'
        );
    }

    /**
     * WhatsApp log may belong to a fee.
     *
     * fee_id is nullable in the database, so
     * this relationship may return null.
     */
    public function fee(): BelongsTo
    {
        return $this->belongsTo(
            Fee::class,
            'fee_id'
        );
    }
}