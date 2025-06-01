<x-filament::page>
    <div class="mb-6 flex gap-3">
        <button wire:click="switchTab('employes')" @class([
            'px-4 py-2 rounded transition-colors',
            'bg-primary-600 text-white' => $activeTab === 'employes',
            'bg-gray-200 hover:bg-gray-300' => $activeTab !== 'employes',
        ])>
            Employés
        </button>
        <button wire:click="switchTab('grhs')" @class([
            'px-4 py-2 rounded transition-colors',
            'bg-primary-600 text-white' => $activeTab === 'grhs',
            'bg-gray-200 hover:bg-gray-300' => $activeTab !== 'grhs',
        ])>
            GRH
        </button>
    </div>

    {{ $this->table }}

    @if(!empty($payslip_preview))
        @php
            $ficheDetails = $payslip_preview['fiche_details'] ?? [];
            $calculatePayslips = new \App\Actions\CalculatePayslips();
        @endphp

        <x-filament::section heading="Fiche de Paie - Résumé" class="mt-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- General Information -->
                <div class="bg-gray-50 p-4 rounded-lg shadow-sm">
                    <h3 class="text-lg font-semibold mb-3 text-gray-800">Informations Générales</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="font-medium">Référence:</dt>
                            <dd>{{ $ficheDetails['id'] ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="font-medium">Période:</dt>
                            <dd>{{ isset($payslip_preview['mois']) ? \Carbon\Carbon::parse($payslip_preview['mois'] . '-01')->locale('fr')->isoFormat('MMMM YYYY') : 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="font-medium">Employé:</dt>
                            <dd>{{ $payslip_preview['employe'] ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="font-medium">Poste:</dt>
                            <dd>{{ $payslip_preview['poste'] ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="font-medium">Département:</dt>
                            <dd>{{ $payslip_preview['departement'] ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="font-medium">Shift:</dt>
                            <dd>{{ $payslip_preview['shift'] ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="font-medium">Statut:</dt>
                            <dd>{{ \App\Filament\Resources\FicheDePaieResource\Pages\CalculateFicheDePaie::translateStatus($ficheDetails['status'] ?? 'en_attente') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="font-medium">Date de génération:</dt>
                            <dd>{{ $ficheDetails['date_generation'] ?? now()->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Work Summary -->
                <div class="bg-gray-50 p-4 rounded-lg shadow-sm">
                    <h3 class="text-lg font-semibold mb-3 text-gray-800">Résumé du Travail</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="font-medium">Heures Théoriques:</dt>
                            <dd>{{ $payslip_preview['heures_theoriques'] ?? '0,00' }} h</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="font-medium">Heures Travaillées:</dt>
                            <dd>{{ $payslip_preview['heures_travaillees'] ?? '0,00' }} h</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="font-medium">Heures Supplémentaires:</dt>
                            <dd class="text-blue-600 font-semibold">{{ $payslip_preview['heures_sup'] ?? '0,00' }} h</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="font-medium">Retards:</dt>
                            <dd>{{ $payslip_preview['retards'] ?? '0' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="font-medium">Absences:</dt>
                            <dd class="text-red-600">{{ $payslip_preview['absences'] ?? '0' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Financial Details -->
            <div class="bg-gray-50 p-4 rounded-lg shadow-sm mb-6">
                <h3 class="text-lg font-semibold mb-3 text-gray-800">Détails Financiers</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="flex justify-between">
                        <span class="font-medium">Salaire de Base:</span>
                        <span>{{ $payslip_preview['salaire_base'] }} €</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium">Montant Base:</span>
                        <span>{{ $payslip_preview['montant_base'] }} €</span>
                    </div>
                    @if(isset($ficheDetails['taux_horaire_sup']) && $ficheDetails['taux_horaire_sup'])
                        <div class="flex justify-between">
                            <span class="font-medium">Taux Heure Sup:</span>
                            <span>{{ $ficheDetails['taux_horaire_sup'] }}x</span>
                        </div>
                    @endif
                    @if(isset($ficheDetails['montant_heures_sup']) && $ficheDetails['montant_heures_sup'])
                        <div class="flex justify-between">
                            <span class="font-medium">Montant Heures Sup:</span>
                            <span>{{ $ficheDetails['montant_heures_sup'] }} €</span>
                        </div>
                    @endif
                    @if(isset($ficheDetails['prime']) && $ficheDetails['prime'])
                        <div class="flex justify-between">
                            <span class="font-medium">Primes:</span>
                            <span>{{ $ficheDetails['prime'] }} €</span>
                        </div>
                    @endif
                    @if(isset($ficheDetails['avance']) && $ficheDetails['avance'])
                        <div class="flex justify-between">
                            <span class="font-medium">Avances:</span>
                            <span>{{ $ficheDetails['avance'] }} €</span>
                        </div>
                    @endif
                    <div class="flex justify-between font-semibold text-gray-800">
                        <span>Montant Total:</span>
                        <span>{{ $ficheDetails['montant'] }} €</span>
                    </div>
                </div>
            </div>

            <!-- Presence Details -->
            @if(isset($payslip_preview['presences']) && count($payslip_preview['presences']))
                <div class="overflow-x-auto mb-6">
                    <h3 class="text-lg font-semibold mb-3 text-gray-800">Détail des Présences</h3>
                    <table class="min-w-full text-xs border bg-white">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2 border text-left">Date</th>
                                <th class="px-3 py-2 border text-left">Jour</th>
                                <th class="px-3 py-2 border text-left">Type</th>
                                <th class="px-3 py-2 border text-left">Heures</th>
                                <th class="px-3 py-2 border text-left">Arrivée</th>
                                <th class="px-3 py-2 border text-left">Statut Arrivée</th>
                                <th class="px-3 py-2 border text-left">Départ</th>
                                <th class="px-3 py-2 border text-left">Statut Départ</th>
                                <th class="px-3 py-2 border text-left">Anomalie</th>
                                <th class="px-3 py-2 border text-left">Résolue</th>
                                <th class="px-3 py-2 border text-left">Statut Global</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payslip_preview['presences'] as $presence)
                                @php
                                    $date = \Carbon\Carbon::parse($presence['date']);
                                    $isWeekend = $presence['is_weekend'] ?? false;
                                    $anomalieResolue = isset($presence['anomalie_resolue']) && $presence['anomalie_resolue'] ? 'Oui' : 'Non';
                                    $etatCheckIn = $calculatePayslips->translateCheckStatus($presence['etat_check_in'] ?? null);
                                    $etatCheckOut = $calculatePayslips->translateCheckStatus($presence['etat_check_out'] ?? null);
                                    $anomalieDisplay = $calculatePayslips->translateAnomalyType($presence['anomalie_type'] ?? null);
                                    $status = $calculatePayslips->determinePresenceStatus($presence);
                                @endphp
                                <tr class="{{ $isWeekend ? 'bg-gray-50' : (floatval(str_replace(',', '.', $presence['duree'])) == 0 ? 'text-red-600' : '') }}">
                                    <td class="px-3 py-2 border">{{ $date->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2 border">{{ $date->locale('fr')->isoFormat('dddd') }}</td>
                                    <td class="px-3 py-2 border">{{ $isWeekend ? 'Weekend' : 'Jour ouvré' }}</td>
                                    <td class="px-3 py-2 border">{{ $presence['duree'] }} h</td>
                                    <td class="px-3 py-2 border">{{ $presence['check_in'] ?? '—' }}</td>
                                    <td class="px-3 py-2 border {{ $presence['etat_check_in'] === 'retard' ? 'text-yellow-600' : '' }}">{{ $etatCheckIn }}</td>
                                    <td class="px-3 py-2 border">{{ $presence['check_out'] ?? '—' }}</td>
                                    <td class="px-3 py-2 border">{{ $etatCheckOut }}</td>
                                    <td class="px-3 py-2 border">{{ $anomalieDisplay }}</td>
                                    <td class="px-3 py-2 border">{{ $presence['anomalie_type'] ? $anomalieResolue : '—' }}</td>
                                    <td class="px-3 py-2 border">{{ $status }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Export Buttons -->
            <div class="flex justify-end gap-3">
                <button wire:click="exportCsv" type="button" 
                    class="bg-primary-600 hover:bg-primary-500 text-white px-4 py-2 rounded text-sm transition-colors">
                    Exporter en CSV
                </button>
                <button wire:click="exportPdf" type="button" 
                    class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded text-sm transition-colors">
                    Exporter en PDF
                </button>
            </div>
        </x-filament::section>
    @endif
</x-filament::page>
