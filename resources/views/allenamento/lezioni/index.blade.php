<!DOCTYPE html>
<html>
<head>
    <title>Archivio Lezioni – Allenamento</title>
</head>
<body>
    <h1>📚 Archivio Lezioni</h1>
    <p>Qui potrai visualizzare, modificare o aggiungere le tue lezioni.</p>

    <a href="{{ route('lezioni.create') }}">➕ Nuova lezione</a>

    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>Titolo</th>
                <th>Tipo</th>
                <th>Piattaforma</th>
                <th>Durata</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lezioni as $lezione)
                <tr>
                    <td>{{ $lezione->titolo }}</td>
                    <td>{{ $lezione->tipo }}</td>
                    <td>{{ $lezione->piattaforma }}</td>
                    <td>{{ $lezione->durata }} min</td>
                    <td>
                        <a href="{{ route('lezioni.edit', $lezione->id) }}">✏️</a>
                        <form action="{{ route('lezioni.destroy', $lezione->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit">🗑️</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Nessuna lezione trovata.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p><a href="{{ url('/') }}">← Torna alla home</a></p>
</body>
</html>
