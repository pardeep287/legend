<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BankStatement extends Model
{
    protected $table = 'bank_statement';

    protected $fillable = [
        'bank_account',
        'date',
        'narrative',
        'debit_amount',
        'credit_amount',
        'category',
        'serial',
        'bank_account_id',
        'bank_id',
        'created_at',
        'updated_at',
    ];

    /**
     * @param $query
     * @return mixed
     */
    public function scopeFinancialYear($query)
    {
        return $query->where('bank_statement.financial_year_id', financialYearId());
    }

    /**
     * @param $inputs
     * @return mixed
     */
    public function saveStatement($inputs)
    {
        return $this->insert($inputs);
    }

    /**
     * @param array $search
     *
     * @return null
     */
    public function bankStatementReport($search = [])
    {
        $fields = [
            'bank_statement.id',
            'bank_statement.date',
            'bank_statement.narrative',
            'bank_statement.debit_amount',
            'bank_statement.credit_amount',
            'groups.name as group_name',
            'bank_master.name as bank_name',
        ];

        $filter = 1; // default filter if no search
        if(array_key_exists('form-search', $search)) {
            if (is_array($search) && count($search) > 0) {
                $f1 = (array_key_exists('group', $search) && $search['group'] != "") ? " AND (groups.id = " .
                    addslashes(trim($search['group'])) . ")" : "";

                $f2 = (array_key_exists('bank', $search) && $search['bank'] != "") ? " AND (bank_master.id = " .
                    addslashes(trim($search['bank'])) . ")" : "";

                if (array_key_exists('from_date', $search) && $search['from_date'] != "" && $search['to_date'] == "" && $search['report_type'] == '1') {
                    $filter .= " and " . \DB::raw('DATE_FORMAT(date, "%Y-%m-%d")') . " = '" . dateFormat('Y-m-d', $search['from_date']) . "' ";
                }

                if (array_key_exists('from_date', $search) && $search['from_date'] != "" &&
                    array_key_exists('to_date', $search) && $search['to_date'] != "" && $search['report_type'] == '1'
                )
                {
                    $filter .= " and " . \DB::raw('DATE_FORMAT(date, "%Y-%m-%d")') . " between '" . dateFormat('Y-m-d', $search['from_date']) . "' and
                    '" . dateFormat('Y-m-d', $search['to_date']) . "'";
                }

                if (array_key_exists('month', $search) && $search['month'] != "" && $search['report_type'] == '2') {
                    $filter .= " and " . \DB::raw('DATE_FORMAT(date, "%m")') . " = '" . paddingLeft($search['month']) . "' ";
                }

                if (array_key_exists('year', $search) && $search['year'] != "" && $search['report_type'] == '3') {
                    $filter .= " and " . \DB::raw('DATE_FORMAT(date, "%Y")') . " = '" . $search['year'] . "' ";
                }

                $filter .= $f1 . $f2;
                return $this->leftJoin('groups', 'groups.id', '=', 'bank_statement.bank_account_id')
                    ->leftJoin('bank_master', 'bank_master.id', '=', 'bank_statement.bank_id')
                    ->where('bank_master.company_id', loggedInCompanyId())
                    ->FinancialYear()
                    ->whereNull('bank_master.deleted_at')
                    ->whereRaw($filter)
                    ->get($fields);
            }
        }
        return null;
    }
}
