<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\CashAccount;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManageTransactions extends ManageRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('transfer')->label('Virman (Hesaplar Arası)')->icon(Heroicon::OutlinedArrowsRightLeft)->color('gray')
                ->schema([
                    Select::make('from')->label('Çıkış Hesabı')->options(CashAccount::pluck('name', 'id'))->required(),
                    Select::make('to')->label('Giriş Hesabı')->options(CashAccount::pluck('name', 'id'))->required()->different('from'),
                    TextInput::make('amount')->label('Çıkan Tutar')->numeric()->required(),
                    TextInput::make('amount_in')->label('Giren Tutar (döviz bozdurma ise farklı)')->numeric(),
                    DatePicker::make('date')->label('Tarih')->default(now())->required()->native(false),
                    TextInput::make('description')->label('Açıklama')->default('Virman'),
                ])
                ->action(function (array $data) {
                    DB::transaction(function () use ($data) {
                        $group = (string) Str::uuid();
                        $from = CashAccount::findOrFail($data['from']);
                        $to = CashAccount::findOrFail($data['to']);
                        $amountIn = $data['amount_in'] ?: $data['amount'];
                        $rateOut = $from->currency === 'TRY' ? 1 : app(\App\Services\ExchangeRateService::class)->rate($from->currency);
                        Transaction::create([
                            'type' => 'expense', 'category' => 'transfer_out', 'cash_account_id' => $from->id,
                            'amount' => $data['amount'], 'exchange_rate' => $rateOut ?: 1, 'date' => $data['date'],
                            'description' => $data['description'].' → '.$to->name, 'transfer_group' => $group,
                        ]);
                        Transaction::create([
                            'type' => 'income', 'category' => 'transfer_in', 'cash_account_id' => $to->id,
                            'amount' => $amountIn, 'exchange_rate' => $to->currency === 'TRY' ? 1 : round($data['amount'] * ($rateOut ?: 1) / $amountIn, 4),
                            'date' => $data['date'], 'description' => $data['description'].' ← '.$from->name, 'transfer_group' => $group,
                        ]);
                    });
                })
                ->successNotificationTitle('Virman kaydedildi'),
            CreateAction::make()->label('Yeni Hareket'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tümü'),
            'income' => Tab::make('Gelirler')->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'income')),
            'expense' => Tab::make('Giderler')->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'expense')),
            'month' => Tab::make('Bu Ay')->modifyQueryUsing(fn (Builder $query) => $query->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])),
        ];
    }
}
