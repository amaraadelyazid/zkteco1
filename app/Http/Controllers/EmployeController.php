<?php

namespace App\Http\Controllers;

use App\Repositories\EmployeRepository;
use Illuminate\Http\Request;

class EmployeController extends Controller
{
    protected $repo;

    public function __construct(EmployeRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $employes = $this->repo->allPaginated();
        return view('employes.index', compact('employes'));
    }

    public function create()
    {
        return view('employes.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'email' => 'nullable|email|unique:employes',
            'biometric_id' => 'required|integer|unique:employes',
            'salaire' => 'required|numeric',
            'poste' => 'required|string',
            'departement_id' => 'nullable|exists:departements,id',
            'shift_id' => 'required|exists:shifts,id',
            'password' => 'nullable|string|min:6',
        ]);

        $this->repo->create($data);
        return redirect()->route('employes.index')->with('success', 'Employé ajouté.');
    }

    public function show($id)
    {
        $employe = $this->repo->find($id);
        return view('employes.show', compact('employe'));
    }

    public function edit($id)
    {
        $employe = $this->repo->find($id);
        return view('employes.edit', compact('employe'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'email' => 'nullable|email|unique:employes,email,' . $id,
            'biometric_id' => 'required|integer|unique:employes,biometric_id,' . $id,
            'salaire' => 'required|numeric',
            'poste' => 'required|string',
            'departement_id' => 'nullable|exists:departements,id',
            'shift_id' => 'required|exists:shifts,id',
            'password' => 'nullable|string|min:6',
        ]);

        $this->repo->update($id, $data);
        return redirect()->route('employes.index')->with('success', 'Employé mis à jour.');
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return redirect()->route('employes.index')->with('success', 'Employé supprimé.');
    }
}
