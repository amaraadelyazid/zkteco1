<?php

namespace App\Repositories;

use App\Models\Employe;

class EmployeRepository
{

    /** @var Employe[] */
    private array $employes;


    public function allPaginated($perPage = 20)
    {
        return Employe::with('departement', 'shift')->paginate($perPage);
    }

    public function find($id)
    {
        return Employe::with('departement', 'shift')->findOrFail($id);
    }

    public function create(array $data)
    {
        return Employe::create($data);
    }

    public function update($id, array $data)
    {
        $employe = $this->find($id);
        $employe->update($data);
        return $employe;
    }

    public function delete($id)
    {
        $employe = $this->find($id);
        return $employe->delete();
    }
}


