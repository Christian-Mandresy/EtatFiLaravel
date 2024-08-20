<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employer;
use App\Models\Transaction;
use App\Models\EmployerPaie;
use Carbon\Carbon;
use App\Models\Caisse;
use DB;
use Log;

class EmployerController extends Controller
{
    public function index()
    {
        $employers = Employer::all();
        return response()->json($employers);
    }

    public function show($id)
    {
        $employer = Employer::findOrFail($id);
        return response()->json($employer);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:70',
            'prenom' => 'required|string|max:70',
            'salaire' => 'required|integer|min:0',
        ]);

        $employer = new Employer();
        $employer->nom = $request->nom;
        $employer->prenom = $request->prenom;
        $employer->salaire = $request->salaire;
        $employer->save();

        return response()->json(['message' => 'Employer created successfully'], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nom' => 'required|string|max:70',
            'prenom' => 'required|string|max:70',
            'salaire' => 'required|integer|min:0',
        ]);

        $employer = Employer::findOrFail($id);
        $employer->nom = $request->nom;
        $employer->prenom = $request->prenom;
        $employer->salaire = $request->salaire;
        $employer->save();

        return response()->json(['message' => 'Employer updated successfully']);
    }

    public function destroy($id)
    {
        $employer = Employer::findOrFail($id);
        $hasPayments = EmployerPaie::where('idemployer', $id)->exists();

        if ($hasPayments) {
            return response()->json(['error' => 'Cannot delete employer with payments'], 400);
        }

        $employer->delete();

        return response()->json(['message' => 'Employer deleted successfully']);
    }

    public function makePayment(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $employer = Employer::findOrFail($id);
        $amount = $request->input('amount');
        $currentMonth = Carbon::now();
        $previousMonth = Carbon::now()->subMonth();
        $caisse = Caisse::findOrFail(1);

        // Check if the caisse has enough balance
        if ($caisse->solde < $amount) {
            return response()->json(['error' => 'Insufficient balance in the caisse'], 400);
        }

        // Calculate total payments for the current and previous month using the salary from employerpaie
        $currentMonthPayments = EmployerPaie::join('transaction', 'employerpaie.idtransaction', '=', 'transaction.id')
            ->where('employerpaie.idemployer', $id)
            ->whereYear('transaction.datetransaction', $currentMonth->year)
            ->whereMonth('employerpaie.mois_cumul', $currentMonth->month)
            ->sum('transaction.montant');

        $previousMonthPayments = EmployerPaie::join('transaction', 'employerpaie.idtransaction', '=', 'transaction.id')
            ->where('employerpaie.idemployer', $id)
            ->whereYear('transaction.datetransaction', $previousMonth->year)
            ->whereMonth('employerpaie.mois_cumul', $previousMonth->month)
            ->sum('transaction.montant');

        // Get the salary for the current and previous month from employerpaie or use the employer's salary
        $currentMonthSalary = EmployerPaie::where('idemployer', $id)
            ->whereYear('mois_cumul', $currentMonth->year)
            ->whereMonth('mois_cumul', $currentMonth->month)
            ->value('salaire') ?? $employer->salaire;

        $previousMonthSalary = EmployerPaie::where('idemployer', $id)
            ->whereYear('mois_cumul', $previousMonth->year)
            ->whereMonth('mois_cumul', $previousMonth->month)
            ->value('salaire') ?? $employer->salaire;

        $remainingPreviousMonth = max(0, $previousMonthSalary - $previousMonthPayments);
        $remainingCurrentMonth = max(0, $currentMonthSalary - $currentMonthPayments);

        // Log the calculated values
        Log::info("Employer ID: $id");
        Log::info("Amount: $amount");
        Log::info("Previous Month Payments: $previousMonthPayments");
        Log::info("Current Month Payments: $currentMonthPayments");
        Log::info("Remaining Previous Month: $remainingPreviousMonth");
        Log::info("Remaining Current Month: $remainingCurrentMonth");

        // Check if the amount exceeds the allowed payment
        if ($amount > $remainingPreviousMonth + $remainingCurrentMonth) {
            Log::error('The amount exceeds the allowed payment');
            return response()->json(['error' => 'The amount exceeds the allowed payment'], 400);
        }

        // Process the payment within a transaction
        DB::beginTransaction();

        try {
            if ($amount <= $remainingPreviousMonth) {
                // If the amount is less than or equal to the remaining amount of the previous month
                Log::info("Processing payment for the previous month: $amount");
                $this->createPayment($id, $amount, $previousMonth->startOfMonth(), $employer->nom, $employer->prenom, $caisse, $previousMonthSalary);
            } else {
                // Pay the remaining amount of the previous month
                if ($remainingPreviousMonth > 0) {
                    Log::info("Processing remaining payment for the previous month: $remainingPreviousMonth");
                    $this->createPayment($id, $remainingPreviousMonth, $previousMonth->startOfMonth(), $employer->nom, $employer->prenom, $caisse, $previousMonthSalary);
                    $amount -= $remainingPreviousMonth;
                }
                // Pay the remaining amount from the current month
                if ($amount > 0) {
                    Log::info("Processing payment for the current month: $amount");
                    $this->createPayment($id, $amount, $currentMonth->startOfMonth(), $employer->nom, $employer->prenom, $caisse, $currentMonthSalary);
                }
            }

            DB::commit();

            return response()->json(['message' => 'Payment processed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('An error occurred while processing the payment: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while processing the payment: ' . $e->getMessage()], 500);
        }
    }

    private function createPayment($employerId, $amount, $accrualMonth, $employerNom, $employerPrenom, $caisse ,$employerSalaire)
    {
        DB::transaction(function () use ($employerId, $amount, $accrualMonth, $employerNom, $employerPrenom, $caisse, $employerSalaire) {
            // Update caisse balance
            $caisse->solde -= $amount;
            $caisse->save();

            // Create transaction
            $transaction = new Transaction();
            $transaction->idcaisse = $caisse->id;
            $transaction->montant = $amount;
            $transaction->datetransaction = now();
            $transaction->typetransaction = 1; 
            $transaction->description = 'Karama ' . $employerNom . ' ' . $employerPrenom;
            $transaction->save();

            // Create employerpaie
            $employerPaie = new EmployerPaie();
            $employerPaie->idemployer = $employerId;
            $employerPaie->idtransaction = $transaction->id;
            $employerPaie->salaire = $employerSalaire;
            $employerPaie->mois_cumul = $accrualMonth;
            $employerPaie->save();
        });
    }

    public function getPaymentsToday()
    {
        $today = Carbon::today();

        $payments = EmployerPaie::join('transaction', 'employerpaie.idtransaction', '=', 'transaction.id')
            ->join('employer', 'employerpaie.idemployer', '=', 'employer.id')
            ->whereDate('transaction.datetransaction', $today)
            ->get(['transaction.id as transaction_id', 'transaction.montant', 'transaction.datetransaction', 'employer.nom', 'employer.prenom', 'employerpaie.mois_cumul']);


        return response()->json(['payments' => $payments], 200);
    }

    public function getPayments($id)
    {
        $currentMonth = Carbon::now()->startOfMonth();

        $totalPayments = EmployerPaie::join('transaction', 'employerpaie.idtransaction', '=', 'transaction.id')
            ->where('employerpaie.idemployer', $id)
            ->where('employerpaie.mois_cumul', $currentMonth)
            ->sum('transaction.montant');

        return response()->json(['totalPayments' => $totalPayments], 200);
    }
   

    public function updatePayment(Request $request, $transactionId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();

        try {
            $transaction = Transaction::findOrFail($transactionId);
            $employerPaie = EmployerPaie::where('idtransaction', $transactionId)->firstOrFail();
            $caisse = Caisse::findOrFail($transaction->idcaisse);

            $newAmount = $request->input('amount');
            $oldAmount = $transaction->montant;

            // Verify total payments for the given mois_cumul
            $totalPayments = EmployerPaie::join('transaction', 'employerpaie.idtransaction', '=', 'transaction.id')
                ->where('employerpaie.idemployer', $employerPaie->idemployer)
                ->where('employerpaie.mois_cumul', $employerPaie->mois_cumul)
                ->sum('transaction.montant');

            if ($totalPayments - $oldAmount + $newAmount > $employerPaie->employer->salaire) {
                return response()->json(['error' => 'The new amount exceeds the allowed payment for the given month'], 400);
            }

            // Check if the caisse has enough balance for the new amount
            if ($caisse->solde + $oldAmount < $newAmount) {
                return response()->json(['error' => 'Insufficient balance in the caisse for the new amount'], 400);
            }

            // Check if there are more recent payments that need to be deleted first
            $recentPayments = EmployerPaie::join('transaction', 'employerpaie.idtransaction', '=', 'transaction.id')
                ->where('employerpaie.idemployer', $employerPaie->idemployer)
                ->where('transaction.datetransaction', '>', $transaction->datetransaction)
                ->exists();

            if ($recentPayments) {
                return response()->json(['error' => 'Cannot update payment, there are more recent payments'], 400);
            }

            // Update caisse balance
            $caisse->solde += $oldAmount; // Add the old amount back to the caisse
            $caisse->solde -= $newAmount; // Subtract the new amount from the caisse
            $caisse->save();

            // Update transaction amount
            $transaction->montant = $newAmount;
            $transaction->save();

            DB::commit();

            return response()->json(['message' => 'Payment updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'An error occurred while updating the payment: ' . $e->getMessage()], 500);
        }
    }

    public function deletePayment($transactionId)
    {
        DB::beginTransaction();

        try {
            $transaction = Transaction::findOrFail($transactionId);
            $employerPaie = EmployerPaie::where('idtransaction', $transactionId)->firstOrFail();
            $caisse = Caisse::findOrFail($transaction->idcaisse);

            // Check if there are more recent payments that would be affected
            $recentPayments = EmployerPaie::join('transaction', 'employerpaie.idtransaction', '=', 'transaction.id')
                ->where('employerpaie.idemployer', $employerPaie->idemployer)
                ->where('transaction.datetransaction', '>', $transaction->datetransaction)
                ->exists();

            if ($recentPayments) {
                return response()->json(['error' => 'Cannot delete payment, there are more recent payments'], 400);
            }

            // Update caisse balance
            $caisse->solde += $transaction->montant;
            $caisse->save();

            // Delete employerpaie record
            $employerPaie->delete();

            // Delete transaction record
            $transaction->delete();

            DB::commit();

            return response()->json(['message' => 'Payment deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'An error occurred while deleting the payment: ' . $e->getMessage()], 500);
        }
    }

    public function getPaymentStatus($year)
    {
        $paymentStatus = DB::table('employer as e')
            ->join('employerpaie as ep', 'e.id', '=', 'ep.idemployer')
            ->join('transaction as t', 'ep.idtransaction', '=', 't.id')
            ->select(
                'e.id',
                'e.nom',
                'e.prenom',
                'ep.salaire',
                DB::raw('SUM(t.montant) as total'),
                DB::raw('ep.salaire - SUM(t.montant) as reste'),
                DB::raw('EXTRACT(YEAR FROM ep.mois_cumul) as annee'),
                DB::raw('EXTRACT(MONTH FROM ep.mois_cumul) as mois')
            )
            ->where(DB::raw('EXTRACT(YEAR FROM ep.mois_cumul)'), $year)
            ->groupBy('e.id', 'e.nom', 'e.prenom', 'ep.salaire', DB::raw('EXTRACT(YEAR FROM ep.mois_cumul)'), DB::raw('EXTRACT(MONTH FROM ep.mois_cumul)'))
            ->orderBy(DB::raw('EXTRACT(MONTH FROM ep.mois_cumul)'))
            ->get();

        return response()->json($paymentStatus);
    }
}
