<?php

namespace App\Filament\Resources\Incomes\Tables;

use App\Models\Income;
use App\Support\Money;
use App\Support\XlsxDownload;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

class IncomesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('received_on', 'desc')
            ->columns([
                TextColumn::make('received_on')
                    ->label('تاريخ الدخل')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('source.name')
                    ->label('مصدر الدخل')
                    ->badge()
                    ->color('success')
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
                Filter::make('received_between')
                    ->label('الفترة')
                    ->schema([
                        DatePicker::make('from')->label('من تاريخ')->native(false)->displayFormat('d/m/Y'),
                        DatePicker::make('until')->label('إلى تاريخ')->native(false)->displayFormat('d/m/Y'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('received_on', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('received_on', '<=', $date)))
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
                SelectFilter::make('income_source_id')
                    ->label('مصدر الدخل')
                    ->relationship('source', 'name')
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
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Export the income records currently shown in the table (with filters applied).
     */
    private static function export(HasTable $livewire): StreamedResponse
    {
        $incomes = $livewire->getFilteredSortedTableQuery()->with(['source', 'patient'])->get();

        $rows = $incomes->map(fn (Income $income): array => [
            $income->received_on->format('Y-m-d'),
            $income->source->name,
            (float) $income->amount,
            $income->patient?->name,
        ])->all();

        $rows[] = ['الإجمالي', null, (float) $incomes->sum('amount'), null];

        return XlsxDownload::make(
            'الدخل-'.now()->format('Y-m-d'),
            ['تاريخ الدخل', 'مصدر الدخل', 'القيمة (ج.م)', 'المريض'],
            $rows,
        );
    }
}
