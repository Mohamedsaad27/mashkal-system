<?php

namespace App\Filament\Resources\Expenses\Tables;

use App\Filament\Resources\Expenses\ExpensePasswordGate;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Models\Expense;
use App\Support\Money;
use App\Support\XlsxDownload;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('spent_on', 'desc')
            ->recordUrl(null)
            ->columns([
                TextColumn::make('spent_on')
                    ->label('تاريخ المصروف')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('سبب الصرف')
                    ->badge()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('القيمة')
                    ->formatStateUsing(fn (float|string|null $state): string => Money::format($state))
                    ->sortable(),
                TextColumn::make('patient.name')
                    ->label('المريض')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإدخال')
                    ->dateTime('d/m/Y h:i A')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('spent_between')
                    ->label('الفترة')
                    ->schema([
                        DatePicker::make('from')->label('من تاريخ')->native(false)->displayFormat('d/m/Y'),
                        DatePicker::make('until')->label('إلى تاريخ')->native(false)->displayFormat('d/m/Y'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('spent_on', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('spent_on', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators[] = 'من '.date('d/m/Y', strtotime($data['from']));
                        }

                        if ($data['until'] ?? null) {
                            $indicators[] = 'إلى '.date('d/m/Y', strtotime($data['until']));
                        }

                        return $indicators;
                    }),
                SelectFilter::make('expense_category_id')
                    ->label('سبب الصرف')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload(),
                SelectFilter::make('patient_id')
                    ->label('المريض')
                    ->relationship('patient', 'name')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make()
                    ->label('سلة المحذوفات'),
            ])
            ->headerActions([
                Action::make('exportExcel')
                    ->label('تصدير Excel')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->action(fn (HasTable $livewire): StreamedResponse => self::export($livewire)),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->hidden(fn (Expense $record): bool => $record->trashed())
                    ->modalHeading('تأكيد كلمة المرور')
                    ->modalDescription('أدخل كلمة المرور لتعديل هذا المصروف.')
                    ->modalSubmitActionLabel('متابعة')
                    ->modalWidth('md')
                    ->schema([ExpensePasswordGate::passwordField()])
                    ->action(function (Expense $record, HasTable $livewire): void {
                        ExpensePasswordGate::grantEditAccess($record);

                        $livewire->redirect(ExpenseResource::getUrl('edit', ['record' => $record]));
                    }),
                DeleteAction::make()
                    ->modalDescription('أدخل كلمة المرور لحذف هذا المصروف (سيذهب إلى سلة المحذوفات).')
                    ->schema([ExpensePasswordGate::passwordField()]),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalDescription('أدخل كلمة المرور لحذف المصاريف المحددة (ستذهب إلى سلة المحذوفات).')
                        ->schema([ExpensePasswordGate::passwordField()]),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Export the expenses currently shown in the table (with filters applied).
     */
    private static function export(HasTable $livewire): StreamedResponse
    {
        $expenses = $livewire->getFilteredSortedTableQuery()->with(['category', 'patient'])->get();

        $rows = $expenses->map(fn (Expense $expense): array => [
            $expense->spent_on->format('Y-m-d'),
            $expense->category->name,
            (float) $expense->amount,
            $expense->patient?->name,
        ])->all();

        $rows[] = ['الإجمالي', null, (float) $expenses->sum('amount'), null];

        return XlsxDownload::make(
            'المصاريف-'.now()->format('Y-m-d'),
            ['تاريخ المصروف', 'سبب الصرف', 'القيمة (ج.م)', 'المريض'],
            $rows,
        );
    }
}
