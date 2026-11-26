<?php

namespace App\Filament\Resources\Incomes\Schemas;

use App\Models\Income;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class IncomeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الدخل')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('amount')
                            ->label('قيمة الدخل')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->maxValue(9999999999)
                            ->step(0.01)
                            ->suffix('ج.م'),
                        Select::make('income_source_id')
                            ->label('مصدر الدخل')
                            ->relationship('source', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        DatePicker::make('received_on')
                            ->label('تاريخ الدخل')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(now()),
                        Select::make('patient_id')
                            ->label('المريض (اختياري)')
                            ->helperText('اختر المريض لو الدخل من مريض معين.')
                            ->relationship(
                                'patient',
                                'name',
                                modifyQueryUsing: fn (Builder $query, ?Income $record): Builder => $query->where(
                                    fn (Builder $query): Builder => $query
                                        ->whereNull('deleted_at')
                                        ->when($record?->patient_id, fn (Builder $query, int $patientId): Builder => $query->orWhere('id', $patientId)),
                                ),
                            )
                            ->searchable()
                            ->preload(),
                    ]),
            ]);
    }
}
