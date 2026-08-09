<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ImportBankStatementAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBankStatementImportRequest;
use Illuminate\Http\RedirectResponse;

class BankStatementImportController extends Controller
{
    public function store(StoreBankStatementImportRequest $request, ImportBankStatementAction $importBankStatement): RedirectResponse
    {
        $result = $importBankStatement->handle($request->file('statement')->getContent());

        return back()->with('import_result', [
            'created' => $result->created,
            'duplicates' => $result->duplicates,
            'matched' => $result->matched,
            'unmatched' => $result->unmatched,
        ]);
    }
}
