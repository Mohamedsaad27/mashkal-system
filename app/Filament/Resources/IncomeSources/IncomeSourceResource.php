<?php

namespace App\Filament\Resources\IncomeSources;

use App\Filament\Resources\IncomeSources\Pages\ManageIncomeSources;
use App\Models\IncomeSource;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class IncomeSourceResource extends Resource
{
    protected static ?string $model = IncomeSource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'الدخل';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'مصدر دخل';

    protected static ?string $pluralModelLabel = 'مصادر الدخل';

    protected static ?string $navigationLabel = 'مصادر الدخل';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('مصدر الدخل')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('مصدر الدخل')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('incomes_count')
                    ->label('عدد مرات الدخل')
                    ->counts('incomes')
                    ->sortable(),
                TextColumn::make('incomes_sum_amount')
                    ->label('إجمالي الدخل')
                    ->sum('incomes', 'amount')
                    ->formatStateUsing(fn (float|string|null $state): string => Money::format($state))
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (IncomeSource $record): bool => ! $record->isDeletable())
                    ->tooltip(fn (IncomeSource $record): ?string => $record->isDeletable()
                        ? null
                        : 'لا يمكن حذف مصدر مسجل عليه دخل، يمكنك تعديل اسمه فقط.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageIncomeSources::route('/'),
        ];
    }
}
