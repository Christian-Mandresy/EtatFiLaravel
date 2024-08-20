<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecapController extends Controller
{
    public function recapWeekly(Request $request, $year)
    {
        $idcaisse = $request->input('idcaisse');
        $perPage = 5;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;

        $total = DB::table('transaction')
        ->select(DB::raw('COUNT(DISTINCT date_trunc(\'week\', datetransaction)) as total'))
        ->whereYear('datetransaction', $year)
        ->where('idcaisse', $idcaisse)
        ->first()
        ->total;

        $results = DB::select("
            WITH weekly_transactions AS (
                SELECT 
                    t.idcaisse,
                    date_trunc('week', t.datetransaction) AS week,
                    SUM(CASE WHEN t.typetransaction = 1 THEN t.montant ELSE 0 END) AS depense,
                    SUM(CASE WHEN t.typetransaction <> 1 THEN t.montant ELSE 0 END) AS entree
                FROM \"transaction\" t
                WHERE EXTRACT(YEAR FROM t.datetransaction) = ? AND t.idcaisse = ?
                GROUP BY t.idcaisse, date_trunc('week', t.datetransaction)
            ),
            last_adjustment_per_week AS (
                SELECT 
                    wt.idcaisse,
                    wt.week,
                    (SELECT a.nouveau_solde
                     FROM ajustements a
                     WHERE a.idcaisse = wt.idcaisse AND a.date_ajustement <= wt.week
                     ORDER BY a.date_ajustement DESC
                     LIMIT 1) AS solde_initial
                FROM weekly_transactions wt
            ),
            weekly_cumulative AS (
                SELECT 
                    wt.idcaisse,
                    wt.week,
                    wt.entree,
                    wt.depense,
                    SUM(wt.entree) OVER (PARTITION BY wt.idcaisse ORDER BY wt.week) AS cumulative_entree,
                    SUM(wt.depense) OVER (PARTITION BY wt.idcaisse ORDER BY wt.week) AS cumulative_depense
                FROM weekly_transactions wt
            )
            SELECT 
                wc.idcaisse,
                c.nom AS nom_caisse,
                wc.week,
                wc.entree AS entree,
                wc.depense AS depense,
                laj.solde_initial + (wc.cumulative_entree - wc.cumulative_depense) AS solde
            FROM weekly_cumulative wc
            JOIN last_adjustment_per_week laj ON wc.idcaisse = laj.idcaisse AND wc.week = laj.week
            JOIN caisse c ON wc.idcaisse = c.id
            ORDER BY wc.week desc
            LIMIT ? OFFSET ?;
        ", [$year, $idcaisse, $perPage, $offset]);

        return response()->json([
            'data' => $results,
            'total' => $total,
            'perPage' => $perPage,
            'currentPage' => $page
        ]);
    }

    public function recapMonthly(Request $request, $year)
    {
        $idcaisse = $request->input('idcaisse');
        $perPage = 5;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;

        $total = DB::table('transaction')
        ->select(DB::raw('COUNT(DISTINCT date_trunc(\'month\', datetransaction)) as total'))
        ->whereYear('datetransaction', $year)
        ->where('idcaisse', $idcaisse)
        ->first()
        ->total;

        $results = DB::select("
            WITH monthly_transactions AS (
                SELECT 
                    t.idcaisse,
                    date_trunc('month', t.datetransaction) AS month,
                    SUM(CASE WHEN t.typetransaction = 1 THEN t.montant ELSE 0 END) AS depense,
                    SUM(CASE WHEN t.typetransaction <> 1 THEN t.montant ELSE 0 END) AS entree
                FROM \"transaction\" t
                WHERE EXTRACT(YEAR FROM t.datetransaction) = ? AND t.idcaisse = ?
                GROUP BY t.idcaisse, date_trunc('month', t.datetransaction)
            ),
            last_adjustment_per_month AS (
                SELECT 
                    mt.idcaisse,
                    mt.month,
                    (SELECT a.nouveau_solde
                     FROM ajustements a
                     WHERE a.idcaisse = mt.idcaisse AND a.date_ajustement <= mt.month
                     ORDER BY a.date_ajustement DESC
                     LIMIT 1) AS solde_initial
                FROM monthly_transactions mt
            ),
            monthly_cumulative AS (
                SELECT 
                    mt.idcaisse,
                    mt.month,
                    mt.entree,
                    mt.depense,
                    SUM(mt.entree) OVER (PARTITION BY mt.idcaisse ORDER BY mt.month) AS cumulative_entree,
                    SUM(mt.depense) OVER (PARTITION BY mt.idcaisse ORDER BY mt.month) AS cumulative_depense
                FROM monthly_transactions mt
            )
            SELECT 
                mc.idcaisse,
                c.nom AS nom_caisse,
                mc.month,
                mc.entree AS entree,
                mc.depense AS depense,
                laj.solde_initial + (mc.cumulative_entree - mc.cumulative_depense) AS solde
            FROM monthly_cumulative mc
            JOIN last_adjustment_per_month laj ON mc.idcaisse = laj.idcaisse AND mc.month = laj.month
            JOIN caisse c ON mc.idcaisse = c.id
            ORDER BY mc.month desc
            LIMIT ? OFFSET ?;
        ", [$year, $idcaisse, $perPage, $offset]);

        return response()->json([
            'data' => $results,
            'total' => $total,
            'perPage' => $perPage,
            'currentPage' => $page
        ]);
    }
}
