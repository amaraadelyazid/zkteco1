@csrf
<label>Nom: <input type="text" name="nom" value="{{ old('nom', $employe->nom ?? '') }}"></label><br>
<label>Prénom: <input type="text" name="prenom" value="{{ old('prenom', $employe->prenom ?? '') }}"></label><br>
<label>Email: <input type="email" name="email" value="{{ old('email', $employe->email ?? '') }}"></label><br>
<label>Biometric ID: <input type="number" name="biometric_id" value="{{ old('biometric_id', $employe->biometric_id ?? '') }}"></label><br>
<label>Salaire: <input type="text" name="salaire" value="{{ old('salaire', $employe->salaire ?? '') }}"></label><br>
<label>Poste: <input type="text" name="poste" value="{{ old('poste', $employe->poste ?? '') }}"></label><br>
<label>Département ID: <input type="number" name="departement_id" value="{{ old('departement_id', $employe->departement_id ?? '') }}"></label><br>
<label>Shift ID: <input type="number" name="shift_id" value="{{ old('shift_id', $employe->shift_id ?? '') }}"></label><br>
<label>Mot de passe: <input type="password" name="password"></label><br>
<button type="submit">Enregistrer</button>
