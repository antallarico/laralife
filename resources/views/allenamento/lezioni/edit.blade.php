<!DOCTYPE html>
<html>
<head>
    <title>Modifica Lezione – Archivio Allenamento</title>
</head>
<body>
    <h1>✏️ Modifica Lezione</h1>
	<form method="POST" action="{{ route('lezioni.update', ['lezione' => $lezione->id]) }}">
        @csrf
        @method('PUT')

        <p>
            <label for="titolo">Titolo:</label><br>
            <input type="text" name="titolo" id="titolo" value="{{ $lezione->titolo }}" required>
        </p>

        <p>
            <label for="descrizione">Descrizione:</label><br>
            <textarea name="descrizione" id="descrizione">{{ $lezione->descrizione }}</textarea>
        </p>

        <p>
            <label for="link">Link:</label><br>
            <input type="url" name="link" id="link" value="{{ $lezione->link }}">
        </p>

        <p>
            <label for="durata">Durata (minuti):</label><br>
            <input type="number" name="durata" id="durata" value="{{ $lezione->durata }}">
        </p>

        <p>
            <label for="tipo">Tipo:</label><br>
            <input type="text" name="tipo" id="tipo" value="{{ $lezione->tipo }}">
        </p>

        <p>
            <label for="piattaforma">Piattaforma:</label><br>
            <input type="text" name="piattaforma" id="piattaforma" value="{{ $lezione->piattaforma }}">
        </p>

        <p>
            <button type="submit">💾 Aggiorna</button>
            <a href="{{ route('lezioni.index') }}">Annulla</a>
        </p>
    </form>
</body>
</html>
