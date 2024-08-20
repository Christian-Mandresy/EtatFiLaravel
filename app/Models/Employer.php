<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Employer extends Model
{
    use HasFactory;

    protected $table = 'employer';
    public $timestamps = false;

    // Function to calculate total payments for the current month
    public function calculateTotalPaymentsForCurrentMonth()
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $totalPayments = DB::table('employerpaie')
            ->join('transaction', 'employerpaie.idtransaction', '=', 'transaction.id')
            ->where('employerpaie.idemployer', $this->id)
            ->whereMonth('transaction.datetransaction', $currentMonth)
            ->whereYear('transaction.datetransaction', $currentYear)
            ->sum('transaction.montant');

        return $totalPayments;
    }

    // Function to process payment
    public function processPayment($amount)
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Fetch the total salary of the employer
        $totalSalary = $this->salaire;

        // Fetch total payments made in the current month
        $totalPayments = $this->calculateTotalPaymentsForCurrentMonth();

        // Check if the amount exceeds the remaining salary
        $remainingSalary = $totalSalary - $totalPayments;

        if ($amount > $remainingSalary) {
            return [
                'status' => 'error',
                'message' => 'Amount exceeds remaining salary for the month.',
            ];
        }

        // Insert transaction
        $transaction = DB::table('transaction')->insertGetId([
            'idcaisse' => 1, // Assuming a default caisse id
            'montant' => $amount,
            'datetransaction' => now(),
            'typetransaction' => 1, // Assuming 1 means "dépense"
            'description' => 'Employer payment',
        ]);

        // Insert employerpaie record
        DB::table('employerpaie')->insert([
            'idemployer' => $this->id,
            'idtransaction' => $transaction,
        ]);

        return [
            'status' => 'success',
            'message' => 'Payment processed successfully.',
        ];
    }
}
