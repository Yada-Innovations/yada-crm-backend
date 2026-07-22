<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Ledger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    public function overview(Request $request)
    {
        $balances = DB::table('chart_of_accounts')
            ->leftJoin('ledgers', 'chart_of_accounts.id', '=', 'ledgers.chart_of_account_id')
            ->select(
                'chart_of_accounts.type',
                DB::raw("SUM(CASE WHEN ledgers.type = 'debit' THEN ledgers.amount ELSE -ledgers.amount END) as balance")
            )
            ->groupBy('chart_of_accounts.type')
            ->get();

        $assets = 0;
        $liabilities = 0;
        $equity = 0;
        $revenue = 0;
        $expense = 0;

        foreach ($balances as $b) {
            $amount = (float) $b->balance;
            switch ($b->type) {
                case 'asset': $assets = $amount; break;
                case 'expense': $expense = $amount; break;
                case 'liability': $liabilities = -$amount; break;
                case 'equity': $equity = -$amount; break;
                case 'revenue': $revenue = -$amount; break;
            }
        }

        return response()->json([
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'revenue' => $revenue,
            'expense' => $expense,
            'net_income' => $revenue - $expense
        ]);
    }

    public function chartOfAccounts(Request $request)
    {
        $accounts = ChartOfAccount::orderBy('code')->get();
        return response()->json($accounts);
    }

    public function storeChartOfAccount(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|unique:chart_of_accounts,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'description' => 'nullable|string',
        ]);

        $account = ChartOfAccount::create([
            'id' => Str::uuid(),
            ...$data,
            'is_active' => true,
        ]);

        return response()->json($account, 201);
    }

    public function ledgers(Request $request)
    {
        $ledgers = Ledger::with('account')
            ->orderBy('entry_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($ledgers);
    }

    public function storeLedger(Request $request)
    {
        $data = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'nullable|string',
            'reference' => 'nullable|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
        ]);

        $totalDebit = collect($data['lines'])->sum('debit');
        $totalCredit = collect($data['lines'])->sum('credit');

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return response()->json(['message' => 'Total debits must equal total credits.'], 400);
        }
        if ($totalDebit <= 0) {
            return response()->json(['message' => 'Entry must have a non-zero amount.'], 400);
        }

        $transactionGroupId = Str::uuid();
        $globalDesc = $data['description'] ?? 'Journal Entry';
        if (!empty($data['reference'])) {
            $globalDesc .= ' (Ref: ' . $data['reference'] . ')';
        }

        foreach ($data['lines'] as $line) {
            if ($line['debit'] > 0) {
                Ledger::create([
                    'chart_of_account_id' => $line['account_id'],
                    'transaction_group_id' => $transactionGroupId,
                    'type' => 'debit',
                    'amount' => $line['debit'],
                    'entry_date' => $data['entry_date'],
                    'description' => $line['description'] ?: $globalDesc,
                ]);
            }
            if ($line['credit'] > 0) {
                Ledger::create([
                    'chart_of_account_id' => $line['account_id'],
                    'transaction_group_id' => $transactionGroupId,
                    'type' => 'credit',
                    'amount' => $line['credit'],
                    'entry_date' => $data['entry_date'],
                    'description' => $line['description'] ?: $globalDesc,
                ]);
            }
        }

        return response()->json(['message' => 'Journal entry created'], 201);
    }

    public function updateLedger(Request $request, $transactionGroupId)
    {
        $data = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'nullable|string',
            'reference' => 'nullable|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
        ]);

        $totalDebit = collect($data['lines'])->sum('debit');
        $totalCredit = collect($data['lines'])->sum('credit');

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return response()->json(['message' => 'Total debits must equal total credits.'], 400);
        }
        if ($totalDebit <= 0) {
            return response()->json(['message' => 'Entry must have a non-zero amount.'], 400);
        }

        Ledger::where('transaction_group_id', $transactionGroupId)->delete();

        $globalDesc = $data['description'] ?? 'Journal Entry';
        if (!empty($data['reference'])) {
            $globalDesc .= ' (Ref: ' . $data['reference'] . ')';
        }

        foreach ($data['lines'] as $line) {
            if ($line['debit'] > 0) {
                Ledger::create([
                    'chart_of_account_id' => $line['account_id'],
                    'transaction_group_id' => $transactionGroupId,
                    'type' => 'debit',
                    'amount' => $line['debit'],
                    'entry_date' => $data['entry_date'],
                    'description' => $line['description'] ?: $globalDesc,
                ]);
            }
            if ($line['credit'] > 0) {
                Ledger::create([
                    'chart_of_account_id' => $line['account_id'],
                    'transaction_group_id' => $transactionGroupId,
                    'type' => 'credit',
                    'amount' => $line['credit'],
                    'entry_date' => $data['entry_date'],
                    'description' => $line['description'] ?: $globalDesc,
                ]);
            }
        }

        return response()->json(['message' => 'Ledger updated']);
    }

    public function destroyLedger($transactionGroupId)
    {
        Ledger::where('transaction_group_id', $transactionGroupId)->delete();
        return response()->json(['message' => 'Ledger deleted']);
    }

    public function incomeStatement(Request $request)
    {
        $from = $request->get('from', Carbon::now()->startOfYear()->toDateString());
        $to = $request->get('to', Carbon::now()->endOfYear()->toDateString());

        $accounts = ChartOfAccount::whereIn('type', ['revenue', 'expense'])->get();
        $ledgers = Ledger::whereBetween('entry_date', [$from, $to])
            ->whereIn('chart_of_account_id', $accounts->pluck('id'))
            ->get();

        $revenueData = [];
        $expenseData = [];
        $totalRevenue = 0;
        $totalExpense = 0;

        foreach ($accounts as $account) {
            $accountLedgers = $ledgers->where('chart_of_account_id', $account->id);
            $debits = $accountLedgers->where('type', 'debit')->sum('amount');
            $credits = $accountLedgers->where('type', 'credit')->sum('amount');

            if ($account->type === 'revenue') {
                $balance = $credits - $debits;
                $revenueData[] = ['account' => $account, 'balance' => $balance];
                $totalRevenue += $balance;
            } else {
                $balance = $debits - $credits;
                $expenseData[] = ['account' => $account, 'balance' => $balance];
                $totalExpense += $balance;
            }
        }

        return response()->json([
            'revenues' => $revenueData,
            'expenses' => $expenseData,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net_income' => $totalRevenue - $totalExpense,
            'period' => ['from' => $from, 'to' => $to]
        ]);
    }

    public function balanceSheet(Request $request)
    {
        $date = $request->get('date', Carbon::now()->toDateString());

        $accounts = ChartOfAccount::whereIn('type', ['asset', 'liability', 'equity'])->get();
        $ledgers = Ledger::where('entry_date', '<=', $date)
            ->whereIn('chart_of_account_id', $accounts->pluck('id'))
            ->get();

        $assetData = [];
        $liabilityData = [];
        $equityData = [];
        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;

        foreach ($accounts as $account) {
            $accountLedgers = $ledgers->where('chart_of_account_id', $account->id);
            $debits = $accountLedgers->where('type', 'debit')->sum('amount');
            $credits = $accountLedgers->where('type', 'credit')->sum('amount');

            if ($account->type === 'asset') {
                $balance = $debits - $credits;
                $assetData[] = ['account' => $account, 'balance' => $balance];
                $totalAssets += $balance;
            } elseif ($account->type === 'liability') {
                $balance = $credits - $debits;
                $liabilityData[] = ['account' => $account, 'balance' => $balance];
                $totalLiabilities += $balance;
            } else {
                $balance = $credits - $debits;
                $equityData[] = ['account' => $account, 'balance' => $balance];
                $totalEquity += $balance;
            }
        }

        $isAccounts = ChartOfAccount::whereIn('type', ['revenue', 'expense'])->get();
        $isLedgers = Ledger::where('entry_date', '<=', $date)
            ->whereIn('chart_of_account_id', $isAccounts->pluck('id'))
            ->get();
        
        $netIncome = 0;
        foreach ($isAccounts as $account) {
            $accountLedgers = $isLedgers->where('chart_of_account_id', $account->id);
            $debits = $accountLedgers->where('type', 'debit')->sum('amount');
            $credits = $accountLedgers->where('type', 'credit')->sum('amount');
            if ($account->type === 'revenue') {
                $netIncome += ($credits - $debits);
            } else {
                $netIncome -= ($debits - $credits);
            }
        }

        $totalEquity += $netIncome;

        return response()->json([
            'assets' => $assetData,
            'liabilities' => $liabilityData,
            'equity' => $equityData,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'net_income' => $netIncome,
            'date' => $date
        ]);
    }
}