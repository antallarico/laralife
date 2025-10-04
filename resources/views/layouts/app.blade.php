<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8" name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Laralife')</title>

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Tom Select --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">

    {{-- SortableJS --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js" defer></script>

    <style>
        body {
            padding: 0;
        }
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 230px;
            background-color: #f8f9fa;
            padding-top: 1rem;
            border-right: 1px solid #ddd;
        }
        .main-content {
            margin-left: 230px;
            padding: 1.5rem;
        }
        .planner-table {
            table-layout: fixed;
            width: 100%;
        }
        .planner-table th, .planner-table td {
            width: 14.28%;
            vertical-align: top;
        }
    </style>
	@stack('styles')
</head>
<body>
    <div class="sidebar">
        <div class="text-center fw-bold mb-3">☀️ Laralife</div>
        <div class="list-group list-group-flush">
            {{-- HOME --}}
            <a href="{{ url('') }}" class="list-group-item">🏠 Home</a>

            {{-- MODULI --}}
            <a href="{{ route('allenamento.dashboard') }}" class="list-group-item">🧘 Allenamento</a>
            <a href="{{ route('casa.dashboard') }}" class="list-group-item">🏠 Casa</a>
            <a href="{{ route('persona.dashboard') }}" class="list-group-item">🧑‍🦰 Cura della persona</a>
            <a href="{{ route('sociale.dashboard') }}" class="list-group-item">👥 Vita sociale</a>
            <a href="{{ route('mentale.dashboard') }}" class="list-group-item">📚 Attività mentali</a>

            {{-- ALLENAMENTO – Funzioni nuove --}}
            <hr class="my-2">

            <div class="accordion" id="plannerAccordion">
                {{-- Dropdown Allenamento --}}
                <div class="accordion-item border-0">
                    <h2 class="accordion-header" id="headingAllenamento">
                        <button class="accordion-button collapsed px-3 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAllenamento" aria-expanded="false" aria-controls="collapseAllenamento">
                            🏋️ Allenamento
                        </button>
                    </h2>
                    <div id="collapseAllenamento" class="accordion-collapse collapse" aria-labelledby="headingAllenamento" data-bs-parent="#plannerAccordion">
                        <div class="accordion-body p-2">
                            <ul class="nav flex-column small">
                                <li class="nav-item"><a href="{{ route('allenamento.dashboard') }}" class="nav-link">🏋️ Dashboard</a></li>
                                <li class="nav-item"><a href="{{ route('lezioni.index') }}" class="nav-link">📚🎥 Archivio Lezioni</a></li>
                                <li class="nav-item"><a href="{{ route('planner.allenamento.oggi') }}" class="nav-link">📅 Agenda Giornaliera</a></li>
                                <li class="nav-item"><a href="{{ route('planner.allenamento.storico') }}" class="nav-link">🕘📊 Storico Esecuzioni</a></li>
                                <li class="nav-item"><a href="{{ route('planner.allenamento.index') }}" class="nav-link">🗓️ Pianificazione Settimanale</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Dropdown Alimentazione --}}
                <div class="accordion-item border-0">
                    <h2 class="accordion-header" id="headingAlimentazione">
                        <button class="accordion-button collapsed px-3 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAlimentazione" aria-expanded="false" aria-controls="collapseAlimentazione">
                            🥗 Alimentazione
                        </button>
                    </h2>
                    <div id="collapseAlimentazione" class="accordion-collapse collapse" aria-labelledby="headingAlimentazione" data-bs-parent="#plannerAccordion">
                        <div class="accordion-body p-2">
                            <ul class="nav flex-column small">
                                <li class="nav-item"><a href="{{ route('planner.alimentazione.index') }}" class="nav-link">🗓️ Pianificazione Pasti</a></li>
								<li class="nav-item"><a href="{{ route('alimentazione.alimenti.index') }}" class="nav-link">🗓️ Archivio Alimenti</a></li>
								<li class="nav-item"><a href="{{ route('alimentazione.dispensa.index') }}" class="nav-link">🗓️ Dispensa</a></li>
                                <li class="nav-item"><a href="{{ route('alimentazione.oggi') }}" class="nav-link">📅 Oggi - Pasti</a></li>
								
								<li class="nav-item"><a href="http://localhost:8080/laralife/public/alimentazione/storico" class="nav-link">📅 Storico - Pasti</a></li>
								<li class="nav-item"><a href="http://localhost:8080/laralife/public/alimentazione/pasti/oggi" class="nav-link">📅 Oggi - Pasti (non funz)</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="main-content">
        @yield('content')
    </div>

    {{-- Tom Select init --}}
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.querySelectorAll('select[name="lezione_id"]').forEach(sel => new TomSelect(sel));
    </script>

    {{-- ✅ Bootstrap Bundle JS (necessario per dropdown/accordion) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
	
</body>
</html>
