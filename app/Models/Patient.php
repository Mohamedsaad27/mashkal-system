<?php

namespace App\Models;

use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'phone', 'admitted_on', 'arrival_supervisor', 'discharged_on'])]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'admitted_on' => 'date',
            'discharged_on' => 'date',
        ];
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * @return HasMany<Income, $this>
     */
    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    /**
     * A patient is resident while they have no discharge date.
     *
     * @return Attribute<bool, never>
     */
    protected function isResident(): Attribute
    {
        return Attribute::get(fn (): bool => $this->discharged_on === null);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function resident(Builder $query): void
    {
        $query->whereNull('discharged_on');
    }
}
