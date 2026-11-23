<?php

namespace App\Filament\Resources\Patients\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات المريض')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم المريض')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('رقم التليفون')
                            ->tel()
                            ->required()
                            ->length(11)
                            ->regex('/^01[0125]\d{8}$/')
                            ->validationMessages([
                                'regex' => 'رقم التليفون يجب أن يكون 11 رقمًا ويبدأ بـ 010 أو 011 أو 012 أو 015.',
                            ])
                            ->placeholder('01xxxxxxxxx')
                            ->extraInputAttributes(['dir' => 'ltr']),
                        DatePicker::make('admitted_on')
                            ->label('تاريخ الدخول')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(now())
                            ->maxDate(now()),
                        TextInput::make('arrival_supervisor')
                            ->label('مشرف الوصول')
                            ->required()
                            ->maxLength(255),
                        DatePicker::make('discharged_on')
                            ->label('تاريخ الخروج')
                            ->helperText('اتركه فارغًا طالما المريض مقيم.')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->afterOrEqual('admitted_on'),
                    ]),
            ]);
    }
}
