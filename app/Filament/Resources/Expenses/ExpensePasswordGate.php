<?php

namespace App\Filament\Resources\Expenses;

use App\Models\Expense;
use Filament\Forms\Components\TextInput;

/**
 * Editing or deleting an expense requires re-entering the login password.
 *
 * Confirming the password grants a one-time pass to open that expense's edit page,
 * so opening the edit URL directly (or reusing it later) asks for the password again.
 */
class ExpensePasswordGate
{
    private const SESSION_KEY = 'expense_edit_grants';

    private const GRANT_TTL_SECONDS = 120;

    public static function passwordField(): TextInput
    {
        return TextInput::make('password')
            ->label('كلمة المرور')
            ->password()
            ->revealable()
            ->required()
            ->currentPassword()
            ->autocomplete('current-password');
    }

    public static function grantEditAccess(Expense $expense): void
    {
        session()->put(self::SESSION_KEY.'.'.$expense->getKey(), now()->addSeconds(self::GRANT_TTL_SECONDS)->getTimestamp());
    }

    /**
     * Use up the pass for this expense; true only if a fresh one existed.
     */
    public static function consumeEditAccess(Expense $expense): bool
    {
        $expiresAt = session()->pull(self::SESSION_KEY.'.'.$expense->getKey());

        return $expiresAt !== null && $expiresAt >= now()->getTimestamp();
    }
}
