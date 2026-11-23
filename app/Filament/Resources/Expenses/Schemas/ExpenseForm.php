<?php

namespace App\Filament\Resources\Expenses\Schemas;

use App\Models\Expense;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات المصروف')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('amount')
                            ->label('قيمة المصروف')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->maxValue(9999999999)
                            ->step(0.01)
                            ->suffix('ج.م'),
                        Select::make('expense_category_id')
                            ->label('سبب الصرف')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        DatePicker::make('spent_on')
                            ->label('تاريخ المصروف')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(now())
                            ->maxDate(now()),
                        Select::make('patient_id')
                            ->label('المريض (اختياري)')
                            ->helperText('اختر المريض لو المصروف خاص بمريض معين.')
                            ->relationship(
                                'patient',
                                'name',
                                modifyQueryUsing: fn (Builder $query, ?Expense $record): Builder => $query->where(
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
