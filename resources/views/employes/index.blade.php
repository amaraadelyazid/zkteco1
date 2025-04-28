@extends('employes.app')

@section('content')
    <h1>Liste des Employés</h1>

    <a href="{{ route('employes.create') }}" class="btn btn-primary mb-3">Créer un employé</a>

    @if($employes->count())
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Poste</th>
                    <th>Département</th>
                    <th>Shift</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employes as $emp)
                    <tr>
                        <td>{{ $emp->nom }}</td>
                        <td>{{ $emp->prenom }}</td>
                        <td>{{ $emp->poste }}</td>
                        <td>{{ $emp->departement->nom ?? '—' }}</td>
                        <td>{{ $emp->shift->nom ?? '—' }}</td>
                        <td>
                            <a href="{{ route('employes.edit', $emp->id) }}" class="btn btn-warning btn-sm">Modifier</a>
                            <form action="{{ route('employes.destroy', $emp->id) }}" method="POST" style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cet employé ?')">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $employes->onEachSide(1)->links() }}
        </div>
    @else
        <p>Aucun employé trouvé.</p>
    @endif
@endsection
