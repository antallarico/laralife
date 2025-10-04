	<!DOCTYPE html>
<html>
<head>
    <title>Nuova Lezione – Archivio Allenamento</title>
</head>
<body>
    <h1>➕ Nuova Lezione</h1>

    <form method="POST" action="{{ route('lezioni.store') }}">
        @csrf

        <p>
            <label for="titolo">Titolo:</label><br>
            <input type="text" name="titolo" id="titolo" required>
        </p>

        <p>
            <label for="descrizione">Descrizione:</label><br>
            <textarea name="descrizione" id="descrizione"></textarea>
        </p>

        <p>
            <label for="link">Link (YouTube, file locale, ecc.):</label><br>
            <input type="url" name="link" id="link">
        </p>

        <p>
            <label for="durata">Durata (minuti):</label><br>
            <input type="number" name="durata" id="durata" min="1">
        </p>

        <p>
            <label for="tipo">Tipo (ballo, forza, stretching...):</label><br>
            <input type="text" name="tipo" id="tipo">
        </p>

        <p>
            <label for="piattaforma">Piattaforma (YouTube, StretchIt, ecc.):</label><br>
            <input type="text" name="piattaforma" id="piattaforma">
        </p>

        <p>
            <button type="submit">💾 Salva</button>
            <a href="{{ route('lezioni.index') }}">Annulla</a>
        </p>
    </form>
</body>
</html>
