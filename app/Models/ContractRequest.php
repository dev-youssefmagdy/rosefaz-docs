<?php

namespace App\Models;

use App\Enums\RentalType;
use Carbon\CarbonInterface;
use Database\Factories\ContractRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use IntlDateFormatter;

#[Fillable([
    'full_name', 'id_number', 'birth_date', 'mobile', 'needs_electricity_bill', 'apartment_no',
    'annual_rent', 'insurance_amount', 'authentication_amount', 'rental_type', 'contract_duration_months',
    'rental_date', 'amount_paid', 'start_date', 'user_id',
])]
class ContractRequest extends Model
{
    /** @use HasFactory<ContractRequestFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'birth_date' => 'date',
            'rental_date' => 'date',
            'start_date' => 'date',
            'viewed_at' => 'datetime',
            'needs_electricity_bill' => 'boolean',
            'annual_rent' => 'decimal:2',
            'insurance_amount' => 'decimal:2',
            'authentication_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'contract_duration_months' => 'integer',
            'rental_type' => RentalType::class,
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(RequestNote::class);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $user->isGeneralManager()
            ? $query
            : $query->where('branch_id', $user->branch_id ?? 0);
    }

    protected function birthDateHijri(): Attribute
    {
        return Attribute::get(fn () => self::toHijri($this->birth_date));
    }

    protected function startDateHijri(): Attribute
    {
        return Attribute::get(fn () => self::toHijri($this->start_date));
    }

    public static function toHijri(?CarbonInterface $date): ?string
    {
        if (! $date) {
            return null;
        }

        $formatter = new IntlDateFormatter(
            'en_US@calendar=islamic-umalqura',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            'UTC',
            IntlDateFormatter::TRADITIONAL,
            'dd/MM/yyyy',
        );

        return $formatter->format($date->copy()->setTimezone('UTC')->startOfDay()) ?: null;
    }
}
