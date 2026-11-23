<?php

namespace App\Filament\Resources\ExpenseCategories;

use App\Filament\Resources\ExpenseCategories\Pages\ManageExpenseCategories;
use App\Models\ExpenseCategory;
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

class ExpenseCategoryResource extends Resource
{
    protected static ?string $model = ExpenseCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'المصاريف';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'سبب صرف';

    protected static ?string $pluralModelLabel = 'أسباب الصرف';

    protected static ?string $navigationLabel = 'أسباب الصرف';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('سبب الصرف')
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
                    ->label('سبب الصرف')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('expenses_count')
                    ->label('عدد المصاريف')
                    ->counts('expenses')
                    ->sortable(),
                TextColumn::make('expenses_sum_amount')
                    ->label('إجمالي المصروف')
                    ->sum('expenses', 'amount')
                    ->formatStateUsing(fn (float|string|null $state): string => Money::format($state))
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (ExpenseCategory $record): bool => ! $record->isDeletable())
                    ->tooltip(fn (ExpenseCategory $record): ?string => $record->isDeletable()
                        ? null
                        : 'لا يمكن حذف سبب مسجل عليه مصاريف، يمكنك تعديل اسمه فقط.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageExpenseCategories::route('/'),
        ];
    }
}
