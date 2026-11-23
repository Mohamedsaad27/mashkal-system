<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Filament\Resources\Expenses\ExpensePasswordGate;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Models\Expense;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    /**
     * The edit page only opens after the password was confirmed from the expenses list.
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var Expense $expense */
        $expense = $this->getRecord();

        if (! ExpensePasswordGate::consumeEditAccess($expense)) {
            Notification::make()
                ->title('يجب إدخال كلمة المرور أولًا')
                ->body('اضغط "تعديل" من قائمة المصاريف وأدخل كلمة المرور.')
                ->warning()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalDescription('أدخل كلمة المرور لحذف هذا المصروف (سيذهب إلى سلة المحذوفات).')
                ->schema([ExpensePasswordGate::passwordField()]),
            RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
