<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Caisse;
use App\Models\Billet;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TransactionController extends Controller
{
    public function ajoutVoady(Request $request)
    {
        try {
            // Démarrez une transaction
            DB::beginTransaction();
    
            $montant = $request->input('montant');
            $idcaisse = $request->input('caisse');
            $description = $request->input('description');
    
            $transaction = new Transaction([
                'idcaisse' => $idcaisse,
                'montant' => $montant,
                'datetransaction' => now(),
                'typetransaction' => 2, // Voady
                'description' => $description,
            ]);
    
            $transaction->save();
    
            // Mettez à jour le solde de la caisse si idcaisse n'est pas nul
            if (!is_null($idcaisse)) {
                $caisse = Caisse::find($idcaisse);
                $caisse->solde += $montant;
                $caisse->save();
            }
    
            // Validez la transaction
            DB::commit();
    
            return response()->json(['message' => 'Voady ajouté avec succès']);
        } catch (\Exception $e) {
            // En cas d'erreur, annulez la transaction
            DB::rollBack();
    
            // Retournez l'erreur
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getVoadyList()
    {
        // Récupérer les voady (transactions avec typetransaction=2) avec les informations nécessaires
        $voadyList = Transaction::where('typetransaction', 2)
            ->with(['caisse' => function ($query) {
                $query->select('id', 'nom'); // Sélectionnez les colonnes nécessaires de la table 'caisse'
            }])
            ->select('id', 'idcaisse', 'montant', 'datetransaction', 'description')
            ->whereDate('datetransaction', Carbon::today())
            ->get();

        return response()->json($voadyList);
    }

    public function getVoadyDetails($voadyId)
    {
        try {
            // Récupérer les détails du voady (transaction avec typetransaction=2)
            $voadyDetails = Transaction::where('id', $voadyId)
                ->where('typetransaction', 2)
                ->with(['caisse' => function ($query) {
                    $query->select('id', 'nom');
                }])
                ->select('id', 'idcaisse', 'montant', 'datetransaction', 'description')
                ->first();

            if (!$voadyDetails) {
                return response()->json(['error' => 'Voady non trouvé.'], 404);
            }

            return response()->json($voadyDetails);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Une erreur s\'est produite.'], 500);
        }
    }

    public function deleteVoady($voadyId)
    {
        try {
            // Vérifiez si le voady existe
            $voady = Transaction::find($voadyId);
            if (!$voady || $voady->typetransaction !== 2) {
                return response()->json(['error' => 'Voady non trouvé.'], 404);
            }

            // Supprimez le voady
            $voady->delete();

            return response()->json(['message' => 'Voady supprimé avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Une erreur s\'est produite.'], 500);
        }
    }

    public function update(Request $request, $voadyId)
    {
        try {
            // Mise à jour de la transaction avec typetransaction=1
            $transaction = Transaction::findOrFail($voadyId);

            $transaction->update([
                'montant' => $request->input('montant'),
                'description' => $request->input('description'),
                'idcaisse' => $request->input('idcaisse'),
            ]);

            return response()->json($transaction, 200);
        } catch (\Exception $e) {
            // Gérez les erreurs comme bon vous semble
            return response()->json(['message' => 'Erreur lors de la modification du Voady.'], 500);
        }
    }

    public function ajoutDepense(Request $request)
    {
        try {
            // Démarrez une transaction
            DB::beginTransaction();
    
            $montant = $request->input('montant');
            $idcaisse = $request->input('caisse');
            $description = $request->input('description');
    
            $transaction = new Transaction([
                'idcaisse' => $idcaisse,
                'montant' => $montant,
                'datetransaction' => now(),
                'typetransaction' => 1, // Voady
                'description' => $description,
            ]);
    
            $transaction->save();
    
            // Mettez à jour le solde de la caisse si idcaisse n'est pas nul
            if (!is_null($idcaisse)) {
                $caisse = Caisse::find($idcaisse);
                $caisse->solde 
                -= $montant;
                $caisse->save();
            }
    
            // Validez la transaction
            DB::commit();
    
            return response()->json(['message' => 'Dépense ajouté avec succès']);
        } catch (\Exception $e) {
            // En cas d'erreur, annulez la transaction
            DB::rollBack();
    
            // Retournez l'erreur
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDepenseList()
    {
        // Récupérer les voady (transactions avec typetransaction=2) avec les informations nécessaires
        $voadyList = Transaction::where('typetransaction', 1)
            ->with(['caisse' => function ($query) {
                $query->select('id', 'nom'); // Sélectionnez les colonnes nécessaires de la table 'caisse'
            }])
            ->select('id', 'idcaisse', 'montant', 'datetransaction', 'description')
            ->whereDate('datetransaction', Carbon::today())
            ->get();

        return response()->json($voadyList);
    }

    public function getDepenseDetails($depenseId)
    {
        try {
            // Récupérer les détails du depense (transaction avec typetransaction=1)
            $DepenseDetails = Transaction::where('id', $depenseId)
                ->where('typetransaction', 1)
                ->with(['caisse' => function ($query) {
                    $query->select('id', 'nom'); // Sélectionnez les colonnes nécessaires de la table 'caisse'
                }])
                ->select('id', 'idcaisse', 'montant', 'datetransaction', 'description')
                ->first();

            if (!$DepenseDetails) {
                return response()->json(['error' => 'Depense non trouvé.'], 404);
            }

            return response()->json($DepenseDetails);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Une erreur s\'est produite.'], 500);
        }
    }

    public function updateDep(Request $request, $depenseId)
    {
        // Début de la transaction
        DB::beginTransaction();
        
        try {
            
            // Récupérer la transaction avant la modification
            $transactionAvantModification = Transaction::findOrFail($depenseId);
            $montantInitial = $transactionAvantModification->montant;
            $idCaisseAvantModification = $transactionAvantModification->idcaisse;
            

            
            Caisse::where('id', $idCaisseAvantModification)->update(['solde' => DB::raw("solde + $montantInitial")]);

            
            $transactionAvantModification->update([
                'montant' => $request->input('montant'),
                'description' => $request->input('description'),
                'idcaisse' => $request->input('idcaisse'),
               
            ]);

            // Mettez à jour la caisse (retrait du nouveau montant modifié)
            $idCaisseNouvelle = $request->input('idcaisse');
            Caisse::where('id', $idCaisseNouvelle)->update(['solde' => DB::raw("solde - {$request->input('montant')}")]);

            // Commit de la transaction
            DB::commit();

            return response()->json($transactionAvantModification, 200);
        } catch (\Exception $e) {
            // En cas d'erreur, rollback de la transaction
            DB::rollBack();

            
            return response()->json(['message' => 'Erreur lors de la modification du depense.'], 500);
        }
    }

    public function deleteDepense($depenseId)
    {
        try {
           
            $voady = Transaction::find($depenseId);
            if (!$voady) {
                return response()->json(['error' => 'Voady non trouvé.'], 404);
            }

            
            $voady->delete();

            return response()->json(['message' => 'Voady supprimé avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Une erreur s\'est produite.'], 500);
        }
    }

    public function getMonthlyBalanceByCaisse()
    {
        $results = DB::table('transaction as t')
            ->join('caisse as c', 't.idcaisse', '=', 'c.id')
            ->whereNotNull('t.datetransaction')
            ->select(
                DB::raw('EXTRACT(YEAR FROM t.datetransaction) as annee'),
                DB::raw('EXTRACT(MONTH FROM t.datetransaction) as mois'),
                DB::raw('COALESCE(SUM(CASE WHEN t.typetransaction IN (0, 2) THEN t.montant ELSE 0 END), 0) as argent_recu'),
                DB::raw('COALESCE(SUM(CASE WHEN t.typetransaction = 1 THEN t.montant ELSE 0 END), 0) as argent_depense'),
                DB::raw('(COALESCE(SUM(CASE WHEN t.typetransaction IN (0, 2) THEN t.montant ELSE 0 END), 0) 
                         - COALESCE(SUM(CASE WHEN t.typetransaction = 1 THEN t.montant ELSE 0 END), 0)) as solde_restant'),
                'c.id'
            )
            ->groupBy('annee', 'mois', 'c.id')
            ->orderBy('annee')
            ->orderBy('mois')
            ->get();

        return response()->json($results);
    }

    //RAKITRA

        //
    public function store(Request $request)
    {
        $this->authorize('create', Transaction::class); // Vérification de l'autorisation

        $user = auth()->user();

        DB::beginTransaction();

        try {
            $rakitra = Transaction::create([
                'datetransaction' => now(),
                'montant' => $request->input('totalMontant'),
                'description' => $request->input('description'),
                'typetransaction' => $request->input('type'),
                'idcaisse' => $request->input('caisse'),
            ]);

            

            foreach ($request->input('billets') as $key => $value) {
                // Extraire la valeur numérique à partir de la clé du billet (par exemple, 'billet100' devient 100)
                $billetValue = intval(str_replace('billet', '', $key));
            
                  Billet::create([
                    'idrakitra' => $rakitra->id,
                    'billet' => $billetValue,
                    'nombre' => $value,
                ]);
            }

            try {
            $caisse = Caisse::findOrFail($rakitra->caisse->id);
            } catch (ModelNotFoundException $e) {
                DB::rollBack();
                return response()->json(['error' => 'Caisse non trouvée'.$rakitra->caisse->id], 404);
            }
            $caisse->solde += $rakitra->montant;
            $caisse->save();


            DB::commit();

            return response()->json(['message' => 'Rakitra et billets insérés avec succès']);
        } catch (\Exception $e) {
            DB::rollBack();

            
            if ($e instanceof \Illuminate\Database\QueryException) {
                // Erreur de requête SQL
                return response()->json(['error' => $e], 500);
            } elseif ($e instanceof \PDOException) {
                // Erreur de la base de données
                return response()->json(['error' => 'Erreur de la base de données lors de l\'insertion'], 500);
            } else {
                // Autre type d'erreur
                error_log(print_r($request->all(), true)); // Pour voir les données de la requête
                error_log($e->getMessage()); // Pour voir le message d'erreur de l'exception

                return response()->json(['error' => $request->all()], 500);
                
            }
        }
    }

    public function listrakitra()
    {
        $rakitraList = Transaction::whereDate('datetransaction', now()->toDateString())
        ->where(function ($query) {
            $query->where('typetransaction', 0)
                ->orWhere('typetransaction', 3);
        })
        ->get();
        return response()->json($rakitraList);
    }

    public function getInitialBillets($rakitraId)
    {
          $initialBillets = Billet::where('idrakitra', $rakitraId)->pluck('nombre', 'billet');

        return response()->json($initialBillets);
    }

    public function modifyBillets(Request $request, $rakitraId)
    {


        
    DB::beginTransaction();

    try {
        
        $montantTotal = $request->input('montantTotal');
        $description = $request->input('description');

        
        $rakitraPrecedent = Transaction::find($rakitraId);
        $montantRakitraPrecedent = $rakitraPrecedent->montant;
        $caisseprecedent = $rakitraPrecedent->caisse;

       
        $caisse = Caisse::find($caisseprecedent->id);
        $caisse->solde -= $montantRakitraPrecedent;
        $caisse->save();

        
        Transaction::where('id', $rakitraId)->update([
            'montant' => $montantTotal,
            'description' => $description,
            'idcaisse' => $request->input('caisse'),
            'typetransaction' => $request->input('typetransaction'),
        ]);

    
        $modifiedBillets = $request->input('modifiedBillets');

        foreach ($modifiedBillets as $billet => $nombre) {
            Billet::where('idrakitra', $rakitraId)->where('billet', $billet)->update(['nombre' => $nombre]);
        }

        
        $caissenouveau=Caisse::find($request->input('caisse'));
        $caissenouveau->solde += $montantTotal;
        $caissenouveau->save();

        
        DB::commit();

        return response()->json(['message' => 'Billets modifiés avec succès']);
    } catch (\Exception $e) {
        
        DB::rollBack();

        return response()->json(['error' => ''.$e], 500);
    }
    }

    public function getRakitraWithBillets()
    {
        
        $rakitraWithBillets = Transaction::with('billets')->get();

        return response()->json($rakitraWithBillets);
    }

    public function deleteRakitra($rakitraId)
    {
        try {
            
            DB::beginTransaction();

            
            Billet::where('idrakitra', $rakitraId)->delete();

            
            Transaction::where('id', $rakitraId)->delete();

            
            DB::commit();

            return response()->json(['message' => 'Rakitra supprimé avec succès']);
        } catch (\Exception $e) {
            
            DB::rollBack();

            return response()->json(['error' => 'Erreur lors de la suppression du Rakitra'], 500);
        }
    }

    public function search(Request $request)
    {
        $query = Transaction::query();

        if ($request->has('typetransaction') && !empty($request->typetransaction)) {
            $query->whereIn('typetransaction', $request->typetransaction);
        }

        if ($request->has('idcaisse') && !empty($request->idcaisse)) {
            $query->where('idcaisse', $request->idcaisse);
        }

        if ($request->has('date_debut') && !empty($request->date_debut)) {
            $query->where('datetransaction', '>=', $request->date_debut);
        }

        if ($request->has('date_fin') && !empty($request->date_fin)) {
            $query->where('datetransaction', '<=', $request->date_fin);
        }

        $transactions = $query->with('caisse')->paginate(5);

        return response()->json($transactions);
    }

    public function modifyTransaction(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        
        $request->validate([
            'montant' => 'required|integer',
            'description' => 'nullable|string',
            'idcaisse' => 'required|exists:caisses,id'
        ]);

        DB::transaction(function () use ($transaction, $request) {
            
            $oldMontant = $transaction->montant;
            $oldCaisseId = $transaction->idcaisse;

            
            $transaction->montant = $request->montant;
            $transaction->description = $request->description;
            $transaction->idcaisse = $request->idcaisse;
            $transaction->save();

            
            $this->updateCaisseSolde($oldCaisseId);

            
            $this->updateCaisseSolde($request->idcaisse);
        });

        return response()->json($transaction);
    }

    
    public function destroyTransaction($id)
    {
        $transaction = Transaction::findOrFail($id);

        DB::transaction(function () use ($transaction) {
            // Sauvegarder l'ID de la caisse pour la mise à jour du solde
            $caisseId = $transaction->idcaisse;

            // Supprimer la transaction
            $transaction->delete();

            // Mettre à jour le solde de la caisse
            $this->updateCaisseSolde($caisseId);
        });

        return response()->json(null, 204);
    }

    // Fonction pour mettre à jour le solde de la caisse
    protected function updateCaisseSolde($caisseId)
    {
        $caisse = Caisse::findOrFail($caisseId);

        $entrees = Transaction::where('idcaisse', $caisseId)
            ->where('typetransaction', '<>', 1)
            ->sum('montant');

        $depenses = Transaction::where('idcaisse', $caisseId)
            ->where('typetransaction', 1)
            ->sum('montant');

        $caisse->solde = $entrees - $depenses;
        $caisse->save();
    }

    public function storeTr(Request $request)
    {
        $request->validate([
            'montant' => 'required|integer',
            'idcaisse' => 'required|exists:caisses,id',
            'description' => 'nullable|string',
            'typetransaction' => 'required|integer|in:0,1,2,3'
        ]);

        DB::transaction(function () use ($request) {
            $transaction = Transaction::create([
                'montant' => $request->montant,
                'idcaisse' => $request->idcaisse,
                'description' => $request->description,
                'typetransaction' => $request->typetransaction,
                'datetransaction' => now()
            ]);

            $this->updateCaisseSolde($transaction->idcaisse);
        });

        return response()->json(['message' => 'Transaction ajoutée avec succès.'], 201);
    }

    
}
